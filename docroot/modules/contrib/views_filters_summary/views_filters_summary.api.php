<?php

/**
 * @file
 * Describes hooks provided by the Views Filters Summary module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alter a filter's summary info definition.
 *
 * Allows developers to adjust how a given Views exposed
 * filter summary is displayed.
 *
 * @param array $info
 *   An array of definition properties.
 * @param \Drupal\views\Plugin\views\filter\FilterPluginBase $filter
 *   The view filter plugin instance.
 *
 * @see \Drupal\views_filters_summary\Plugin\views\area\ViewsFiltersSummary::buildFilterDefinition()
 */
function hook_views_filters_summary_info_alter(array &$info, \Drupal\views\Plugin\views\filter\FilterPluginBase $filter) {
  if ($filter->getPluginId() === 'datetime' && $time = strtotime($filter->value['value'])) {
    $info['value'] = [
      [
        'id' => 0,
        'raw' => $filter->value['value'],
        'value' => \Drupal::service('date.formatter')->format(
          $time,
          'custom',
          'F d, Y'
        ),
      ],
    ];
  }
}

/**
 * Alter a filter's summary replacement items.
 *
 * Allows developers to add new replacement items to be displayed in the
 * summary text.
 *
 * @param array $replacements
 *   An array of replacement items.
 * @param \Drupal\views\ViewExecutable $view
 *   The view instance.
 *
 * @see \Drupal\views_filters_summary\Plugin\views\area\ViewsFiltersSummary::defineReplacements()
 */
function hook_views_filters_summary_replacements_alter(&$replacements, $view) {
  foreach ($view->display_handler->handlers["filter"] as $filter) {
    if ($filter->getPluginId() == 'search_api_fulltext') {
      $identifier = $filter->options["expose"]["identifier"];
      $replacements['search_api_fulltext'] =
        \Drupal\Component\Utility\Html::escape($view->exposed_data[$identifier]);
    }
  }
}

/**
 * Allow filter plugins to specify an alias to use for processing.
 *
 * @param \Drupal\views\Plugin\views\filter\FilterPluginBase $filter
 *   The view filter plugin instance.
 *
 * @return string|void
 *   The plugin alias to use to process the filter values.
 */
function views_filters_summary_address_views_filters_summary_plugin_alias($filter) {
  if ($filter->getPluginId() === 'administrative_area') {
    // The administrative_area plugin behaves like the list_field plugin.
    return 'list_field';
  }
}

/**
 * Allow validating an array index value for a specific plugin.
 *
 * @param int|string $index
 *   The array index value to check.
 * @param \Drupal\views\Plugin\views\filter\FilterPluginBase $filter
 *   The view filter plugin instance.
 *
 * @return true|void
 *   TRUE if the index value is valid for that filter plugin.
 */
function views_filters_summary_address_views_filters_summary_valid_index($index, $filter) {
  if ($filter->getPluginId() === 'administrative_area') {
    // Array is similar to: ['AL' => 'AL', 'AK' => 'AK'].
    return TRUE;
  }
}

/**
 * @} End of "addtogroup hooks".
 */
