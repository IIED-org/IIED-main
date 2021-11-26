<?php

namespace Drupal\iied_migrate_d7\Plugin\migrate\source;

use Drupal\file\Plugin\migrate\source\d7\File;

/**
 * Drupal 7 file source (optionally filtered by type) from database.
 *
 * See https://www.computerminds.co.uk/articles/migrating-drupal-7-files-drupal-8-9-media-entities
 *
 * @MigrateSource(
 *   id = "d7_file_by_type",
 *   source_module = "file"
 * )
 */
class FileByType extends File {

  /**
   * {@inheritdoc}
   */
  public function query()
  {
    $query = parent::query();

    // Filter by file type, if configured.
    if (isset($this->configuration['type'])) {
      $query->condition('f.type', $this->configuration['type']);
    }

    // Get the alt text, if configured.
    if (isset($this->configuration['get_alt'])) {
      $alt_alias = $query->addJoin('left', 'field_data_field_file_image_alt_text', 'alt', 'f.fid = %alias.entity_id');
      $query->addField($alt_alias, 'field_file_image_alt_text_value', 'alt');
    }

    // Get the title text, if configured.
    if (isset($this->configuration['get_title'])) {
      $title_alias = $query->addJoin('left', 'field_data_field_file_image_title_text', 'title', 'f.fid = %alias.entity_id');
      $query->addField($title_alias, 'field_file_image_title_text_value', 'title');
    }

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields()
  {
    $fields = parent::fields();
    $fields['type'] = $this->t('The type of file.');
    $fields['alt'] = $this->t('Alt text of the file (if present)');
    $fields['title'] = $this->t('Title text of the file (if present)');
    return $fields;
  }
}
