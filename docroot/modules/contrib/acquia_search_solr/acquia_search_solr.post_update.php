<?php

/**
 * @file
 * Search Solr updates once other modules have made their own updates.
 */

use Drupal\Core\PhpStorage\PhpStorageFactory;

/**
 * Clear cache to rebuild routes.
 */
function acquia_search_solr_post_update_clear_routes() {
  \Drupal::service("router.builder")->rebuild();
  PhpStorageFactory::get("twig")->deleteAll();
}
