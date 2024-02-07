<?php

namespace Drupal\facets_pretty_paths;

use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Url;
use Drupal\facets\FacetSource\FacetSourcePluginManager;
use Symfony\Component\Routing\RouteCollection;

/**
 * Alter facet source routes, adding a parameter.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * Service plugin.manager.facet_source.
   *
   * @var \Drupal\facets\FacetSource\FacetSourcePluginManager
   */
  protected $facetSourcePluginManager;

  /**
   * Constructs a RouteSubscriber object.
   *
   * @param \Drupal\facets\FacetSource\FacetSourcePluginManager $facetSourcePluginManager
   *   The plugin.manager.facets.facet_source service.
   */
  public function __construct(FacetSourcePluginManager $facetSourcePluginManager) {
    $this->facetSourcePluginManager = $facetSourcePluginManager;
  }

  /**
   * {@inheritdoc}
   */
  public function alterRoutes(RouteCollection $collection) {
    $sources = $this->facetSourcePluginManager->getDefinitions();
    foreach ($sources as $source) {
      $sourcePlugin = $this->facetSourcePluginManager->createInstance($source['id']);
      $path = $sourcePlugin->getPath();

      $storage = \Drupal::entityTypeManager()->getStorage('facets_facet_source');
      $source_id = str_replace(':', '__', $sourcePlugin->getPluginId());
      $facet_source = $storage->load($source_id);
      if (!$facet_source || $facet_source->getUrlProcessorName() != 'facets_pretty_paths') {
        // If no custom configuration is set for the facet source, it is not
        // using pretty_paths. If there is custom configuration, ensure the url
        // processor is pretty paths.
        continue;
      }

      try {
        $url = Url::fromUri('internal:' . $path);
        $sourceRoute = $collection->get($url->getRouteName());

        // Ensure this only triggers once per route.
        // See https://www.drupal.org/project/facets_pretty_paths/issues/2984105
        if ($sourceRoute && strpos($sourceRoute->getPath(), '/{facets_query}') === FALSE) {
          $sourceRoute->setPath($sourceRoute->getPath() . '/{facets_query}');
          $sourceRoute->setDefault('facets_query', '');
          $sourceRoute->setRequirement('facets_query', '.*');

          // Core improperly checks for route parameters that can have a slash
          // in them, only making the route match for parameters that don't
          // have a slash.
          // Workaround that here by adding fake optional parameters to the
          // route, that'll never be filled, and won't get any value set because
          // {facets_query} will already have matched the whole path.
          // Note that until the core bug is resolved, the path maximum length
          // of 255 in the router table induces a limit to the number of facets
          // that can be triggered, which will depend on the facets source path
          // length. For a base path of "/search", 40 placeholders can be added,
          // which means 20 active filter pairs.
          // See https://www.drupal.org/project/drupal/issues/2741939
          $routePath = $sourceRoute->getPath();

          for ($i = 0; strlen($routePath) < 250; $i++) {
            $sourceRoute->setDefault('f' . $i, '');
            $routePath .= "/{f{$i}}";
          }

          $sourceRoute->setPath($routePath);
        }
      }
      catch (\Exception $e) {

      }

    }

  }

}
