<?php

namespace Drupal\media_revisions_ui\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    $route = $collection->get('entity.media.revision');
    if (!$route) {
      return;
    }

    $route->setRequirement('_entity_access', 'media_revision.view revision');
  }

}
