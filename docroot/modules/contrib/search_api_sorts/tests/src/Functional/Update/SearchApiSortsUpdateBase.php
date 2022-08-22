<?php

namespace Drupal\Tests\search_api_sorts\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Base class for search_api_sorts update tests.
 */
abstract class SearchApiSortsUpdateBase extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'search_api_sorts',
    'search_api_sorts_test_views',
  ];

  /**
   * A list of entity types that should be installed.
   *
   * @var string[]
   */
  public static $entityTypes = [
    'search_api_index',
    'search_api_server',
    'search_api_task',
    'search_api_sorts_field',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      DRUPAL_ROOT . '/core/modules/system/tests/fixtures/update/drupal-8.8.0.filled.standard.php.gz',
      __DIR__ . '/../../../../../search_api/modules/search_api_db/tests/fixtures/update/search-api-db-base.php',
      __DIR__ . '/../../../fixtures/update/search-api-sorts-db-base.php',
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // We need to manually set our entity types as "installed".
    foreach ($this->getEntityTypesFromClassProperty() as $entity_type_id) {
      $entity_type = $this->container->get('entity_type.manager')->getDefinition($entity_type_id);
      $this->container->get('entity_type.listener')->onEntityTypeCreate($entity_type);
    }
  }

  /**
   * Get the entity types from the "$entityTypes" class property.
   *
   * @return array
   *   An array of entity type IDs.
   */
  protected function getEntityTypesFromClassProperty() {
    $entity_types = [];

    $class = get_class($this);
    while ($class) {
      if (property_exists($class, 'entityTypes')) {
        $entity_types = array_merge($entity_types, $class::$entityTypes);
      }
      $class = get_parent_class($class);
    }

    return array_unique($entity_types);
  }

}
