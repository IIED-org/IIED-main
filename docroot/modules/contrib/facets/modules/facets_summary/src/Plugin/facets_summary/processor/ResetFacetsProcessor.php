<?php

namespace Drupal\facets_summary\Plugin\facets_summary\processor;

use Drupal\Core\Form\FormStateInterface;
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
 *   id = "reset_facets",
 *   label = @Translation("Adds reset facets link"),
 *   description = @Translation("When checked, this facet will add a link to reset enabled facets."),
 *   stages = {
 *     "build" = 30
 *   }
 * )
 */
class ResetFacetsProcessor extends ProcessorPluginBase implements BuildProcessorInterface, ContainerFactoryPluginInterface {

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
    $configuration = $facets_summary->getProcessorConfigs()[$this->getPluginId()];
    $hasReset = FALSE;

    // Do nothing if there are no selected facets.
    if (empty($build['#items'])) {
      return $build;
    }

    $request_stack = \Drupal::requestStack();
    // Support 9.3+.
    // @todo remove switch after 9.3 or greater is required.
    $request = version_compare(\Drupal::VERSION, '9.3', '>=') ? $request_stack->getMainRequest() : $request_stack->getMasterRequest();
    $query_params = $request->query->all();

    // Bypass all active facets and remove them from the query parameters array.
    foreach ($facets as $facet) {
      $url_alias = $facet->getUrlAlias();
      $filter_key = $facet->getFacetSourceConfig()->getFilterKey() ?: 'f';

      if ($facet->getActiveItems()) {
        // This removes query params when using the query url processor.
        if (isset($query_params[$filter_key])) {
          foreach ($query_params[$filter_key] as $delta => $param) {
            if (strpos($param, $url_alias . ':') !== FALSE) {
              unset($query_params[$filter_key][$delta]);
            }
          }

          if (!$query_params[$filter_key]) {
            unset($query_params[$filter_key]);
          }
        }

        $hasReset = TRUE;
      }
    }

    if (!$hasReset) {
      return $build;
    }

    $url = Url::fromUserInput($facets_summary->getFacetSource()->getPath());
    $url->setOptions(['query' => $query_params]);
    // Check if reset link text is not set or it contains only whitespaces.
    // Set text from settings or set default text.
    if (empty($configuration['settings']['link_text']) || strlen(trim($configuration['settings']['link_text'])) === 0) {
      $itemText = $this->t('Reset');
    }
    else {
      $itemText = $configuration['settings']['link_text'];
    }
    $item = (new Link($itemText, $url))->toRenderable();
    $item['#wrapper_attributes'] = [
      'class' => [
        'facet-summary-item--clear',
      ],
    ];
    array_unshift($build['#items'], $item);
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state, FacetsSummaryInterface $facets_summary) {
    // By default, there should be no config form.
    $config = $this->getConfiguration();

    $build['link_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Reset facets link text'),
      '#default_value' => $config['link_text'],
    ];
    $build['clear_string'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Clear the current search string'),
      '#default_value' => $config['clear_string'],
      '#description' => $this->t('If checked, the reset link will also clear the text used for the search.'),
      '#states' => [
        'visible' => [
          // @todo get the processor id (show_string) dynamically
          ':input[name="facets_summary_settings[show_string][status]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'link_text' => '',
      'clear_string' => FALSE,
    ];
  }

}
