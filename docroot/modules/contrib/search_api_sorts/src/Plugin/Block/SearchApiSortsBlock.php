<?php

namespace Drupal\search_api_sorts\Plugin\Block;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;
use Drupal\search_api_sorts\ConfigIdEscapeTrait;

/**
 * Exposes a search api sorts rendered as a block.
 *
 * @Block(
 *   id = "search_api_sorts_block",
 *   deriver = "Drupal\search_api_sorts\Plugin\Block\SearchApiSortsBlockDeriver"
 * )
 */
class SearchApiSortsBlock extends BlockBase {
  use ConfigIdEscapeTrait;

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    /** @var \Drupal\search_api\Display\DisplayInterface $search_api_display */
    $search_api_display = $this->pluginDefinition['search_api_display'];

    if (!$search_api_display->isRenderedInCurrentRequest()) {
      // Display is not rendered in current request, hide block.
      return [];
    }

    /** @var \Drupal\search_api_sorts\SearchApiSortsManagerInterface $search_api_sorts_manager */
    $search_api_sorts_manager = \Drupal::service('search_api_sorts.manager');

    $enabled_sorts = $search_api_sorts_manager->getEnabledSorts($search_api_display);
    if (!$enabled_sorts) {
      // No fields are enabled for sorting, hide block.
      return [];
    }

    $active_sort = $search_api_sorts_manager->getActiveSort($search_api_display);
    $current_sort_field = $active_sort->getFieldName();
    $current_sort_order = $active_sort->getOrder();
    if ($active_sort->getFieldName() == NULL) {
      $default_sort = $search_api_sorts_manager->getDefaultSort($search_api_display);
      $current_sort_field = $default_sort->getFieldName();
      $current_sort_order = $default_sort->getOrder();
    }

    // Helper array to sort enabled sorts by weight.
    $sorts = [];
    foreach ($enabled_sorts as $enabled_sort) {
      $sorts[$enabled_sort->get('field_identifier')] = [
        'label' => $enabled_sort->get('label'),
        'weight' => $enabled_sort->get('weight'),
        'default_order' => $enabled_sort->get('default_order'),
      ];
    }

    uasort($sorts, [
      'Drupal\Component\Utility\SortArray',
      'sortByWeightElement',
    ]);

    // TODO: fetch path by configuration (data source?) instead of current path.
    $url = \Drupal::request()->getRequestUri();
    $base_path = \Drupal::request()->getBasePath();
    $url = str_replace($base_path, '', $url);
    $url_array = UrlHelper::parse($url);

    $items = [];
    foreach ($sorts as $sort_field => $sort) {
      $order = $sort['default_order'];
      if ($sort_field == $current_sort_field) {
        $order = ($current_sort_order == 'desc') ? 'asc' : 'desc';
      }

      $url_array['query']['sort'] = $sort_field;
      $url_array['query']['order'] = $order;

      $active = $sort_field == $current_sort_field;
      $order_indicator = '';
      if ($active) {
        $order_indicator = [
          '#theme' => 'tablesort_indicator',
          '#style' => $order,
        ];
      }

      $items[] = [
        '#theme' => 'search_api_sorts_sort',
        '#label' => $sort['label'],
        '#url' => Url::fromUserInput($url_array['path'], [
          'query' => $url_array['query'],
          'fragment' => $url_array['fragment'],
        ])->toString(),
        '#active' => $active,
        '#order' => $order,
        '#order_indicator' => $order_indicator,
        '#sort_field' => $sort_field,
      ];
    }

    $build['links'] = [
      '#theme' => 'item_list__search_api_sorts',
      '#items' => $items,
      '#attributes' => [
        'class' => ['search-api-sorts', 'search-api-sorts--' . Html::getClass($search_api_display->getPluginId())],
      ],
    ];

    $build['#contextual_links']['search_api_sorts'] = [
      'route_parameters' => [
        'search_api_index' => $search_api_display->getIndex()->id(),
        'search_api_display' => $this->getEscapedConfigId($search_api_display->getPluginId()),
      ],
    ];

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    // A search api sorts block cannot be cached, because it must always match
    // the current search results, and Search API gets those search results from
    // a data source that can be external to Drupal. Therefore it is impossible
    // to guarantee that the search results are in sync with the data managed by
    // Drupal. Consequently, it is not possible to cache the search results at
    // all. If the search results cannot be cached, then neither can the search
    // api sorts, because they must always match.
    // Fortunately, search api sorts blocks are rendered using a lazy builder
    // (like all blocks in Drupal), which means their rendering can be deferred
    // (unlike the search results, which are the main content of the page, and
    // deferring their rendering would mean sending an empty page to the user).
    // This means that search api sorts blocks can be rendered and sent *after*
    // the initial page was loaded, by installing the BigPipe (big_pipe) module.
    //
    // When BigPipe is enabled, the search results will appear first, and then
    // each search api sorts block will appear one-by-one, in DOM order.
    // See https://www.drupal.org/project/big_pipe.
    //
    // In a future version of search api sorts API, this could be refined, but
    // due to the reliance on external data sources, it will be very difficult
    // if not impossible to improve this significantly.
    return 0;
  }

}
