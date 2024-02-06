<?php

namespace Drupal\Tests\facets_pretty_paths\Kernel;

use Drupal\facets\Entity\Facet;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\facets_pretty_paths\Traits\FacetsRequestTrait;

/**
 * Tests the active filter service.
 *
 * @group facets_pretty_paths
 */
class ActiveFiltersTest extends KernelTestBase {

  use FacetsRequestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'facets',
    'facets_pretty_paths',
    'facets_pretty_paths_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installConfig(['system', 'facets_pretty_paths_test']);
  }

  /**
   * Tests the service responsible for determining the active filters.
   */
  public function testDefaultActiveFilters() {
    $facet = $this->container->get('entity_type.manager')->getStorage('facets_facet')->load('content_type');
    // The facet should get imported from the optional test module config as
    // it no longer has dependencies.
    $this->assertInstanceOf(Facet::class, $facet);

    // This doesn't actually exist but it's the one our test facet uses.
    $source_id = 'search_api:views_page__search__page_1';

    $stack = $this->container->get('request_stack');
    // Push a dummy request to the stack.
    $this->pushRequest($stack, 'My search page', 'content_type/article/content_type/page/content_type/dummy');

    /** @var \Drupal\facets_pretty_paths\PrettyPathsActiveFilters $active_filters_service */
    $active_filters_service = $this->container->get('facets_pretty_paths.active_filters');
    $active_filters = $active_filters_service->getActiveFilters($source_id);

    $this->assertEquals([
      'content_type' => [
        'article',
        'page',
        'dummy',
      ],
    ], $active_filters);
  }

  /**
   * Tests that the coder plugins successfully decode the filters.
   */
  public function testEncodedActiveFilters() {
    /** @var \Drupal\facets\FacetInterface $facet */
    $facet = $this->container->get('entity_type.manager')->getStorage('facets_facet')->load('content_type');
    $this->assertInstanceOf(Facet::class, $facet);
    $facet->setThirdPartySetting('facets_pretty_paths', 'coder', 'dummy_coder');
    $facet->save();

    // This doesn't actually exist but it's the one our test facet uses.
    $source_id = 'search_api:views_page__search__page_1';

    $stack = $this->container->get('request_stack');
    // Push a dummy request to the stack.
    $this->pushRequest($stack, 'My search page', 'content_type/dummy-article/content_type/dummy-page/content_type/dummy-dummy');

    /** @var \Drupal\facets_pretty_paths\PrettyPathsActiveFilters $active_filters_service */
    $active_filters_service = $this->container->get('facets_pretty_paths.active_filters');
    $active_filters = $active_filters_service->getActiveFilters($source_id);

    $this->assertEquals([
      'content_type' => [
        'article',
        'page',
        'dummy',
      ],
    ], $active_filters);
  }

}
