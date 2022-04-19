<?php

/**
 * @file
 * Documentation for Views Layout API.
 */

use Drupal\views\ViewExecutable;
use Drupal\views\Views;
use \Drupal\views\Plugin\views\cache\CachePluginBase;

/**
 * Callback for skipped regions.
 *
 * @param $plugin_id
 *   The id of the layout being rendered.
 * @param $region
 *   The name of the skipped region being rendered.
 * @param $view
 *   The view being rendered.
 *
 * @return array
 *   A render array for the skipped region.
 */
function example_callback($plugin_id, $region, ViewExecutable $view) {

  // Find two distinct random nodes and store in a static variable.
  $random = &drupal_static(__FUNCTION__, array());
  if (empty($random)) {
    $query = \Drupal::service('entity.query');
    $ids = $query->get('node')
      ->condition('type', 'article')
      ->condition('status', 1)
      ->execute();
    $ids = array_values($ids);
    shuffle($ids);
    $random = array_slice($ids, 0, 2);
  }

  // Render the random values in the skipped regions.
  switch ($region) {
    case 'three':
      $id = $random[0];
      break;
    case 'seven':
      $id = $random[1];
      break;
  }
  $view_builder = \Drupal::entityTypeManager()->getViewBuilder('node');
  $node = \Drupal::entityTypeManager()->getStorage('node')->load($id);
  return $view_builder->view($node, 'teaser');
}

/**
 * Implementation of hook_views_post_render().
 *
 * Another way to add content to skipped regions. This works only if the
 * regions were not skipped using the 'Skip' setting, since in that case the
 * regions won't exist at all in $output.
 *
 * This method will work with any selected region, so could also be used to
 * replace, or append values to, either populated or skipped regions.
 */
function example_views_post_render(ViewExecutable $view, &$output, CachePluginBase $cache) {

  // The view that contains skipped regions.
  $view_id = 'view_name';
  $display_id = 'block_1';

  if ($view->id() == $view_id && $view->current_display == $display_id) {

    // The skipped regions in this layout.
    $skipped_regions = ['three', 'seven'];

    // The render array that will be inserted.
    $renderArray = [
      '#type' => 'markup',
      '#markup' => 'Some text',
    ];

    // Iterate over views results.
    foreach ($output['#rows']['#rows'] as $delta => $row) {
      foreach ($row as $region => $value) {

        // Inject the render array into skipped regions.
        if (in_array($region, $skipped_regions)) {
          $output['#rows']['#rows'][$delta][$region] = [$renderArray];
        }
      }
    }
  }
}
