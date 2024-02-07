<?php

namespace Drupal\Tests\facets_pretty_paths\Functional;

use Drupal\facets\FacetInterface;
use Drupal\Tests\BrowserTestBase;
use Symfony\Component\Routing\Route;

/**
 * Main functional test for the Pretty Paths URL processor.
 *
 * @group facets_pretty_paths
 */
class PrettyPathsFunctionalTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'views',
    'block',
    'facets',
    'search_api',
    'search_api_db',
    'facets_pretty_paths',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->container->get('module_installer')->install(['facets_pretty_paths_test']);
  }

  /**
   * Tests the Facets Pretty Paths URL preprocessor..
   */
  public function testPrettyPathsUrlProcessor() {
    $this->ensureSearchResults();

    /** @var \Drupal\facets\FacetInterface $facet */
    $facet = $this->container->get('entity_type.manager')
      ->getStorage('facets_facet')
      ->load('content_type');
    $urls = $this->buildUrlsFromFacet($facet);

    // We expect 2 URLs to have been generated.
    $this->assertCount(2, $urls);
    foreach ($urls as $url) {
      $this->assertEquals('view.search.page_1', $url->getRouteName());
    }
    $this->assertEquals('content_type/article', $urls[0]->getRouteParameters()['facets_query']);
    $this->assertEquals('content_type/page', $urls[1]->getRouteParameters()['facets_query']);
  }

  /**
   * Tests that the coder plugins get called properly when generating the URLs.
   */
  public function testPrettyPathsCoder() {
    $this->ensureSearchResults();

    /** @var \Drupal\facets\FacetInterface $facet */
    $facet = $this->container->get('entity_type.manager')
      ->getStorage('facets_facet')
      ->load('content_type');
    $facet->setThirdPartySetting('facets_pretty_paths', 'coder', 'dummy_coder');
    $facet->save();

    $urls = $this->buildUrlsFromFacet($facet);
    // We expect 2 URLs to have been generated.
    $this->assertCount(2, $urls);
    foreach ($urls as $url) {
      $this->assertEquals('view.search.page_1', $url->getRouteName());
    }
    $this->assertEquals('content_type/dummy-article', $urls[0]->getRouteParameters()['facets_query']);
    $this->assertEquals('content_type/dummy-page', $urls[1]->getRouteParameters()['facets_query']);
  }

  /**
   * Test that the module correctly alters the source provider route definition.
   */
  public function testRouteSubscriber() {
    // Assert the base line that the Views source provider still has the default
    // route definition.
    $route = $this->container->get('router.route_provider')->getRouteByName('view.search.page_1');
    $this->assertInstanceOf(Route::class, $route);
    $this->assertEquals('/facets-search', $route->getPath());

    // Rebuild everything.
    $this->rebuildAll();
    $route = $this->container->get('router.route_provider')->getRouteByName('view.search.page_1');
    $this->assertEquals('/facets-search/{facets_query}/{f0}/{f1}/{f2}/{f3}/{f4}/{f5}/{f6}/{f7}/{f8}/{f9}/{f10}/{f11}/{f12}/{f13}/{f14}/{f15}/{f16}/{f17}/{f18}/{f19}/{f20}/{f21}/{f22}/{f23}/{f24}/{f25}/{f26}/{f27}/{f28}/{f29}/{f30}/{f31}/{f32}/{f33}/{f34}/{f35}/{f36}/{f37}/{f38}', $route->getPath());
    $this->assertEquals('.*', $route->getRequirement('facets_query'));
    $this->assertEquals('', $route->getDefault('facets_query'));
  }

  /**
   * Given a Facet, build the search results for it.
   *
   * @param \Drupal\facets\FacetInterface $facet
   *   The facet.
   *
   * @return array|\Drupal\Core\Url[]
   *   The URLs.
   */
  protected function buildUrlsFromFacet(FacetInterface $facet) {
    $source = $facet->getFacetSource();

    /** @var \Drupal\facets\UrlProcessor\UrlProcessorPluginManager $manager */
    $manager = $this->container->get('plugin.manager.facets.url_processor');
    /** @var \Drupal\facets_pretty_paths\Plugin\facets\url_processor\FacetsPrettyPathsUrlProcessor $processor */
    $processor = $manager->createInstance('facets_pretty_paths', ['facet' => $facet]);

    $source->fillFacetsWithResults([$facet]);

    /** @var \Drupal\facets\Result\ResultInterface[] $results */
    $results = $processor->buildUrls($facet, $facet->getResults());

    /** @var \Drupal\Core\Url[] $urls */
    $urls = [];

    foreach ($results as $result) {
      $urls[] = $result->getUrl();
    }
    array_filter($urls);

    return $urls;
  }

  /**
   * Ensures that there is test content indexed and ready to be used.
   */
  protected function ensureSearchResults() {
    $this->container->get('entity_type.manager')->getStorage('node')->create([
      'type' => 'article',
      'title' => 'My article title',
    ])->save();

    $this->container->get('entity_type.manager')->getStorage('node')->create([
      'type' => 'page',
      'title' => 'My page title',

    ])->save();

    /** @var \Drupal\search_api\IndexInterface $index */
    $index = $this->container->get('entity_type.manager')
      ->getStorage('search_api_index')
      ->load('node');
    $index->indexItems();

    // Clear the caches for the new route of the Views to be regenerated.
    $this->rebuildAll();
  }

}
