<?php
/**
 * @file
 * Theme and preprocess functions for pages.
 */
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use Drupal\node\NodeInterface;

/**
 * Implements radix_preprocess_page().
 */
function radix_preprocess_page(&$variables) {
  // Add the site name to the page. 
  $variables['site_name'] = \Drupal::config('system.site')->get('name');
}

/**
 * Implements hook_theme_suggestions_hook_alter().
 */
function radix_theme_suggestions_page_alter(array &$suggestions, array $variables) {
  // * Add a template suggestions for node type.
  /** @var \Drupal\node\NodeInterface $node */
  if ($node = \Drupal::routeMatch()->getParameter('node')) {
    if (is_numeric($node)) {
      $node = Node::load($node);
    }

    $suggestions[] = 'page__node__' . $node->bundle();
  }

  $current_path = \Drupal::service('path.current')->getPath();
  $result = \Drupal::service('path_alias.manager')->getAliasByPath($current_path);

  $path_alias = trim($result, '/');
  $path_alias = str_replace('/', '-', $path_alias);
  $path_alias = str_replace('-', '_', $path_alias);

  $suggestions[] = 'page__path__' . $path_alias;

  // * Defines custom theme suggestions based on the route.
  $route_name = \Drupal::request()->attributes->get('_route');
  if ('system.404' === $route_name) {
    $suggestions[] = 'page__404';
  }
  if ('system.403' === $route_name) {
    $suggestions[] = 'page__403';
  }

  // * Adds page node type theme suggestion.
  $route_matcher = \Drupal::routeMatch();
  $node = $route_matcher->getParameter('node');
  $node_revision = $route_matcher->getParameter('node_revision');
  if($node_revision !== NULL) {
      $node = is_object($node_revision) ? $node_revision : \Drupal::entityTypeManager()->getStorage('node')->loadRevision($node_revision);
  }

  if ($node = \Drupal::routeMatch()->getParameter('node')) {
    if ($node instanceof NodeInterface) {
      $suggestions[] = 'page__node__' . $node->getType();
    }
  }

  // * Add view mode theme suggestions based on the vocabulary
  if (\Drupal::routeMatch()->getRouteName() == 'entity.taxonomy_term.canonical' && $tid = \Drupal::routeMatch()->getRawParameter('taxonomy_term')) {
    $term = Term::load($tid);
    $suggestions[] = 'page__taxonomy__' . $term->bundle();
  }
}