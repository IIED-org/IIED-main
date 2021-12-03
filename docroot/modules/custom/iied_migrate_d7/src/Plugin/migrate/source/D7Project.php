<?php

namespace Drupal\iied_migrate_d7\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\node\Plugin\migrate\source\d7\NodeComplete;
use Drupal\node\Plugin\migrate\source\d7\Node;

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
class D7Project extends Node {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = parent::query();
    // Since we are mapping incoming projects to existing nodes, we only want to
    // select the source project nodes that are published.
    $query->condition('n.status', 1);
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $x = '';
    return parent::prepareRow($row);
  }

}
