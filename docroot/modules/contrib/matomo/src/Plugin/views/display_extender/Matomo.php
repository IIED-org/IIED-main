<?php

declare(strict_types=1);

namespace Drupal\matomo\Plugin\views\display_extender;

use Drupal\Core\Form\FormStateInterface;
use Drupal\facets\FacetManager\DefaultFacetManager;
use Drupal\search_api\Plugin\views\query\SearchApiQuery;
use Drupal\views\Plugin\views\display_extender\DisplayExtenderPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides interface to manage search data sent to Matomo.
 *
 * @ingroup views_display_extender_plugins
 *
 * @ViewsDisplayExtender(
 *     id = "matomo",
 *     title = @Translation("Matomo"),
 *     help = @Translation("Send data to Matomo when search happens."),
 *     no_ui = FALSE
 * )
 */
class Matomo extends DisplayExtenderPluginBase {

  /**
   * The behavior key when not using the feature.
   */
  public const BEHAVIOR_NONE = 'none';

  /**
   * The behavior key when using the first GET parameter.
   */
  public const BEHAVIOR_FIRST = 'first';

  /**
   * The behavior key when using all GET parameters.
   */
  public const BEHAVIOR_ALL = 'all';

  /**
   * The facets manager service. If present.
   *
   * @var \Drupal\facets\FacetManager\DefaultFacetManager|null
   */
  protected ?DefaultFacetManager $facetsManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->facetsManager = $container->get('facets.manager', ContainerInterface::NULL_ON_INVALID_REFERENCE);
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['enabled'] = ['default' => FALSE];
    $options['keyword_gets'] = ['default' => ''];
    $options['keyword_behavior'] = ['default' => self::BEHAVIOR_FIRST];
    $options['keyword_concat_separator'] = ['default' => ' '];
    $options['category_behavior'] = ['default' => self::BEHAVIOR_NONE];
    $options['category_gets'] = ['default' => ''];
    $options['category_concat_separator'] = ['default' => ' '];
    $options['category_fallback'] = ['default' => ''];
    $options['category_facets'] = ['default' => []];
    $options['category_facets_concat_separator'] = ['default' => ', '];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state): void {
    if ($form_state->get('section') != 'matomo') {
      return;
    }
    $form['#title'] .= $this->t('Matomo');

    $form['enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Send search data to Matomo'),
      '#default_value' => $this->options['enabled'],
    ];

    $form['keyword'] = [
      '#type' => 'details',
      '#title' => $this->t('Keyword'),
      '#open' => TRUE,
    ];

    $form['keyword']['keyword_behavior'] = [
      '#type' => 'radios',
      '#title' => $this->t('Behavior'),
      '#description' => $this->t('Define how multiple GET parameters should be handled.'),
      '#default_value' => $this->options['keyword_behavior'],
      '#options' => [
        self::BEHAVIOR_FIRST => $this->t('Use first non-empty parameter'),
        self::BEHAVIOR_ALL => $this->t('Concatenate all non-empty parameters'),
      ],
    ];

    $form['keyword']['keyword_gets'] = [
      '#type' => 'textfield',
      '#title' => $this->t('GET parameters'),
      '#description' => $this->t('The GET parameters used to detect keywords. Enter a comma-separated list, order matters.'),
      '#default_value' => $this->options['keyword_gets'],
    ];

    $form['keyword']['keyword_concat_separator'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Concatenation separator'),
      '#description' => $this->t('Concatenation separator when using multiple GET parameters or with multivalued GET parameters.'),
      '#default_value' => $this->options['keyword_concat_separator'],
    ];

    $form['category'] = [
      '#type' => 'details',
      '#title' => $this->t('Category'),
      '#open' => TRUE,
    ];

    $form['category']['category_behavior'] = [
      '#type' => 'radios',
      '#title' => $this->t('Behavior'),
      '#description' => $this->t('Define how multiple GET parameters should be handled.'),
      '#default_value' => $this->options['category_behavior'],
      '#options' => [
        self::BEHAVIOR_NONE => $this->t('Do not handle category'),
        self::BEHAVIOR_FIRST => $this->t('Use first non-empty parameter'),
        self::BEHAVIOR_ALL => $this->t('Concatenate all non-empty parameters'),
      ],
    ];

    $form['category']['container'] = [
      '#type' => 'container',
      '#states' => [
        'invisible' => [
          ':input[name="category_behavior"]' => [
            'value' => 'none',
          ],
        ],
      ],
    ];

    $form['category']['container']['category_gets'] = [
      '#type' => 'textfield',
      '#title' => $this->t('GET parameters'),
      '#description' => $this->t('The GET parameters used to detect categories. Enter a comma-separated list, order matters.'),
      '#default_value' => $this->options['category_gets'],
    ];

    // Facets support.
    $query = $this->view->getQuery();
    if ($query instanceof SearchApiQuery && $this->facetsManager != NULL) {
      $facet_source = 'search_api:' . \str_replace(':', '__', $query->getSearchApiQuery()->getSearchId());
      $facets = $this->facetsManager->getFacetsByFacetSourceId($facet_source);
      $facets_options = [];
      $facets_default_value = [];
      foreach ($facets as $facet) {
        $facet_id = $facet->id();
        $facets_options[$facet_id] = $facet->label();
        if (\in_array($facet_id, $this->options['category_facets'], TRUE)) {
          $facets_default_value[] = $facet_id;
        }
      }

      $form['category']['container']['facets'] = [
        '#type' => 'details',
        '#title' => $this->t('Facets'),
        '#open' => TRUE,
      ];

      $form['category']['container']['facets']['category_facets'] = [
        '#type' => 'checkboxes',
        '#title' => $this->t('Facets'),
        '#description' => $this->t('Use these facets as category, the active item is appended after the facet ID. If a facet has multiple active items, its will be concatenated.'),
        '#default_value' => $facets_default_value,
        '#options' => $facets_options,
      ];

      $form['category']['container']['facets']['category_facets_concat_separator'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Concatenation separator for facets'),
        '#description' => $this->t('Concatenation separator when using multiple active elements facets.'),
        '#default_value' => $this->options['category_facets_concat_separator'],
      ];
    }

    $form['category']['container']['category_concat_separator'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Concatenation separator'),
      '#description' => $this->t('Concatenation separator when using multiple GET parameters or with multivalued GET parameters.'),
      '#default_value' => $this->options['category_concat_separator'],
    ];

    $form['category']['container']['category_fallback'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Fallback category'),
      '#description' => $this->t('If no categories are found use this one.'),
      '#default_value' => $this->options['category_fallback'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function submitOptionsForm(&$form, FormStateInterface $form_state): void {
    if ($form_state->get('section') != 'matomo') {
      return;
    }

    /** @var array $form_state_values */
    $form_state_values = $form_state->cleanValues()->getValues();
    foreach ($form_state_values as $option => $value) {
      $this->options[$option] = $value;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function optionsSummary(&$categories, &$options): void {
    $options['matomo'] = [
      'category' => 'other',
      'title' => $this->t('Matomo'),
      'desc' => $this->t('Send data to Matomo when search happens.'),
      'value' => $this->options['enabled'] ? $this->t('Yes') : $this->t('No'),
    ];
  }

}
