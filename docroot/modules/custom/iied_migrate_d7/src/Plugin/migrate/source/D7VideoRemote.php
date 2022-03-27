<?php

namespace Drupal\iied_migrate_d7\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;

/**
 * Drupal 7 file source (optionally filtered by type) from database.
 *
 * See https://www.computerminds.co.uk/articles/migrating-drupal-7-files-drupal-8-9-media-entities
 *
 * @MigrateSource(
 *   id = "d7_video_remote",
 *   source_module = "node"
 * )
 */
class D7VideoRemote extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {

    // Source data is queried from 'field_data_field_video' table.
    $query = $this->select('field_data_field_video', 'fv')
      ->fields('fv', [
        'entity_type',
        'bundle',
        'deleted',
        'entity_id',
        'revision_id',
        'language',
        'delta',
        'field_video_video_url',
        'field_video_thumbnail_path',
        'field_video_embed_code',
        'field_video_description',
      ]);
    $query->orderBy('entity_id');
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = parent::fields();
    $fields['entity_id'] = $this->t('Entity ID.');
    $fields['revision_id'] = $this->t('Revision ID');
    $fields['field_video_video_url'] = $this->t('URL');
    $fields['field_video_thumbnail_path'] = $this->t('Thumbnail');
    $fields['field_video_video_data'] = $this->t('Video data');
    $fields['field_video_description'] = $this->t('Description');

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['entity_id']['type'] = 'integer';
    $ids['entity_id']['alias'] = 'fv';
    $ids['delta']['type'] = 'integer';
    $ids['delta']['alias'] = 'fv';
    return $ids;
  }

}
