<?php

namespace Drupal\iied_migrate_d7\Plugin\migrate\source;

use Drupal\node\Plugin\migrate\source\d7\Node;

/**
 * Source plugin for article content.
 *
 * This is a useful sub-class of Node to allow us to override the query
 * or alter the data in prepareRow().
 *
 * @MigrateSource(
 *   id = "iied_d7_news_media",
 *   source_module = "node"
 * )
 */
class D7NewsMedia extends Node {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = parent::query();
    // Select only the source nodes that are published.
    $query->condition('n.status', 1);
    // $query->condition('n.nid', '3979');
    return $query;
  }

}
