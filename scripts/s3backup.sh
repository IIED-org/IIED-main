#!/bin/bash

################################################################################
# GFS Backup Script for aws S3
# Backs up /sites/default/files to S3 with Grandfather-Father-Son rotation
################################################################################

# Configuration
export AWS_CONFIG_FILE="~/.aws/config" #user-specific
export AWS_ACCESS_KEY_ID=########
export AWS_SECRET_ACCESS_KEY=########
SITE_NAME="iied.org"
SOURCE_DIR="/var/www/prod.${SITE_NAME}/gitroot/docroot/sites/default/files"
S3_BUCKET="s3://iied-backup/${SITE_NAME}"
BACKUP_BASE="${S3_BUCKET}/IIED_files"
LOCK_FILE="/tmp/s3backup.lock"
LOG_FILE="/var/log/s3backup.log"
PATH_TO_AWS="path/to/aws"

# Retention periods
DAILY_RETENTION=14 # 14 days
WEEKLY_RETENTION=26 # 26 weeks
MONTHLY_RETENTION=12 # 12 months (1 year)
# 6-monthly backups retained indefinitely

# Date calculations
TODAY=$(date +%Y-%m-%d)
DAY_OF_WEEK=$(date +%u) # 1-7 (Monday-Sunday)
DAY_OF_MONTH=$(date +%d) # 01-31
WEEK_OF_MONTH=$(( ($(date +%d) - 1) / 7 + 1 ))
MONTH=$(date +%m)

################################################################################
# Functions
################################################################################

log() {
echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" | tee -a "$LOG_FILE"
}

error_exit() {
log "ERROR: $1"
rm -f "$LOCK_FILE"
exit 1
}

check_dependencies() {
command -v rsync >/dev/null 2>&1 || error_exit "rsync is not installed"
command -v $PATH_TO_AWS >/dev/null 2>&1 || error_exit "aws CLI is not installed"

# Check aws credentials
$PATH_TO_AWS s3 ls "$S3_BUCKET" >/dev/null 2>&1 || error_exit "Cannot access S3 bucket: $S3_BUCKET"
}

acquire_lock() {
if [ -f "$LOCK_FILE" ]; then
error_exit "Backup already running (lock file exists)"
fi
touch "$LOCK_FILE" || error_exit "Cannot create lock file"
}

release_lock() {
rm -f "$LOCK_FILE"
}

determine_backup_type() {
# GFS logic: promote backups based on rotation schedule

# 6-monthly backup (on 1st day of January and July)
if [[ "$DAY_OF_MONTH" == "01" ]] && [[ "$MONTH" =~ ^(01|07)$ ]]; then
echo "biannual"
return
fi

# Monthly backup (first day of the month)
if [ "$DAY_OF_MONTH" == "01" ]; then
echo "monthly"
return
fi

# Weekly backup (Monday = day 1)
if [ "$DAY_OF_WEEK" == "1" ]; then
echo "weekly"
return
fi

# Daily backup
echo "daily"
}

perform_backup() {
local backup_type=$1
local backup_path="${BACKUP_BASE}/${backup_type}/${TODAY}"

log "Starting ${backup_type} backup to ${backup_path}"

# Use rsync to sync to a temporary local directory first, then upload to S3
# This is more efficient than directly syncing to S3

TEMP_BACKUP="/tmp/${SITE_NAME}-${TODAY}"

# Create tar, perform rsync backup
rsync -avz --delete \
--exclude='*.tmp' \
--exclude='*.cache' \
"$SOURCE_DIR/" "$TEMP_BACKUP/" 2>&1 | tee -a "$LOG_FILE"

if [ ${PIPESTATUS[0]} -ne 0 ]; then
rm -rf "$TEMP_BACKUP"
error_exit "Rsync failed"
fi

# Upload to S3
log "Uploading to S3: ${backup_path}"
$PATH_TO_AWS s3 sync "$TEMP_BACKUP/" "${backup_path}/" --delete 2>&1 | tee -a "$LOG_FILE"

if [ $? -ne 0 ]; then
rm -rf "$TEMP_BACKUP"
error_exit "S3 upload failed"
fi

# Create a marker file with metadata
echo "Backup completed: $(date)" > /tmp/backup-metadata.txt
echo "Backup type: ${backup_type}" >> /tmp/backup-metadata.txt
echo "Source: ${SOURCE_DIR}" >> /tmp/backup-metadata.txt
$PATH_TO_AWS s3 cp /tmp/backup-metadata.txt "${backup_path}/.backup-metadata.txt"
rm -f /tmp/backup-metadata.txt

# Cleanup temp directory
rm -rf "$TEMP_BACKUP"

log "${backup_type} backup completed successfully"
}

