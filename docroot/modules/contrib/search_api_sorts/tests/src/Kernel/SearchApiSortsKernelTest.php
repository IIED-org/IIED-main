<?php

namespace Drupal\Tests\search_api_sorts\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Kernel test for search_api_sorts module.
 *
 * @group search_api_sorts
 */
class SearchApiSortsKernelTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'search_api',
    'search_api_db',
    'search_api_test_db',
    'search_api_test_views',
    'entity_test',
    'search_api_sorts',
    'search_api_sorts_test_views',
    'search_api_sorts_test_entity',
    'views',
    'user',
    'system',
  ];

  /**
   * Holds all the matching config entities.
   *
   * @var array
   *   An array of all the config entities.
   */
  private $records;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('entity_test_mulrev_changed');
    $this->installEntitySchema('search_api_task');
    $this->installEntitySchema('search_api_sorts_field');

    $this->installConfig([
      'search_api',
      'search_api_test_db',
      'search_api_db',
      'search_api_sorts_test_views',
    ]);

    $service = \Drupal::service('search_api_sorts_test_entity.query.config');
    $entityType = \Drupal::entityTypeManager()->getDefinition('search_api_sorts_field');

    $this->records = $service->get($entityType, 'AND')->getRecords();
  }

  /**
   * Tests that the correct search_api_sorts_field is returned from the query.
   */
  public function testThatSortFieldIsPresent() {
    $this->assertArrayHasKey('views_page---search_api_sorts_test_view__page_1_title',
      $this->records);
  }

  /**
   * Tests that only matching configs are returned from the entity query.
   */
  public function testThatSortFieldIsNotPresent() {
    $this->assertArrayNotHasKey('views_page---nonexistent_search_api_sorts_test_view__page_1_title',
      $this->records);
  }

}
