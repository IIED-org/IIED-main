<?php

namespace Drupal\facets_pretty_paths;

use Drupal\Core\Batch\BatchStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Drupal\facets_pretty_paths\Coder\CoderPluginManager;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Used for determining the Pretty Paths active filters on a given request.
 */
class PrettyPathsActiveFilters {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The Coder plugin manager.
   *
   * @var \Drupal\facets_pretty_paths\Coder\CoderPluginManager
   */
  protected $coderManager;

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The batch storage.
   *
   * @var \Drupal\Core\Batch\BatchStorageInterface
   */
  protected $batchStorage;

  /**
   * Constructs an instance of PrettyPathsActiveFilters.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   The current route match.
   * @param \Drupal\facets_pretty_paths\Coder\CoderPluginManager $coderManager
   *   The Coder plugin manager.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   * @param \Drupal\Core\Batch\BatchStorageInterface $batch_storage
   *   The batch storage.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, RouteMatchInterface $routeMatch, CoderPluginManager $coderManager, RequestStack $requestStack, BatchStorageInterface $batch_storage) {
    $this->entityTypeManager = $entityTypeManager;
    $this->routeMatch = $routeMatch;
    $this->coderManager = $coderManager;
    $this->request = $requestStack->getCurrentRequest();
    $this->batchStorage = $batch_storage;
  }

  /**
   * Returns the active filters for a given Facet source ID.
   *
   * @param string $facet_source_id
   *   The Facet Source ID.
   *
   * @return array
   *   The array of active filters.
   */
  public function getActiveFilters($facet_source_id) {
    // Do heavy lifting only once per facet source id.
    $mapping = &drupal_static('facets_pretty_paths_init', []);

    if ($mapping && isset($mapping[$facet_source_id])) {
      return $mapping[$facet_source_id];
    }

    $mapping[$facet_source_id] = [];

    // Keep a local cache of already initialised coders.
    $initialized_coders = [];

    $filters = $this->getFiltersFromRoute();
    if (!$filters) {
      return $mapping[$facet_source_id];
    }

    $parts = explode('/', $filters);
    if (count($parts) % 2 !== 0) {
      // Our key/value combination should always be even. If uneven, we just
      // assume that the first string is not part of the filters, and remove
      // it. This can occur when an url lives in the same path as our facet
      // source, e.g. /search/overview where /search is the facet source path.
      array_shift($parts);
    }
    foreach ($parts as $index => $part) {
      if ($index % 2 == 0) {
        $url_alias = $part;
      }
      else {
        // The $url_alias comes from the previous (odd) iteration.
        $facet_id = $this->getFacetIdByUrlAlias($url_alias, $facet_source_id);
        if (!$facet_id) {
          // No valid facet url alias specified in url.
          continue;
        }
        // Only initialize facet and their coder once per facet id.
        if (!isset($initialized_coders[$facet_id])) {
          /** @var \Drupal\facets\FacetInterface $facet */
          $facet = $this->entityTypeManager->getStorage('facets_facet')->load($facet_id);
          $coder_id = $facet->getThirdPartySetting('facets_pretty_paths', 'coder', 'default_coder');
          $coder = $this->coderManager->createInstance($coder_id, ['facet' => $facet]);
          $initialized_coders[$facet_id] = $coder;
        }
        if (!isset($mapping[$facet_source_id][$facet_id])) {
          $mapping[$facet_source_id][$facet_id] = [$initialized_coders[$facet_id]->decode($part)];
        }
        else {
          $mapping[$facet_source_id][$facet_id][] = $initialized_coders[$facet_id]->decode($part);
        }
      }
    }

    return $mapping[$facet_source_id];
  }

  /**
   * Gets the facet id from the url alias & facet source id.
   *
   * @param string $url_alias
   *   The url alias.
   * @param string $facet_source_id
   *   The facet source id.
   *
   * @return bool|string
   *   Either the facet id, or FALSE if that can't be loaded.
   */
  protected function getFacetIdByUrlAlias($url_alias, $facet_source_id) {
    $mapping = &drupal_static(__FUNCTION__);
    if (!isset($mapping[$facet_source_id][$url_alias])) {
      $storage = $this->entityTypeManager->getStorage('facets_facet');
      $facet = current($storage->loadByProperties(['url_alias' => $url_alias, 'facet_source_id' => $facet_source_id]));
      if (!$facet) {
        return NULL;
      }
      $mapping[$facet_source_id][$url_alias] = $facet->id();
    }
    return $mapping[$facet_source_id][$url_alias];
  }

  /**
   * Returns the raw string of filters from the current route.
   *
   * @return string|null
   *   The raw filters from the URL.
   */
  protected function getFiltersFromRoute() {
    // Default pretty path routes have their filters defined as route params.
    if ($this->routeMatch->getParameter('facets_query')) {
      return $this->routeMatch->getParameter('facets_query');
    }

    // When current route is views.ajax, retrieve filters from the real url,
    // defined as GET parameter.
    if ($this->routeMatch->getRouteName() === 'views.ajax') {
      $q = $this->request->query->get('q');
      if ($q === NULL && $this->request->isMethod('POST') && !empty($_REQUEST['view_path'])) {
        $q = str_replace($this->request->getSchemeAndHttpHost(), '', $this->request->headers->get('referer'));
      }
      if ($q) {
        $q = preg_replace('/^' . str_replace('/', '\/', base_path()) . '/', '/', $q);
        $route_params = Url::fromUserInput($q)->getRouteParameters();
        if (isset($route_params['facets_query'])) {
          return $route_params['facets_query'];
        }
      }
    }

    // For views data export with batch, retrieve filters from the batch source
    // URL, defined as GET parameter.
    if (strpos($this->routeMatch->getRouteName(), 'system.batch_page') === 0) {
      $batch_id = $this->request->query->get('id');
      $batch_set = $this->batchStorage->load($batch_id);
      if (!empty($batch_set['source_url'])) {
        $route_params = $batch_set['source_url']->getRouteParameters();
        if (isset($route_params['facets_query'])) {
          return $route_params['facets_query'];
        }
      }
    }

    return NULL;
  }

}
