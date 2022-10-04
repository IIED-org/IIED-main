<?php

namespace Drupal\iied_migrate_fixes\Plugin\migrate\source;

use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Source plugin for files fix migration.
 *
 * @MigrateSource(
 *   id = "fix_iied_files"
 * )
 */
class FixFiles extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Query to select all files referenced from a field_media_document field.
    $query = $this->select('file_managed', 'f');
    $query->join('media__field_media_document', 'fmd', 'f.fid=fmd.field_media_document_target_id');
    $query->fields('f');
    $query->fields('fmd');
    $query->orderBy('f.created');
    //$query->condition('f.fid', '37656');
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'fid' => $this->t('File ID'),
      'uid' => $this->t('The {users}.uid who added the file. If set to 0, this file was added by an anonymous user.'),
      'filename' => $this->t('File name'),
      'uri' => $this->t('File url'),
      'filemime' => $this->t('File MIME Type'),
      'filesize' => $this->t('File size'),
      'status' => $this->t('The published status of a file.'),
      'created' => $this->t('The time that the file was added.'),
      'changed' => $this->t('The time that the file was updated.'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['fid']['type'] = 'integer';
    $ids['fid']['alias'] = 'f';
    return $ids;
  }

}
