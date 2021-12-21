<?php

namespace Drupal\iied_migrate_d7\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\node\Plugin\migrate\source\d7\NodeComplete;
use Drupal\node\Plugin\migrate\source\d7\Node;
use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\d7\FieldableEntity;


/**
 * Source plugin for project content.
 *
 * This is a useful sub-class of NodeComplete to allow us to override the query
 * or alter the data in prepareRow().
 *
 * @MigrateSource(
 *   id = "d7_field_paragraphs"
 * )
 */
class D7FieldParagraphs extends FieldableEntity {

  /**
   * {@inheritdoc}
   */
  public function query() {

    // @todo check if we should restrict this query to only select paragraphs
    // that are attached to content types that we are migrating: blog, article,
    // but not one_page.

    $query = $this->select('paragraphs_item', 'pi')
      ->fields('pi', [
        'item_id',
        'field_name',
        'bundle',
        'revision_id',
      ]);
    if (isset($this->configuration['field_name'])) {
      $query->leftJoin('field_data_' . $this->configuration['field_name'], 'fd', 'fd.' . $this->configuration['field_name'] . '_value = pi.item_id');
      $query->fields(
        'fd',
        [
          'entity_type',
          'entity_id',
          'bundle',
          $this->configuration['field_name'] . '_revision_id',
        ]
      );
      $query->condition('pi.field_name', $this->configuration['field_name']);
      $types = ['article', 'blog', 'event', 'media_release'];
      $query->condition('fd.bundle', $types, 'IN');

      // Join the field_data_field_basic_text.
      $query->leftJoin('field_data_field_basic_text', 'fbt', 'fbt.entity_id = fd.' . $this->configuration['field_name'] . '_value');
      $query->fields(
        'fbt',
        [
          'field_basic_text_value',
        ]
      );
      // Join the field_data_upload when needed.
      $query->leftJoin('field_data_upload', 'fdu', 'fdu.entity_id = fd.' . $this->configuration['field_name'] . '_value');
      $query->fields(
        'fdu',
        [
          'upload_fid',
          'upload_description',
        ]
      );

      // Join the field_data_field_main_image when needed.
      $query->leftJoin('field_data_field_main_image', 'fmi', 'fmi.entity_id = fd.' . $this->configuration['field_name'] . '_value');
      $query->fields(
        'fmi',
        [
          'field_main_image_fid',
        ]
      );

      // Join the field_data_field_video_embed when needed.
      $query->leftJoin('field_data_field_video  ', 'fdfv', 'fdfv.entity_id = fd.' . $this->configuration['field_name'] . '_value');
      $query->fields(
        'fdfv',
        [
          'entity_id',
          'delta',
          'field_video_video_url',
        ]
      );

      // Join the field_data_field_video_description.
      $query->leftJoin('field_data_field_video_description  ', 'fdfvd', 'fdfvd.entity_id = fd.' . $this->configuration['field_name'] . '_value');
      $query->fields(
        'fdfvd',
        [
          'entity_id',
          'delta',
          'field_video_description_value',
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
