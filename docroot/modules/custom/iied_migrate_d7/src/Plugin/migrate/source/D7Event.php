<?php

namespace Drupal\iied_migrate_d7\Plugin\migrate\source;

use Drupal\node\Plugin\migrate\source\d7\Node;

/**
 * Source plugin for event content.
 *
 * @MigrateSource(
 *   id = "iied_d7_event",
 *   source_module = "node"
 * )
 */
class D7Event extends Node {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = parent::query();
    // Select only source nodes that are published.
    $query->condition('n.status', 1);
    // $query->condition('n.nid', '11846');
    return $query;
  }

}
