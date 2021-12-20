<?php

namespace Drupal\iied_migrate_d7\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\node\Plugin\migrate\source\d7\NodeComplete;
use Drupal\node\Plugin\migrate\source\d7\Node;
use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\d7\FieldableEntity;


/**
 * Source plugin for paragraphs field: field_about_the_author.
 *
 * @MigrateSource(
 *   id = "d7_field_about_the_author"
 * )
 */
class D7FieldAboutTheAuthor extends FieldableEntity {

  /**
   * {@inheritdoc}
   */
  public function query() {

    $query = $this->select('paragraphs_item', 'pi')
      ->fields('pi', [
        'item_id',
        'field_name',
        'bundle',
        'revision_id',
      ]);
    if (isset($this->configuration['field_name'])) {
      $query->leftJoin('field_data_field_about_the_author', 'fd', 'fd.' . 'field_about_the_author_value = pi.item_id');
      $query->fields(
        'fd',
        [
          'entity_type',
          'entity_id',
          'bundle',
          'field_about_the_author_revision_id',
        ]
      );
      $query->condition('pi.field_name', 'field_about_the_author');
      $types = ['article', 'blog', 'event', 'media_release'];
      $query->condition('fd.bundle', $types, 'IN');

      // Join the field_data_field_author_biog.
      $query->leftJoin('field_data_field_author_biog', 'fab', 'fab.entity_id = fd.' . 'field_about_the_author_value');
      $query->fields(
        'fab',
        [
          'field_author_biog_value',
        ]
      );
      // Join the field_data_field_author_photo when needed.
      $query->leftJoin('field_data_field_author_photo', 'fap', 'fap.entity_id = fd.' . 'field_about_the_author_value');
      $query->fields(
        'fap',
        [
          'field_author_photo_target_id',
        ]
      );

    }

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'item_id' => $this->t('Item ID'),
      'revision_id' => $this->t('Revision ID'),
      'field_name' => $this->t('Name of field'),
    ];
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['item_id']['type'] = 'integer';
    $ids['item_id']['alias'] = 'pi';
    return $ids;
  }

}
