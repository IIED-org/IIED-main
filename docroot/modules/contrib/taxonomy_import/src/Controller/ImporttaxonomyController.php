<?php

namespace Drupal\taxonomy_import\Controller;

/**
 * Class for Import Taxonomy.
 */
class ImporttaxonomyController {

  /**
   * Returns a simple page.
   *
   * @return array
   *   A simple renderable array.
   */
  public function importtaxonomy() {
    $element = [
      '#markup' => 'Welcome Page!!',
    ];
    return $element;
  }

}
