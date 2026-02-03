<?php

declare(strict_types=1);

namespace Drupal\matomo\HookHandler;

use Drupal\Component\Serialization\Json;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\facets\FacetManager\DefaultFacetManager;
use Drupal\matomo\Component\Render\MatomoJavaScriptSnippet;
use Drupal\matomo\Plugin\views\display_extender\Matomo;
use Drupal\search_api\Plugin\views\query\SearchApiQuery;
use Drupal\views\Plugin\views\cache\CachePluginBase;
use Drupal\views\ViewExecutable;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Hook handler for the hook_views_post_render() hook.
 */
class ViewsPostRenderHookHandler implements ContainerInjectionInterface {

  /**
   * The request stack service.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $currentRequest;

  /**
   * The facets manager service. If present.
   *
   * @var \Drupal\facets\FacetManager\DefaultFacetManager|null
   */
  protected $facetsManager;

  /**
   * Constructor.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack service.
   * @param \Drupal\facets\FacetManager\DefaultFacetManager|null $facetsManager
   *   The facets manager service. If present.
   */
  public function __construct(
    RequestStack $requestStack,
    ?DefaultFacetManager $facetsManager,
  ) {
    $this->currentRequest = $requestStack->getCurrentRequest() ?: new Request();
    $this->facetsManager = $facetsManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new self(
      $container->get('request_stack'),
      $container->get('facets.manager', ContainerInterface::NULL_ON_INVALID_REFERENCE),
    );
  }

  /**
   * Prepare data for Matomo according to configuration.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   The view object being processed.
   * @param array $output
   *   A structured content array representing the view output. The given array
   *   depends on the style plugin and can be either a render array or an array
   *   of render arrays.
   * @param \Drupal\views\Plugin\views\cache\CachePluginBase $cache
   *   The cache settings.
   */
  public function process(ViewExecutable $view, array &$output, CachePluginBase $cache): void {
    $extenders = $view->getDisplay()->getExtenders();
    if (!isset($extenders['matomo'])) {
      // If the ID of the plugin is not in the list then do nothing.
      return;
    }
    /** @var \Drupal\matomo\Plugin\views\display_extender\Matomo $extender */
    $extender = $extenders['matomo'];

    $options = $extender->options;
    if (!$options['enabled']) {
      // If the plugin is not enabled on this view then do nothing.
      return;
    }

    $exposed_input = $view->getExposedInput();
    if (empty($exposed_input)) {
      // No explicit search done.
      return;
    }

    $keyword = $this->getKeyword($options);
    if (empty($keyword)) {
      // Not a keyword search.
      return;
    }

    $total = $view->total_rows ?? \count($view->result);
    $category = $this->getCategory($options, $view);
    if (empty($category)) {
      $category = 'false';
    }

    $script = 'if (typeof _paq !== "undefined") {_paq.push(["trackSiteSearch", ' . $keyword . ', ' . $category . ', ' . $total . ']);}';
    $output['#attached']['html_head'][] = [
      [
        '#tag' => 'script',
        '#value' => new MatomoJavaScriptSnippet($script),
      ],
      'matomo_search_results_' . $view->id() . '__' . $view->getDisplay()->display['id'],
    ];

    // Prevent trackPageView.
    $output['#attached']['html_head'][] = [
      [
        '#tag' => 'script',
        '#value' => 'window.matomo_search_results_active = true;',
        '#weight' => JS_LIBRARY - 1,
      ],
      'matomo_search_results_active',
    ];
  }

  /**
   * The keyword from GET parameters.
   *
   * @param array $options
   *   The extender options.
   *
   * @return string
   *   The JSON encoded keyword.
   */
  protected function getKeyword(array $options): string {
    $keyword = '';

    // @todo sanitize GET parameters?
    $get_parameters = $this->currentRequest->query->all();

    $keywords = [];
    $keyword_gets = \array_map('trim', \explode(',', $options['keyword_gets']));
    foreach ($keyword_gets as $keyword_get) {
      if (!isset($get_parameters[$keyword_get]) || empty($get_parameters[$keyword_get])) {
        continue;
      }

      if (\is_array($get_parameters[$keyword_get])) {
        $keywords[] = \implode($options['keyword_concat_separator'], $get_parameters[$keyword_get]);
      }
      else {
        $keywords[] = $get_parameters[$keyword_get];
      }

      if ($options['category_behavior'] == Matomo::BEHAVIOR_FIRST) {
        break;
      }
    }

    if (!empty($keywords)) {
      $keyword = Json::encode(\implode($options['keyword_concat_separator'], $keywords));
    }

    return $keyword;
  }

  /**
   * The category from GET parameters.
   *
   * @param array $options
   *   The extender options.
   * @param \Drupal\views\ViewExecutable $view
   *   The view object being processed.
   *
   * @return string
   *   The JSON encoded category. Empty if not found or not handled.
   */
  protected function getCategory(array $options, ViewExecutable $view): string {
    $category = '';
    if ($options['category_behavior'] == Matomo::BEHAVIOR_NONE) {
      return $category;
    }

    // @todo sanitize GET parameters?
    $get_parameters = $this->currentRequest->query->all();

    $categories = [];
    $category_gets = \array_map('trim', \explode(',', $options['category_gets']));
    foreach ($category_gets as $category_get) {
      if (!isset($get_parameters[$category_get]) || empty($get_parameters[$category_get])) {
        continue;
      }

      if (\is_array($get_parameters[$category_get])) {
        $categories[] = \implode($options['category_concat_separator'], $get_parameters[$category_get]);
      }
      else {
        $categories[] = $get_parameters[$category_get];
      }

      if ($options['category_behavior'] == Matomo::BEHAVIOR_FIRST) {
        break;
      }
    }

    // Facets support.
    if (!empty($options['category_facets'])
      && ($options['category_behavior'] != Matomo::BEHAVIOR_FIRST) || empty($categories)
    ) {
      $query = $view->getQuery();
      if ($query instanceof SearchApiQuery && $this->facetsManager != NULL) {
        $facet_source = 'search_api:' . \str_replace(':', '__', $query->getSearchApiQuery()->getSearchId());
        $facets = $this->facetsManager->getFacetsByFacetSourceId($facet_source);
        foreach ($facets as $facet) {
          if (!\in_array($facet->id(), $options['category_facets'], TRUE)) {
            continue;
          }

          $facet_values = $facet->getActiveItems();
          if (empty($facet_values)) {
            continue;
          }

          $categories[] = $facet->id() . ': ' . \implode($options['category_facets_concat_separator'], $facet_values);

          if ($options['category_behavior'] == Matomo::BEHAVIOR_FIRST) {
            break;
          }
        }
      }
    }

    if (!empty($categories)) {
      $category = Json::encode(\implode($options['category_concat_separator'], $categories));
    }
    elseif (!empty($options['category_fallback'])) {
      $category = Json::encode($options['category_fallback']);
    }

    return $category;
  }

}