cleanup_old_backups() {
log "Starting cleanup of old backups"

# Cleanup daily backups older than retention period
log "Cleaning up daily backups older than ${DAILY_RETENTION} days"
$PATH_TO_AWS s3 ls "${BACKUP_BASE}/daily/" | awk '{print $2}' | while read -r backup_dir; do
backup_date="${backup_dir%/}"
if [ -n "$backup_date" ]; then
backup_epoch=$(date -d "$backup_date" +%s 2>/dev/null)
cutoff_epoch=$(date -d "${DAILY_RETENTION} days ago" +%s)

if [ -n "$backup_epoch" ] && [ "$backup_epoch" -lt "$cutoff_epoch" ]; then
log "Deleting old daily backup: ${backup_date}"
$PATH_TO_AWS s3 rm "${BACKUP_BASE}/daily/${backup_date}/" --recursive
fi
fi
done

# Cleanup weekly backups older than retention period
log "Cleaning up weekly backups older than ${WEEKLY_RETENTION} weeks"
$PATH_TO_AWS s3 ls "${BACKUP_BASE}/weekly/" | awk '{print $2}' | while read -r backup_dir; do
backup_date="${backup_dir%/}"
if [ -n "$backup_date" ]; then
backup_epoch=$(date -d "$backup_date" +%s 2>/dev/null)
cutoff_epoch=$(date -d "${WEEKLY_RETENTION} weeks ago" +%s)

if [ -n "$backup_epoch" ] && [ "$backup_epoch" -lt "$cutoff_epoch" ]; then
log "Deleting old weekly backup: ${backup_date}"
$PATH_TO_AWS s3 rm "${BACKUP_BASE}/weekly/${backup_date}/" --recursive
fi
fi
done

# Cleanup monthly backups older than retention period
log "Cleaning up monthly backups older than ${MONTHLY_RETENTION} months"
$PATH_TO_AWS s3 ls "${BACKUP_BASE}/monthly/" | awk '{print $2}' | while read -r backup_dir; do
backup_date="${backup_dir%/}"
if [ -n "$backup_date" ]; then
backup_epoch=$(date -d "$backup_date" +%s 2>/dev/null)
cutoff_epoch=$(date -d "${MONTHLY_RETENTION} months ago" +%s)

if [ -n "$backup_epoch" ] && [ "$backup_epoch" -lt "$cutoff_epoch" ]; then
log "Deleting old monthly backup: ${backup_date}"
$PATH_TO_AWS s3 rm "${BACKUP_BASE}/monthly/${backup_date}/" --recursive
fi
fi
done

# Biannual backups are kept indefinitely (no cleanup)
log "Biannual backups are retained indefinitely"

log "Cleanup completed"
}

send_notification() {
local status=$1
local message=$2

# Optionally implement email/SNS notifications here
log "Notification: ${status} - ${message}"
}

################################################################################
# Main execution
################################################################################

main() {
log "=== Starting GFS Backup Process ==="

# Check dependencies
check_dependencies

# Acquire lock to prevent concurrent runs
acquire_lock

# Trap to ensure lock is released on exit
trap release_lock EXIT

# Check if source directory exists
if [ ! -d "$SOURCE_DIR" ]; then
error_exit "Source directory does not exist: $SOURCE_DIR"
fi

# Determine backup type based on GFS schedule
BACKUP_TYPE=$(determine_backup_type)
log "Backup type determined: ${BACKUP_TYPE}"

# Perform the backup
perform_backup "$BACKUP_TYPE"

# Cleanup old backups according to retention policy
cleanup_old_backups

# Send success notification
send_notification "SUCCESS" "Backup completed successfully (${BACKUP_TYPE})"

log "=== Backup Process Completed Successfully ==="
}

# Run main function
main