<?php

namespace Drupal\facets_summary\Plugin\facets_summary\processor;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;
use Drupal\facets_summary\FacetsSummaryInterface;
use Drupal\facets_summary\Processor\BuildProcessorInterface;
use Drupal\facets_summary\Processor\ProcessorPluginBase;

/**
 * Provides a processor that includes the current search string.
 *
 * @SummaryProcessor(
 *   id = "show_string",
 *   label = @Translation("Show the current search string"),
 *   description = @Translation("When checked, it will display the text used for the search, if any."),
 *   stages = {
 *     "build" = 20
 *   }
 * )
 */
class ShowStringProcessor extends ProcessorPluginBase implements BuildProcessorInterface {

  /**
   * {@inheritdoc}
   */
  public function build(FacetsSummaryInterface $facets_summary, array $build, array $facets) {
    if (!isset($build['#items'])) {
      return $build;
    }

    $currentString = \Drupal::request()->query->get($facets_summary->getSearchFilterIdentifier());
    if (empty($currentString)) {
      return $build;
    }

    $configuration = $facets_summary->getProcessorConfigs()[$this->getPluginId()];
    $build_string = [
      '#theme' => 'facets_summary_string',
      '#label' => $configuration['settings']['label'],
      '#search_string' => Markup::create(Html::escape($currentString)),
      '#cache' => [
        'contexts' => [
          'url.query_args:' . $facets_summary->getSearchFilterIdentifier(),
        ],
      ],
    ];
    array_unshift($build['#items'], $build_string);
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state, FacetsSummaryInterface $facets_summary) {
    $config = $this->getConfiguration();

    $build['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#default_value' => $config['label'],
    ];

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return ['label' => 'Current text search'];
  }

}
