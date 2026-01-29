#!/bin/bash

################################################################################
# GFS Backup Script for AWS S3
# Backs up /sites/default/files to S3 with Grandfather-Father-Son rotation
# Daily backups are incremental, weekly backups are full
# Uses s3fs to mount S3 bucket for efficient incremental backups
################################################################################

# Configuration
export AWS_CONFIG_FILE="${HOME}/.aws/config" # change it per user
SITE_NAME="IIED-main"
SOURCE_DIR="${HOME}/Sites/${SITE_NAME}/docroot/sites/default/files"
S3_BUCKET="s3://iied-backup/${SITE_NAME}"
S3_BUCKET_NAME="iied-backup"  # Bucket name without s3:// prefix
S3_MOUNT_POINT="/tmp/s3-mount-${SITE_NAME}"
BACKUP_BASE="${S3_BUCKET}/IIED_files"
BACKUP_BASE_LOCAL="${S3_MOUNT_POINT}/IIED_files"
LOCK_FILE="/tmp/s3_backup.lock"
LOG_FILE="/var/log/s3backup.log"
TEMP_BASE="/tmp/backups"

# MacOS specific
PATH_TO_AWS="/opt/homebrew/bin/aws"
PATH_TO_S3FS="/opt/homebrew/bin/s3fs"
DATE_CMD="/opt/homebrew/bin/gdate" 

# Local cache for reference backup
LOCAL_REFERENCE_CACHE="${TEMP_BASE}/reference-cache-${SITE_NAME}"
CACHE_MARKER="${LOCAL_REFERENCE_CACHE}/.cache-info"

# Retention periods
DAILY_RETENTION=14 # 14 days
WEEKLY_RETENTION=26 # 26 weeks
MONTHLY_RETENTION=12 # 12 months (1 year)
# 6-monthly backups retained indefinitely

# Date calculations
TODAY=$($DATE_CMD +%Y-%m-%d)
DAY_OF_WEEK=$($DATE_CMD +%u) # 1-7 (Monday-Sunday)
DAY_OF_MONTH=$($DATE_CMD +%d) # 01-31
WEEK_OF_MONTH=$(( ($($DATE_CMD +%d) - 1) / 7 + 1 ))
MONTH=$($DATE_CMD +%m)

# S3FS mounted flag
S3FS_MOUNTED=0

################################################################################
# Functions
################################################################################

log() {
    echo "[$($DATE_CMD '+%Y-%m-%d %H:%M:%S')] $1" | tee -a "$LOG_FILE"
}

error_exit() {
    log "ERROR: $1"
    # Don't unmount s3fs on error - keep it for manual inspection
    rm -f "$LOCK_FILE"
    exit 1
}

