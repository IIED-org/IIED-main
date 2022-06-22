<?php

namespace Drupal\iied_migrate_d7\Plugin\migrate\source;

use Drupal\node\Plugin\migrate\source\d7\Node;
use Drupal\migrate\Row;

/**
 * Source plugin for blog content.
 *
 * @MigrateSource(
 *   id = "iied_d7_blog",
 *   source_module = "node"
 * )
 */
class D7Blog extends Node {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = parent::query();
    // Select only the source article nodes that are published.
    $query->condition('n.status', 1);
    // Node 26866 is useful for testing.
    // $query->condition('n.nid', '26866');
    // $query->condition('n.nid', '2554'); // Good for second author.
    $query->condition('n.nid', '138216'); // Blog with Needs info_box component, basic_text paragraph missing styled image.
    return $query;
  }

}
