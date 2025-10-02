<?php

namespace Drupal\facets_pretty_paths\Plugin\facets_pretty_paths\coder;

use Drupal\facets_pretty_paths\Coder\CoderPluginBase;

/**
 * Default facets pretty paths coder.
 *
 * @FacetsPrettyPathsCoder(
 *   id = "default_coder",
 *   label = @Translation("Default"),
 *   description = @Translation("Use the raw value, no special processing is done, e.g. /color/<strong>2</strong>")
 * )
 */
class DefaultCoder extends CoderPluginBase {

  /**
   * Encode an id into an alias.
   *
   * @param string $id
   *   An entity id.
   *
   * @return string
   *   An alias.
   */
  public function encode($id) {
    return $id;
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
    return $alias;
  }

}
