<?php

namespace Drupal\facets_pretty_paths\Plugin\facets_pretty_paths\coder;

use Drupal\facets_pretty_paths\Coder\CoderPluginBase;
use Drupal\node\Entity\Node;

/**
 * Node title facets pretty paths coder.
 *
 * @FacetsPrettyPathsCoder(
 *   id = "node_title_coder",
 *   label = @Translation("Node title + id"),
 *   description = @Translation("Use node title with entity id, e.g. /color/<strong>blue-2</strong>")
 * )
 */
class NodeTitleCoder extends CoderPluginBase {

  /**
   * Encode an id into an alias.
   *
   * @param string $nid
   *   Node id.
   *
   * @return string
   *   An alias.
   */
  public function encode($nid) {
    if ($node = Node::load($nid)) {
      $label = $node->label();
      $label = \Drupal::service('pathauto.alias_cleaner')
        ->cleanString($label);
      return $label . '-' . $nid;
    }
    return $nid;
  }

  /**
   * Decodes an alias back to an id.
   *
   * @param string $alias
   *   An alias.
   *
   * @return string
   *   An id.
   */
  public function decode($alias) {
    $exploded = explode('-', $alias);
    $id = array_pop($exploded);

    return $id;
  }

}
