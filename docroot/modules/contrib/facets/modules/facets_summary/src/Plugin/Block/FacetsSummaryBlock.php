<?php

namespace Drupal\facets_summary\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\UncacheableDependencyTrait;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\Component\Utility\Html;
use Drupal\facets_summary\Entity\FacetsSummary;
use Drupal\facets_summary\FacetsSummaryBlockInterface;
use Drupal\facets_summary\FacetsSummaryManager\DefaultFacetsSummaryManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Exposes a summary based on all the facets as a block.
 *
 * @Block(
 *   id = "facets_summary_block",
 *   deriver = "Drupal\facets_summary\Plugin\Block\FacetsSummaryBlockDeriver"
 * )
 */
class FacetsSummaryBlock extends BlockBase implements FacetsSummaryBlockInterface, ContainerFactoryPluginInterface {

  use UncacheableDependencyTrait;

  /**
   * The facet manager service.
   *
   * @var \Drupal\facets_summary\FacetsSummaryManager\DefaultFacetsSummaryManager
   */
  protected $facetsSummaryManager;

  /**
   * The associated facets_source_summary entity.
   *
   * @var \Drupal\facets_summary\FacetsSummaryInterface
   */
  protected $facetsSummary;

  /**
   * Constructs a source summary block.
   *
   * @param array $configuration
   *   The configuration of the Facets Summary Block.
   * @param string $plugin_id
   *   The block plugin block identifier.
   * @param array $plugin_definition
   *   The block plugin block definition.
   * @param \Drupal\facets_summary\FacetsSummaryManager\DefaultFacetsSummaryManager $facets_summary_manager
   *   The facet manager service.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, DefaultFacetsSummaryManager $facets_summary_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->facetsSummaryManager = $facets_summary_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('facets_summary.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getEntity() {
    if (!isset($this->facetsSummary)) {
      $source_id = $this->getDerivativeId();
      if (!$this->facetsSummary = FacetsSummary::load($source_id)) {
        $this->facetsSummary = FacetsSummary::create(['id' => $source_id]);
        $this->facetsSummary->save();
      }
    }
    return $this->facetsSummary;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Do not build the facet summary if the block is being previewed.
    if ($this->getContextValue('in_preview')) {
      return [];
    }

    /** @var \Drupal\facets_summary\FacetsSummaryInterface $summary */
    $facets_summary = $this->getEntity();

    // Let the facet_manager build the facets.
    $build = [];

    // Let the facet_manager build the facets.
    $summary_build = $this->facetsSummaryManager->build($facets_summary);

    if ($summary_build) {
      $build = [
        'facets_summary' => [
          '#type' => 'container',
          '#contextual_links' => [
            'facets_summary' => [
              'route_parameters' => ['facets_summary' => $facets_summary->id()],
            ],
          ],
          '#attributes' => [
            'data-drupal-facets-summary-id' => $facets_summary->id(),
            'data-drupal-facets-summary-plugin-id' => $this->getPluginId(),
            'id' => Html::getUniqueId(str_replace(':', '-', $this->getPluginId())),
            'class' => [
              'facets-summary-block__wrapper',
            ],
          ],
          'summary_build' => $summary_build,
        ],
      ];

      // Hidden empty result.
      if (!isset($summary_build['#items']) && !isset($summary_build['#message'])) {
        $build['facets_summary']['#attributes']['class'][] = 'hidden';
      }

      /** @var \Drupal\views\ViewExecutable $view */
      if ($view = $facets_summary->getFacetSource()->getViewsDisplay()) {
        $build['#attached']['drupalSettings']['facets_views_ajax']['facets_summary_ajax_' . $facets_summary->id()] = [
          'facets_summary_id' => $facets_summary->id(),
          'view_id' => $view->id(),
          'current_display_id' => $view->current_display,
          'ajax_path' => Url::fromRoute('views.ajax')->toString(),
        ];
      }
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $source_id = $this->getDerivativeId();
    if ($summary = FacetsSummary::load($source_id)) {
      return [$summary->getConfigDependencyKey() => [$summary->getConfigDependencyName()]];
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getPreviewFallbackString() {
    return $this->t('Placeholder for the "@facet_summary" facet summary', ['@facet_summary' => $this->getDerivativeId()]);
  }

}
