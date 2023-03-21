<?php

namespace Drupal\linkchecker;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Routing\EntityRouteProviderInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Provides routes for linkchecker link.
 */
class LinkCheckerLinkRouteProvider implements EntityRouteProviderInterface {

  /**
   * {@inheritdoc}
   */
  public function getRoutes(EntityTypeInterface $entity_type) {
    $route_collection = new RouteCollection();

    $route = (new Route('/admin/config/content/linkcheckerlink/{linkcheckerlink}/edit'))
      ->setDefault('_entity_form', 'linkcheckerlink.edit')
      ->setRequirement('_entity_access', 'linkcheckerlink.update')
      ->setRequirement('linkcheckerlink', '\d+');
    $route_collection->add('entity.linkcheckerlink.edit_form', $route);

    return $route_collection;
  }

}
