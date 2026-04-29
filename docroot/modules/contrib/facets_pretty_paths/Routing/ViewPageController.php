<?php

namespace Drupal\facets_pretty_paths\Routing;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Routing\LocalRedirectResponse;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Drupal\facets\Entity\Facet;
use Drupal\facets\FacetInterface;
use Drupal\facets_exposed_filters\Plugin\views\filter\FacetsFilter;
use Drupal\facets_pretty_paths\Coder\CoderPluginManager;
use Drupal\views\Routing\ViewPageController as CoreViewPageController;
use Drupal\views\Views;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * A page controller to execute and render a view with pretty facet filters.
 */
class ViewPageController extends CoreViewPageController implements ContainerInjectionInterface {

  /**
   * The coder plugin manager.
   *
   * @var \Drupal\facets_pretty_paths\Coder\CoderPluginManager
   */
  protected CoderPluginManager $coderPluginManager;

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected Request $currentRequest;

  /**
   * The current view's pretty-paths-enabled facets filters, indexed by URL key.
   *
   * @var \Drupal\facets_exposed_filters\Plugin\views\filter\FacetsFilter[]
   */
  protected array $filters = [];

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected RouteMatchInterface $routeMatch;

  /**
   * The requested view ID.
   *
   * @var string
   */
  protected string $viewId;