check_dependencies() {
    command -v rsync >/dev/null 2>&1 || error_exit "rsync is not installed"
    command -v $PATH_TO_AWS >/dev/null 2>&1 || error_exit "AWS CLI is not installed"
    command -v $PATH_TO_S3FS >/dev/null 2>&1 || error_exit "s3fs is not installed. Install with: brew install s3fs"
    command -v $DATE_CMD >/dev/null 2>&1 || error_exit "gdate is not installed. Install with: brew install coreutils"

    # Check AWS credentials
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

mount_s3fs() {
    log "Mounting S3 bucket using s3fs..."
    
    # Create mount point if it doesn't exist
    mkdir -p "$S3_MOUNT_POINT"
    
    # Check if already mounted
    if mount | grep -q "$S3_MOUNT_POINT"; then
        log "S3 bucket already mounted at $S3_MOUNT_POINT"
        S3FS_MOUNTED=1
        return 0
    fi
    
    # Mount the S3 bucket
    $PATH_TO_S3FS "$S3_BUCKET_NAME" "$S3_MOUNT_POINT" \
        -o passwd_file=${HOME}/.passwd-s3fs \
        -o allow_other \
        2>&1 | tee -a "$LOG_FILE" &
    
    # Wait for mount to be ready
    sleep 3
    
    if ! mount | grep -q "$S3_MOUNT_POINT"; then
        error_exit "Failed to mount S3 bucket"
    fi
    
    S3FS_MOUNTED=1
    log "S3 bucket mounted successfully at $S3_MOUNT_POINT"
}

unmount_s3fs() {
    if [ "$S3FS_MOUNTED" -eq 1 ]; then
        log "Unmounting S3 bucket..."
        
        # Give processes time to finish
        sleep 2
        
        # Unmount
        if mount | grep -q "$S3_MOUNT_POINT"; then
            umount "$S3_MOUNT_POINT" 2>/dev/null || umount -f "$S3_MOUNT_POINT" 2>/dev/null
            
            if [ $? -eq 0 ]; then
                log "S3 bucket unmounted successfully"
            else
                log "Warning: Could not unmount S3 bucket cleanly"
            fi
        fi
        
        S3FS_MOUNTED=0
        
        # Clean up mount point
        rmdir "$S3_MOUNT_POINT" 2>/dev/null
    fi
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

find_latest_full_backup() {
    # Find the most recent full backup (weekly, monthly, or biannual)
    log "Looking for latest full backup..."
    
    # Check biannual first (most recent)
    if [ -d "${BACKUP_BASE_LOCAL}/biannual" ]; then
        local latest_biannual=$(ls -1 "${BACKUP_BASE_LOCAL}/biannual" 2>/dev/null | sort -r | head -n 1)
        if [ -n "$latest_biannual" ] && [ -d "${BACKUP_BASE_LOCAL}/biannual/$latest_biannual" ]; then
            echo "biannual/$latest_biannual"
            return
        fi
    fi
    
    # Check monthly
    if [ -d "${BACKUP_BASE_LOCAL}/monthly" ]; then
        local latest_monthly=$(ls -1 "${BACKUP_BASE_LOCAL}/monthly" 2>/dev/null | sort -r | head -n 1)
        if [ -n "$latest_monthly" ] && [ -d "${BACKUP_BASE_LOCAL}/monthly/$latest_monthly" ]; then
            echo "monthly/$latest_monthly"
            return
        fi
    fi
    
    # Check weekly
    if [ -d "${BACKUP_BASE_LOCAL}/weekly" ]; then
        local latest_weekly=$(ls -1 "${BACKUP_BASE_LOCAL}/weekly" 2>/dev/null | sort -r | head -n 1)
        if [ -n "$latest_weekly" ] && [ -d "${BACKUP_BASE_LOCAL}/weekly/$latest_weekly" ]; then
            echo "weekly/$latest_weekly"
            return
        fi
    fi
    
    echo ""
}

is_cache_valid() {
    local expected_backup=$1
    
    # Check if cache directory exists and has the marker file
    if [ ! -d "$LOCAL_REFERENCE_CACHE" ] || [ ! -f "$CACHE_MARKER" ]; then
        return 1
    fi
    
    # Check if the cached backup matches the expected one
    local cached_backup=$(cat "$CACHE_MARKER" 2>/dev/null)
    if [ "$cached_backup" != "$expected_backup" ]; then
        return 1
    fi
    
    log "Valid cache found for: $expected_backup"
    return 0
}

update_local_cache() {
    local backup_path=$1
    local backup_identifier=$2
    
    log "Updating local reference cache from: ${backup_path}"
    
    # Remove old cache
    rm -rf "$LOCAL_REFERENCE_CACHE"
    
    # Create new cache directory
    mkdir -p "$LOCAL_REFERENCE_CACHE"
    
    # Copy/sync the backup to local cache via the S3 mount
    rsync -av "${backup_path}/" "${LOCAL_REFERENCE_CACHE}/" 2>&1 | tee -a "$LOG_FILE"
    
    if [ ${PIPESTATUS[0]} -ne 0 ]; then
        log "Warning: Failed to update local cache"
        rm -rf "$LOCAL_REFERENCE_CACHE"
        return 1
    fi
    
    # Create marker file
    echo "$backup_identifier" > "$CACHE_MARKER"
    
    log "Local reference cache updated successfully"
    return 0
}

perform_full_backup() {
    local backup_type=$1
    local backup_path_local="${BACKUP_BASE_LOCAL}/${backup_type}/${TODAY}"

    log "Starting FULL ${backup_type} backup to ${backup_path_local}"

    # Create backup directory on S3 mount
    mkdir -p "$backup_path_local"

    # Perform full rsync backup directly to mounted S3
    rsync -avz --delete \
        --exclude='*.tmp' \
        --exclude='*.cache' \
        "$SOURCE_DIR/" "$backup_path_local/" 2>&1 | tee -a "$LOG_FILE"

    if [ ${PIPESTATUS[0]} -ne 0 ]; then
        error_exit "Rsync failed for full backup"
    fi

    # Create a marker file with metadata
    cat > "${backup_path_local}/.backup-metadata.txt" << EOF
Backup completed: $($DATE_CMD)
Backup type: ${backup_type} (FULL)
Source: ${SOURCE_DIR}
EOF

    log "FULL ${backup_type} backup completed successfully"
    
    # Update local cache with this new full backup
    log "Updating local cache with new full backup..."
    update_local_cache "$backup_path_local" "${backup_type}/${TODAY}"
}

perform_incremental_backup() {
    local backup_type=$1
    local backup_path_local="${BACKUP_BASE_LOCAL}/${backup_type}/${TODAY}"
    
    log "Starting INCREMENTAL ${backup_type} backup to ${backup_path_local}"
    
    # Find the latest full backup to use as reference
    local latest_full=$(find_latest_full_backup)
    
    if [ -z "$latest_full" ]; then
        log "No previous full backup found. Performing full backup instead."
        perform_full_backup "$backup_type"
        return
    fi
    
    log "Latest full backup: ${latest_full}"
    
    # Verify the backup path exists before trying to cache it
    if [ ! -d "${BACKUP_BASE_LOCAL}/${latest_full}" ]; then
        log "Warning: Latest full backup path does not exist: ${BACKUP_BASE_LOCAL}/${latest_full}"
        log "Performing full backup instead."
        perform_full_backup "$backup_type"
        return
    fi
    
    # Check if we have a valid local cache
    if ! is_cache_valid "$latest_full"; then
        log "Local cache invalid or missing. Updating cache..."
        update_local_cache "${BACKUP_BASE_LOCAL}/${latest_full}" "$latest_full"
        
        if [ $? -ne 0 ]; then
            log "Failed to create local cache. Falling back to full backup."
            perform_full_backup "$backup_type"
            return
        fi
    else
        log "Using existing local cache for reference"
    fi
    
    # Create backup directory on S3 mount
    mkdir -p "$backup_path_local"
    
    # Perform incremental rsync backup using --link-dest with local cache
    log "Performing incremental backup with reference: ${LOCAL_REFERENCE_CACHE}"
    rsync -avz --delete \
        --link-dest="$LOCAL_REFERENCE_CACHE" \
        --exclude='*.tmp' \
        --exclude='*.cache' \
        "$SOURCE_DIR/" "$backup_path_local/" 2>&1 | tee -a "$LOG_FILE"
    
    if [ ${PIPESTATUS[0]} -ne 0 ]; then
        error_exit "Rsync failed for incremental backup"
    fi
    
    # Create a marker file with metadata
    cat > "${backup_path_local}/.backup-metadata.txt" << EOF
Backup completed: $($DATE_CMD)
Backup type: ${backup_type} (INCREMENTAL)
Reference backup: ${latest_full}
Source: ${SOURCE_DIR}
EOF
    
    log "INCREMENTAL ${backup_type} backup completed successfully"
}

perform_backup() {
    local backup_type=$1
    
    # Create temp base directory if it doesn't exist
    mkdir -p "$TEMP_BASE"
    
    # Full backups for weekly, monthly, and biannual
    # Incremental backups for daily
    if [ "$backup_type" == "daily" ]; then
        perform_incremental_backup "$backup_type"
    else
        perform_full_backup "$backup_type"
    fi
}

cleanup_old_backups() {
    log "Starting cleanup of old backups"

    # Cleanup daily backups older than retention period
    log "Cleaning up daily backups older than ${DAILY_RETENTION} days"
    if [ -d "${BACKUP_BASE_LOCAL}/daily" ]; then
        for backup_dir in "${BACKUP_BASE_LOCAL}/daily"/*; do
            if [ -d "$backup_dir" ]; then
                backup_date=$(basename "$backup_dir")
                backup_epoch=$($DATE_CMD -d "$backup_date" +%s 2>/dev/null)
                cutoff_epoch=$($DATE_CMD -d "${DAILY_RETENTION} days ago" +%s)

                if [ -n "$backup_epoch" ] && [ "$backup_epoch" -lt "$cutoff_epoch" ]; then
                    log "Deleting old daily backup: ${backup_date}"
                    rm -rf "$backup_dir"
                fi
            fi
        done
    fi

    # Cleanup weekly backups older than retention period
    log "Cleaning up weekly backups older than ${WEEKLY_RETENTION} weeks"
    if [ -d "${BACKUP_BASE_LOCAL}/weekly" ]; then
        for backup_dir in "${BACKUP_BASE_LOCAL}/weekly"/*; do
            if [ -d "$backup_dir" ]; then
                backup_date=$(basename "$backup_dir")
                backup_epoch=$($DATE_CMD -d "$backup_date" +%s 2>/dev/null)
                cutoff_epoch=$($DATE_CMD -d "${WEEKLY_RETENTION} weeks ago" +%s)

                if [ -n "$backup_epoch" ] && [ "$backup_epoch" -lt "$cutoff_epoch" ]; then
                    log "Deleting old weekly backup: ${backup_date}"
                    rm -rf "$backup_dir"
                fi
            fi
        done
    fi

    # Cleanup monthly backups older than retention period
    log "Cleaning up monthly backups older than ${MONTHLY_RETENTION} months"
    if [ -d "${BACKUP_BASE_LOCAL}/monthly" ]; then
        for backup_dir in "${BACKUP_BASE_LOCAL}/monthly"/*; do
            if [ -d "$backup_dir" ]; then
                backup_date=$(basename "$backup_dir")
                backup_epoch=$($DATE_CMD -d "$backup_date" +%s 2>/dev/null)
                cutoff_epoch=$($DATE_CMD -d "${MONTHLY_RETENTION} months ago" +%s)

                if [ -n "$backup_epoch" ] && [ "$backup_epoch" -lt "$cutoff_epoch" ]; then
                    log "Deleting old monthly backup: ${backup_date}"
                    rm -rf "$backup_dir"
                fi
            fi
        done
    fi

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

    # Trap to ensure lock is released on exit (but keep s3fs mounted)
    trap release_lock EXIT INT TERM

    # Check if source directory exists
    if [ ! -d "$SOURCE_DIR" ]; then
        error_exit "Source directory does not exist: $SOURCE_DIR"
    fi

    # Mount S3 bucket (will reuse if already mounted)
    mount_s3fs

    # Determine backup type based on GFS schedule
    BACKUP_TYPE=$(determine_backup_type)
    log "Backup type determined: ${BACKUP_TYPE}"

    # Perform the backup
    perform_backup "$BACKUP_TYPE"

    # Cleanup old backups according to retention policy
    cleanup_old_backups

    # Keep S3 bucket mounted for next run
    log "Keeping S3 bucket mounted for future backups"

    # Send success notification
    send_notification "SUCCESS" "Backup completed successfully (${BACKUP_TYPE})"

    log "=== Backup Process Completed Successfully ==="
}

# Run main function
main