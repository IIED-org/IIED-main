<?php

namespace Drupal\search_api_sorts_test_entity\Entity;

use Drupal\Core\Config\Entity\Query\QueryFactory;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Defines a query factory for the search_api_sorts_test_entity entity type.
 *
 * @package Drupal\search_api_sorts_test_entity\Entity
 */
class SearchApiSortsTestEntityQueryFactory extends QueryFactory {

  /**
   * {@inheritdoc}
   */
  public function get(EntityTypeInterface $entity_type, $conjunction) {
    return new SearchApiSortsTestEntityQuery($entity_type, $conjunction, $this->configFactory, $this->keyValueFactory, $this->namespaces);
  }

}
