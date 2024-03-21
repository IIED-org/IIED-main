<?php

namespace Drupal\give\Entity;

use Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider;
use Drupal\Core\Entity\EntityTypeInterface;
use Symfony\Component\Routing\Route;

/**
 * Provides routes for the transaction entity.
 */
class DonationRouteProvider extends DefaultHtmlRouteProvider {

  /**
   * {@inheritdoc}
   */
  public function getRoutes(EntityTypeInterface $entity_type) {
    $route_collection = parent::getRoutes($entity_type);

    // the transaction complete form)
    $route = (new Route('/donation/{give_donation}/complete'))
      ->setDefault('_entity_form', 'give_donation.complete')
      ->setRequirement('_permission', 'administer give');
    $route_collection->add('entity.give_donation.complete_form', $route);

    return $route_collection;
  }

}
