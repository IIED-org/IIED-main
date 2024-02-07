<?php

namespace Drupal\facets_pretty_paths\Coder;

/**
 * Describes the public API for coder plugins.
 */
interface CoderInterface {

  /**
   * Transforms a raw value into an url value.
   *
   * @param string $id
   *   The raw value.
   *
   * @return string
   *   The pretty value.
   */
  public function encode($id);

  /**
   * Transforms a url value into a raw value.
   *
   * @param string $alias
   *   The pretty value.
   *
   * @return string
   *   The raw value.
   */
  public function decode($alias);

}
