<?php

namespace Drupal\iied_migrate_d7\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\node\Plugin\migrate\source\d7\NodeComplete;
use Drupal\migrate\Row;

/**
 * Source plugin for project content.
 *
 * This is a useful sub-class of NodeComplete to allow us to override the query
 * or alter the data in prepareRow().
 *
 * @MigrateSource(
 *   id = "iied_d7_project",
 *   source_module = "node"
 * )
 */
class D7Project extends NodeComplete {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = parent::query();
    // Add field_dates field.
    // $query->leftJoin('field_data_field_dates', 'fdfd', '[fdfd].[entity_id] = [nt].[nid]');
    // $query->addField('fdfd', 'field_dates_value', 'field_dates');

    // Add field_standfirst field.
    // $query->leftJoin('field_data_field_standfirst', 'fdfs', '[fdfs].[entity_id] = [nt].[nid]');
    // $query->addField('fdfs', 'field_standfirst_value', 'field_standfirst');

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $c = '';
    return parent::prepareRow($row);
  }

}
