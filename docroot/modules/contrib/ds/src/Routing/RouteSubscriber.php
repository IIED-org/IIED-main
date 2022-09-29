<?php

namespace Drupal\ds\Routing;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Subscriber for Devel routes.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * The entity manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new RouteSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {
      $base_table = $entity_type->getBaseTable();
      if (($route_name = $entity_type->get('field_ui_base_route')) && !empty($base_table)) {

        if (!$entity_route = $collection->get($route_name)) {
          continue;
        }

        if ($display = $entity_type->getLinkTemplate('display')) {

          $options = [];
          $options['parameters'][$entity_type_id] = [
            'type' => 'entity:' . $entity_type_id,
          ];

          $defaults = [
            'entity_type_id' => $entity_type_id,
          ];

          $route = new Route(
            $display,
            [
              '_controller' => '\Drupal\ds\Controller\DsController::contextualTab',
              '_title' => 'Manage display',
            ] + $defaults,
            ['_permission' => 'administer ' . $entity_type_id . ' display'],
            $options
          );
          $collection->add("entity.$entity_type_id.display", $route);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events = parent::getSubscribedEvents();
    $events[RoutingEvents::ALTER] = ['onAlterRoutes', 100];
    return $events;
  }

}
