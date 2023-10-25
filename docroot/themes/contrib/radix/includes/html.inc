<?php

/**
 * @file
 * Theme and preprocess functions for html wrapper.
 */

use Drupal\node\NodeInterface;

/**
 *
 */
function radix_preprocess_html(&$variables) {
  if (($node = \Drupal::routeMatch()->getParameter('node')) || ($node = \Drupal::routeMatch()->getParameter('node_preview'))) {
    if ($node instanceof NodeInterface) {
      // Add node ID body class.
      $variables['attributes']['class'][] = 'node-' . $node->id();

      // Add content type machine name body class.
      $variables['attributes']['class'][] = 'node-type--' . str_replace('_', '-', $node->bundle());
    }
  }

  // * Check different error conditions to add body classes.
  $route_name = \Drupal::request()->attributes->get('_route');
  if ('system.404' === $route_name) {
    $variables['attributes']['class'][] = 'error404';
  }
  if ('system.403' === $route_name) {
    $variables['attributes']['class'][] = 'error403';
  }

  // * Get theme path in JS
  $variables['#attached']['drupalSettings']['path']['themeUrl'] = \Drupal::theme()->getActiveTheme()->getPath();
}

/**
 * Implements hook_theme_suggestions_HOOK_alter().
 *
 * Add additional template suggestion based on node type.
 */
function radix_theme_suggestions_html_alter(array &$suggestions, array $variables) {
  /** @var \Drupal\node\Entity\Node $node */
  
  $node = \Drupal::routeMatch()->getParameter('node');
  if ($node instanceof \Drupal\node\NodeInterface) {
    $suggestions[] = 'html__node__' . $node->getType();
  }
}
