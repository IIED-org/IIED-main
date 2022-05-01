<?php

namespace Drupal\iied_migrate_d7\Plugin\migrate\source;

use Drupal\node\Plugin\migrate\source\d7\Node;
use Drupal\migrate\Row;

/**
 * Source plugin for article content.
 *
 * This is a useful sub-class of Node to allow us to override the query
 * or alter the data in prepareRow().
 *
 * @MigrateSource(
 *   id = "iied_d7_article",
 *   source_module = "node"
 * )
 */
class D7Article extends Node {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = parent::query();
    // Select only the source article nodes that are published.
    $query->condition('n.status', 1);
    // Node 27381 is useful for testing as it hase a video_embed_standard para
    // that has been migrated into a video_embed paragraph on the
    // field_paragraphs field.
    // Node 140676 has multiple images in the rich text body field:
    // https://www.iied.org/conservation-discrimination-case-studies-nepals-national-parks
    // $query->condition('n.nid', '140321');
    return $query;
  }

}
