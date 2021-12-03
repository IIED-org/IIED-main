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
 *   id = "d7_paragraph_item"
 * )
 */
class D7Paragraph extends FieldableEntity {

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
      $query->innerJoin('field_data_' . $this->configuration['field_name'], 'fd', 'fd.' . $this->configuration['field_name'] . '_value = pi.item_id');
      $query->fields(
        'fd',
        [
          'entity_type',
          'entity_id',
          $this->configuration['field_name'] . '_revision_id',
        ]
      );
      $query->condition('pi.field_name', $this->configuration['field_name']);

      // Join the field_data_field_basic_text.
      $query->innerJoin('field_data_field_basic_text', 'fbt', 'fbt.entity_id = fd.' . $this->configuration['field_name'] . '_value');
      $query->fields(
        'fbt',
        [
          'field_basic_text_value',
        ]
      );

    }

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields()
  {
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
  public function getIds()
  {
    $ids['item_id']['type'] = 'integer';
    $ids['item_id']['alias'] = 'pi';
    return $ids;
  }
  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $x = '';
    return parent::prepareRow($row);
  }

}
