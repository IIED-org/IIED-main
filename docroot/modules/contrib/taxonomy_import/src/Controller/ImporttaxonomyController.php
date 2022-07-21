<?php

namespace Drupal\taxonomy_import\Controller;

/**
 * Contains \Drupal\taxonomy_import\Controller\ImporttaxonomyController.
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