  /**
   * Constructs a ViewPageController.
   *
   * @param \Drupal\facets_pretty_paths\Coder\CoderPluginManager $coder_plugin_manager
   *   The coder plugin manager.
   * @param \Symfony\Component\HttpFoundation\Request $current_request
   *   The current request.
   */
  public function __construct(CoderPluginManager $coder_plugin_manager, Request $current_request) {
    $this->coderPluginManager = $coder_plugin_manager;
    $this->currentRequest = $current_request;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.facets_pretty_paths.coder'),
      $container->get('request_stack')->getCurrentRequest()
    );
  }

  /**
   * Handle response for a view containing pretty-paths-enabled facets filters.
   *
   * @param string $view_id
   *   The ID of the view.
   * @param string $display_id
   *   The ID of the display within the view.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   *
   * @return array|\Symfony\Component\HttpFoundation\Response
   *   A render array or a Response object.
   *
   * @see \Drupal\facets_pretty_paths\RouteSubscriber
   */
  public function handle($view_id, $display_id, RouteMatchInterface $route_match): array|Response {
    // Initialize the view.
    if (($view = Views::getView($view_id)) && $view->setDisplay($display_id)) {
      // Store the current route match and requested view ID for use in other
      // methods.
      $this->routeMatch = $route_match;
      $this->viewId = $view_id;

      // Identify and store any pretty facets filters found in this display.
      foreach ($view->getDisplay()->getHandlers('filter') as $filter) {
        if ($filter instanceof FacetsFilter && !empty($filter->options['expose']['facets_pretty_paths_coder'])) {
          // Key by the exposed filter identifier, which is how the facet is
          // represented in the URL, so downstream lookups are simpler.
          $this->filters[$filter->options['expose']['identifier']] = $filter;
        }
      }

      // Do any of this display's pretty facet filters currently exist as URL
      // query parameters?
      if (count(array_intersect_key($this->filters, $this->currentRequest->query->all()))) {
        // This is a views exposed form submission.
        // Redirect to the pretty path.
        return $this->getPrettyPathRedirectResponse();
      }
      else {
        // This is a pretty path request.
        // Populate the current request's query parameters from the
        // facets_query route parameter so pretty facet filters are aware
        // of current selections.
        $this->populateCurrentRequestQuery();
      }
    }

    // Let the core view page controller do the rest.
    return parent::handle($view_id, $display_id, $route_match);
  }

  /**
   * Get a LocalRedirectResponse to the pretty path for the current VEF request.
   *
   * Only useful when the current request is a views exposed form submission
   * with filter selections present as URL query parameters.
   *
   * @return \Drupal\Core\Routing\LocalRedirectResponse
   *   The LocalRedirectResponse to the pretty path for the current VEF request.
   */
  protected function getPrettyPathRedirectResponse(): LocalRedirectResponse {
    // Begin building the new URL using the current path.
    $url = Url::fromRouteMatch($this->routeMatch);

    // Extract all non-empty query parameters from the current request.
    $url_query = array_filter($this->currentRequest->query->all(), function ($value) {
      // A raw value of "" or "All" indicates an empty filter / no value.
      return $value !== '' && $value !== 'All';
    });

    // Transition any pretty filter values into facets_query route parameter
    // parts.
    $facets_query_parts = [];
    foreach ($this->filters as $filter_key => $filter) {
      if (isset($url_query[$filter_key])) {
        $coder_id = $filter->options['expose']['facets_pretty_paths_coder'];
        $coder = $this
          ->coderPluginManager
          ->createInstance($coder_id, ['facet' => $this->getFilterFacet($filter)]);
        $raw_values = (array) $url_query[$filter_key];
        foreach ($raw_values as $raw_value) {
          $facets_query_parts[] = $filter_key . '/' . $coder->encode($raw_value);
        }
        unset($url_query[$filter_key]);
      }
    }

    // Add the facets_query route parameter and remaining query parameters.
    $url->setRouteParameter('facets_query', implode('/', $facets_query_parts));
    $url->setOption('query', $url_query);

    $redirect_url = $url->setAbsolute()->toString(TRUE);
    $redirect_url_string = $redirect_url->getGeneratedUrl();
    // Set up a redirect response to this new URL.
    $response = new LocalRedirectResponse($redirect_url_string);

    // Add cacheability metadata to the response.
    $cache_metadata = new CacheableMetadata();
    // The response we generate here depends on the configuration of the
    // requested view and the requested URL.
    $cache_metadata->addCacheTags(['config:views.view.' . $this->viewId]);
    $cache_metadata->addCacheContexts(['url']);
    $response->addCacheableDependency($cache_metadata);
    $response->addCacheableDependency($redirect_url);

    // Return the redirect response.
    return $response;
  }

  /**
   * Populate the current request's query parameters from facets_query values.
   *
   * Translates any values found in the current request's facets_query
   * route parameter to the URL query parameters expected by the associated
   * views facets exposed filters.
   */
  protected function populateCurrentRequestQuery(): void {
    if ($this->routeMatch->getParameter('facets_query')) {
      $query_params = [];
      $facets_query_parts = explode('/', $this->routeMatch->getParameter('facets_query'));
      if (count($facets_query_parts) % 2 !== 0) {
        // Our key/value combination should always be even. If uneven, we just
        // assume that the first string is not part of the filters, and remove
        // it. This can occur when a URL lives in the same path as this view,
        // e.g. /search/overview where /search is the view path.
        array_shift($facets_query_parts);
      }
      // Loop through each filter-key/encoded-value pair.
      $coders = [];
      for ($i = 0; $i < count($facets_query_parts); $i += 2) {
        $filter_key = $facets_query_parts[$i];
        if (isset($this->filters[$filter_key])) {
          $encoded_value = $facets_query_parts[$i + 1];
          $filter = $this->filters[$filter_key];
          // Avoid repeat-loading of the same coder for multi-value.
          if (!isset($coders[$filter_key])) {
            $coder_id = $filter->options['expose']['facets_pretty_paths_coder'];
            $coders[$filter_key] = $this
              ->coderPluginManager
              ->createInstance($coder_id, ['facet' => $this->getFilterFacet($filter)]);
          }
          $decoded_value = $coders[$filter_key]->decode($encoded_value);
          if ($filter->options['expose']['multiple']) {
            $query_params[$filter_key][$decoded_value] = $decoded_value;
          }
          else {
            $query_params[$filter_key] = $decoded_value;
          }
        }
      }
      foreach ($query_params as $filter_key => $value) {
        $this->currentRequest->query->set($filter_key, $value);
      }
    }
  }

  /**
   * Helper function to retrieve the representing facet for a filter handler.
   *
   * Code adapted from FacetsFilter::getFacet().
   *
   * Could be replaced by FacetsFilter::getFacet() if that method became
   * became public.
   *
   * @param \Drupal\facets_exposed_filters\Plugin\views\filter\FacetsFilter $filter
   *   The views facets filter handler for which to get the representing facet.
   *
   * @return \Drupal\facets\FacetInterface
   *   The facet entity representing this views facets filter handler.
   *
   * @see \Drupal\facets_exposed_filters\Plugin\views\filter\FacetsFilter::getFacet()
   */
  protected function getFilterFacet(FacetsFilter $filter): FacetInterface {
    $facet = Facet::create([
      'id' => $filter->options["field"],
      'field_identifier' => $filter->getConfiguration()["search_api_field_identifier"],
      'facet_source_id' => 'search_api:views_' . $filter->displayHandler->getPluginId() . '__' . $filter->view->id() . '__' . $filter->view->current_display,
      'query_operator' => $filter->options["facet"]["query_operator"] ?? 'or',
      'use_hierarchy' => isset($filter->options["facet"]["processor_configs"]["hierarchy_processor"]),
      'expand_hierarchy' => $filter->options["facet"]["expand_hierarchy"] ?? FALSE,
      'min_count' => $filter->options["facet"]["min_count"] ?? 1,
      'widget' => '<nowidget>',
      'facet_type' => 'facets_exposed_filter',
    ]);
    if ($facet->getUseHierarchy()) {
      $facet->setHierarchy($filter->options["facet"]["hierarchy"], []);
    }
    if (isset($filter->options["facet"]["processor_configs"])) {
      foreach ($filter->options["facet"]["processor_configs"] as $processor_id => $processor_settings) {
        $facet->addProcessor([
          'processor_id' => $processor_id,
          'settings' => $processor_settings["settings"] ?? [],
          'weights' => $processor_settings["weights"] ?? [],
        ]);
      }
    }
    return $facet;
  }

}
