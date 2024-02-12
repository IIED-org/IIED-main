<?php

namespace Drupal\facets_pretty_paths_test\Plugin\facets_pretty_paths\coder;

use Drupal\facets_pretty_paths\Coder\CoderPluginBase;

/**
 * A dummy coder.
 *
 * @FacetsPrettyPathsCoder(
 *   id = "dummy_coder",
 *   label = @Translation("Dummy"),
 *   description = @Translation("A dummy coder used for testing")
 * )
 */
class DummyCoder extends CoderPluginBase {

  /**
   * {@inheritdoc}
   */
  public function encode($id) {
    return "dummy-$id";
  }

  /**
   * {@inheritdoc}
   */
  public function decode($alias) {
    $exploded = explode('-', $alias);
    return $exploded[1];
  }

}
