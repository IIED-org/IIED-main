<?php

namespace Drupal\facets_pretty_paths;

use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Link;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Pretty Paths breadcrumb builder.
 */
class PrettyPathBreadcrumb implements BreadcrumbBuilderInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    if ($route_match->getParameter('facets_query')) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $route_match) {
    $breadcrumb = new Breadcrumb();
    $breadcrumb->addCacheContexts(['route']);

    $route_object = $route_match->getRouteObject();
    $route_without_facets_query = explode('/{facets_query}', $route_object->getPath())[0];

    $request = \Drupal::request();
    $title = \Drupal::service('title_resolver')
      ->getTitle($request, $route_object);

    $url = Url::fromUserInput($route_without_facets_query);
    $links[] = Link::createFromRoute($this->t('Home'), '<front>');
    $links[] = Link::fromTextAndUrl($title, $url);
    return $breadcrumb->setLinks($links);
  }

}
