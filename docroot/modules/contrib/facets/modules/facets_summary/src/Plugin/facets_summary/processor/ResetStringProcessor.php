<?php

namespace Drupal\facets_summary\Plugin\facets_summary\processor;

use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\facets_summary\FacetsSummaryInterface;
use Drupal\facets_summary\Processor\BuildProcessorInterface;
use Drupal\facets_summary\Processor\ProcessorPluginBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides a processor that adds a link to reset facet filters.
 *
 * @SummaryProcessor(
 *   id = "reset_string",
 *   label = @Translation("Adds reset search string link"),
 *   description = @Translation("When checked, this will add a link to reset the search string."),
 *   stages = {
 *     "build" = 30
 *   }
 * )
 */
class ResetStringProcessor extends ProcessorPluginBase implements BuildProcessorInterface, ContainerFactoryPluginInterface {

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Builds ResetFacetsProcessor object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin_definition for the plugin instance.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RequestStack $request_stack) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('request_stack'));
  }

  /**
   * {@inheritdoc}
   */
  public function build(FacetsSummaryInterface $facets_summary, array $build, array $facets) {
    $request = $this->requestStack->getMasterRequest();
    if (!empty($request->query)) {
      $query_params = $request->query->all();
    }

    $current_string = $request->query->get($facets_summary->getSearchFilterIdentifier());
    if (empty($current_string)) {
      return $build;
    }

    unset($query_params[$facets_summary->getSearchFilterIdentifier()]);

    $url = Url::fromUserInput($facets_summary->getFacetSource()->getPath());
    $url->setOptions(['query' => $query_params]);

    $item = [
      '#theme' => 'facets_result_item__summary',
      '#value' => $current_string,
      '#show_count' => FALSE,
      '#is_active' => TRUE,
    ];
    $item = (new Link($item, $url))->toRenderable();
    $item['#wrapper_attributes'] = [
      'class' => [
        'facet-summary-item--search-string',
      ],
    ];
    if (isset($build['#items'])) {
      array_unshift($build['#items'], $item);
    }
    else {
      $build['#items'] = [
        $item,
      ];
    }

    return $build;
  }

}
