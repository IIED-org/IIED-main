<?php

namespace Drupal\facets_pretty_paths\Plugin\facets\url_processor;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Drupal\facets\Entity\Facet;
use Drupal\facets\Event\UrlCreated;
use Drupal\facets\FacetInterface;
use Drupal\facets\UrlProcessor\UrlProcessorPluginBase;
use Drupal\facets_pretty_paths\PrettyPathsActiveFilters;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Pretty paths URL processor.
 *
 * @FacetsUrlProcessor(
 *   id = "facets_pretty_paths",
 *   label = @Translation("Pretty paths"),
 *   description = @Translation("Pretty paths uses slashes as separator, e.g. /brand/drupal/color/blue"),
 * )
 */
class FacetsPrettyPathsUrlProcessor extends UrlProcessorPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The current_route_match service.
   *
   * @var \Drupal\Core\Routing\ResettableStackedRouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The service responsible for determining the active filters.
   *
   * @var \Drupal\facets_pretty_paths\PrettyPathsActiveFilters
   */
  protected $activeFiltersService;

  /**
   * Records active children/parents to deselect with "use hierarchy" option.
   *
   * @var array
   */
  protected $activeHierarchy = [];

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Constructs FacetsPrettyPathsUrlProcessor object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   A request object for the current request.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   The route match service.
   * @param \Drupal\facets_pretty_paths\PrettyPathsActiveFilters $activeFilters
   *   The active filters service.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher
   *   The event dispatcher.
   *
   * @throws \Drupal\facets\Exception\InvalidProcessorException
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Request $request, EntityTypeManagerInterface $entity_type_manager, RouteMatchInterface $routeMatch, PrettyPathsActiveFilters $activeFilters, EventDispatcherInterface $eventDispatcher) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $request, $entity_type_manager);
    $this->routeMatch = $routeMatch;
    $this->activeFiltersService = $activeFilters;
    $this->initializeActiveFilters();
    $this->eventDispatcher = $eventDispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      version_compare(\Drupal::VERSION, '9.3', '>=') ? $container->get('request_stack')->getMainRequest() : $container->get('request_stack')->getMasterRequest(),
      $container->get('entity_type.manager'),
      $container->get('current_route_match'),
      $container->get('facets_pretty_paths.active_filters'),
      $container->get('event_dispatcher')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildUrls(FacetInterface $facet, array $results) {

    // No results are found for this facet, so don't try to create urls.
    if (empty($results)) {
      return [];
    }

    $initialized_coders = [];
    $initialized_facets = [];
    $filters = $this->getActiveFilters();
    $coder_plugin_manager = \Drupal::service('plugin.manager.facets_pretty_paths.coder');
    $coder_id = $facet->getThirdPartySetting('facets_pretty_paths', 'coder', 'default_coder');
    $coder = $coder_plugin_manager->createInstance($coder_id, ['facet' => $facet]);
    $facet_source_url = Url::fromUri('internal:' . $facet->getFacetSource()->getPath());

    /** @var \Drupal\facets\Result\ResultInterface $result */
    foreach ($results as &$result) {
      $raw_value = $result->getRawValue();

      $filters_current_result = $filters;
      // If the value is active, remove the filter string from the parameters.
      if ($result->isActive()) {
        if (($key = array_search($raw_value, $filters_current_result[$result->getFacet()->id()])) !== FALSE) {
          unset($filters_current_result[$result->getFacet()->id()][$key]);
        }
        if ($result->getFacet()->getEnableParentWhenChildGetsDisabled() && $result->getFacet()->getUseHierarchy()) {
          // Enable parent id again if exists.
          $parent_ids = $result->getFacet()->getHierarchyInstance()->getParentIds($raw_value);
          if (isset($parent_ids[0]) && $parent_ids[0]) {
            $filters_current_result[$result->getFacet()->id()][] = $coder->encode($parent_ids[0]);
          }
        }
      }
      // If the value is not active, add the filter string.
      else {
        // Exclude others results if we are in the show_only_one_result mode.
        if ($result->getFacet()->getShowOnlyOneResult()) {
          $filters_current_result[$result->getFacet()->id()] = [0 => $raw_value];
        }
        else {
          $filters_current_result[$result->getFacet()->id()][] = $raw_value;
        }

        if ($result->getFacet()->getUseHierarchy()) {

          foreach (($this->activeFilters[$result->getFacet()->id()] ?? []) as $activeFilter) {
            // Cache hierarchical queries at request level:
            if (!array_key_exists($activeFilter, $this->activeHierarchy)) {
              $parents = $result->getFacet()->getHierarchyInstance()->getParentIds($activeFilter);
              $childs = $result->getFacet()->getHierarchyInstance()->getChildIds([$activeFilter]);
              $childs = $childs[$activeFilter] ?? [];
              $this->activeHierarchy[$activeFilter] = array_merge($parents, $childs);
            }
            if (in_array($raw_value, $this->activeHierarchy[$activeFilter])) {
              $key = array_search($activeFilter, $filters_current_result[$result->getFacet()->id()]);
              unset($filters_current_result[$result->getFacet()->id()][$key]);
            }
          }
        }
      }

      // Now we start transforming $filters_current_result array into a string
      // which we append later to the current path.
      $pretty_paths_presort_data = [];
      foreach ($filters_current_result as $facet_id => $active_values) {
        foreach ($active_values as $active_value) {
          // Ensure we only load every facet and coder once.
          if (!isset($initialized_facets[$facet_id])) {
            $facet = Facet::load($facet_id);
            $initialized_facets[$facet_id] = $facet;
            $coder_id = $facet->getThirdPartySetting('facets_pretty_paths', 'coder', 'default_coder');
            $coder = $coder_plugin_manager->createInstance($coder_id, ['facet' => $facet]);
            $initialized_coders[$facet_id] = $coder;
          }
          $encoded_value = $initialized_coders[$facet_id]->encode($active_value);
          $pretty_paths_presort_data[] = [
            'weight' => $initialized_facets[$facet_id]->getWeight(),
            'name' => $initialized_facets[$facet_id]->getName(),
            'pretty_path_alias' => "/" . $initialized_facets[$facet_id]->getUrlAlias() . "/" . $encoded_value,
          ];
        }
      }
      $pretty_paths_presort_data = $this->sortByWeightAndName($pretty_paths_presort_data);
      $pretty_paths_string = implode('', array_column($pretty_paths_presort_data, 'pretty_path_alias'));
      // Remove the leading slash.
      $pretty_paths_string = ltrim($pretty_paths_string, '/');

      // Reset the URL for each result.
      $url = clone $facet_source_url;
      // Set the facets_query to the updated facets.
      $url->setRouteParameter('facets_query', trim($pretty_paths_string, '/'));
      $url->setRouteParameter('facets_query', $pretty_paths_string);
      $url->setOption('attributes', ['rel' => 'nofollow']);

      // First get the current list of get parameters.
      $get_params = $this->request->query;

      // When adding/removing a filter the number of pages may have changed,
      // possibly resulting in an invalid page parameter.
      if ($get_params->has('page')) {
        $current_page = $get_params->get('page');
        $get_params->remove('page');
      }
      $url->setOption('query', $get_params->all());

      // Allow other modules to alter the result url built.
      $event = new UrlCreated($url, $result, $facet);
      $this->eventDispatcher->dispatch($event);

      $result->setUrl($event->getUrl());
      // Restore page parameter again. See https://www.drupal.org/node/2726455.
      if (isset($current_page)) {
        $get_params->set('page', $current_page);
      }
    }

    return $results;
  }

  /**
   * Sorts an array with weight and name values.
   *
   * It sorts first by weight, then by the alias of the facet item value.
   *
   * @param array $pretty_paths
   *   The values to sort.
   *
   * @return array
   *   The sorted values.
   */
  public function sortByWeightAndName(array $pretty_paths) {
    array_multisort(array_column($pretty_paths, 'weight'), SORT_ASC,
      array_column($pretty_paths, 'name'), SORT_ASC,
      array_column($pretty_paths, 'pretty_path_alias'), SORT_ASC, $pretty_paths);

    return $pretty_paths;
  }

  /**
   * Initializes the active filters from the url.
   *
   * Get all the filters that are active by checking the request url and store
   * them in activeFilters which is an array where key is the facet id and value
   * is an array of raw values.
   */
  protected function initializeActiveFilters() {
    $facet_source_id = $this->configuration['facet']->getFacetSourceId();
    $this->activeFilters = $this->activeFiltersService->getActiveFilters($facet_source_id);
  }

}
