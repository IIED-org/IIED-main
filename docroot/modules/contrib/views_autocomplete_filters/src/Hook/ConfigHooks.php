<?php

namespace Drupal\views_autocomplete_filters\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Views Hooks implementations for Views Autocomplete Filters.
 */
class ConfigHooks {

  /**
   * Implements hook_config_schema_info_alter().
   */
  #[Hook('config_schema_info_alter')]
  public function configSchemaInfoAlter(&$definitions): void {
    // Extend the Views StringFilter and Combine filters with autocomplete.
    foreach ($definitions as $key => $value) {
      // See hook_views_plugins_filter_alter implementation for the list of
      // the fields modified by this module.
      if ($key === 'views.filter.combine'
        || $key === 'views.filter.search_api_fulltext'
        || $key === 'views.filter.search_api_text'
        || $key === 'views.filter.string'
      ) {
        $definitions[$key]['mapping']['expose']['mapping']['autocomplete_filter'] = [
          'type' => 'integer',
          'label' => 'Use Autocomplete',
        ];
        $definitions[$key]['mapping']['expose']['mapping']['autocomplete_min_chars'] = [
          'type' => 'string',
          'label' => 'Minimum number of characters to start filter',
        ];
        $definitions[$key]['mapping']['expose']['mapping']['autocomplete_items'] = [
          'type' => 'string',
          'label' => 'Maximum number of items in Autocomplete',
        ];
        $definitions[$key]['mapping']['expose']['mapping']['autocomplete_field'] = [
          'type' => 'string',
          'label' => 'Field with autocomplete results',
        ];
        $definitions[$key]['mapping']['expose']['mapping']['autocomplete_raw_suggestion'] = [
          'type' => 'integer',
          'label' => 'Unformatted suggestion',
        ];
        $definitions[$key]['mapping']['expose']['mapping']['autocomplete_raw_dropdown'] = [
          'type' => 'integer',
          'label' => 'Unformatted dropdown',
        ];
        $definitions[$key]['mapping']['expose']['mapping']['autocomplete_dependent'] = [
          'type' => 'integer',
          'label' => 'Suggestions depend on other filter fields',
        ];
        $definitions[$key]['mapping']['expose']['mapping']['autocomplete_contextual'] = [
          'type' => 'integer',
          'label' => 'Allow Contextual filters to apply to this filter',
        ];
        $definitions[$key]['mapping']['expose']['mapping']['autocomplete_autosubmit'] = [
          'type' => 'integer',
          'label' => 'Autosubmit Views Exposed Form on autocomplete suggestion selection',
        ];
      }
    }
  }

}
