<?php

namespace Drupal\facets_pretty_paths;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Url;
use Drupal\facets\FacetSource\FacetSourcePluginManager;
use Drupal\facets_exposed_filters\Plugin\views\filter\FacetsFilter;
use Drupal\views\Views;
use Symfony\Component\Routing\RouteCollection;

/**
 * Alter facet source routes.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * Service plugin.manager.facet_source.
   *
   * @var \Drupal\facets\FacetSource\FacetSourcePluginManager
   */
  protected $facetSourcePluginManager;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a RouteSubscriber object.
   *
   * @param \Drupal\facets\FacetSource\FacetSourcePluginManager $facetSourcePluginManager
   *   The plugin.manager.facets.facet_source service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(FacetSourcePluginManager $facetSourcePluginManager, ModuleHandlerInterface $module_handler) {
    $this->facetSourcePluginManager = $facetSourcePluginManager;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public function alterRoutes(RouteCollection $collection) {
    $urls = [];
    $pretty_facets_exposed_filters_views = [];

    if ($this->moduleHandler->moduleExists('facets_exposed_filters')) {
      // Extract URLs from views that contain facets exposed filters with
      // enabled pretty path coder settings.
      foreach (Views::getApplicableViews('uses_route') as $data) {
        [$view_id, $display_id] = $data;
        $view = Views::getView($view_id);
        $view->setDisplay($display_id);
        foreach ($view->getDisplay()->getHandlers('filter') as $handler) {
          if ($handler instanceof FacetsFilter && !empty($handler->options['expose']['facets_pretty_paths_coder'])) {
            $urls[] = $view->getUrl();
            $pretty_facets_exposed_filters_views[] = $view_id;
            continue;
          }
        }
      }
    }

    // Extract URLs from facet source entities that use the pretty paths URL
    // processor.
    foreach ($this->facetSourcePluginManager->getDefinitions() as $source) {
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
      $urls[] = Url::fromUri('internal:' . $path);
    }

    // Set up routing.
    foreach ($urls as $url) {
      try {
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

          // Use our controller for views with pretty facets exposed filters.
          if (in_array($sourceRoute->getDefault('view_id'), $pretty_facets_exposed_filters_views)) {
            $sourceRoute->setDefault('_controller', 'Drupal\\facets_pretty_paths\\Routing\\ViewPageController::handle');
          }
        }
      }
      catch (\Exception $e) {

      }
    }

  }

}
