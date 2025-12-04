<?php

declare(strict_types=1);

namespace Drupal\views_autocomplete_filters\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\views_autocomplete_filters\Plugin\views\filter\ViewsAutocompleteFiltersCombine;
use Drupal\views_autocomplete_filters\Plugin\views\filter\ViewsAutocompleteFiltersSearchApiFulltext;
use Drupal\views_autocomplete_filters\Plugin\views\filter\ViewsAutocompleteFiltersSearchApiText;
use Drupal\views_autocomplete_filters\Plugin\views\filter\ViewsAutocompleteFiltersString;

/**
 * Views hooks.
 */
class ViewsHooks {

  /**
   * Implements hook_views_plugins_filter_alter().
   */
  #[Hook('views_plugins_filter_alter')]
  public function viewsPluginsFilterAlter(array &$definitions) : void {
    // Extend String handler with autocomplete capabilities.
    if (isset($definitions['string'])) {
      $definitions['string']['class'] = ViewsAutocompleteFiltersString::class;
    }
    // Extend Combine filter handler with autocomplete capabilities.
    if (isset($definitions['combine'])) {
      $definitions['combine']['class'] = ViewsAutocompleteFiltersCombine::class;
    }
    // Extend SearchApiText filter handler with autocomplete capabilities.
    if (isset($definitions['search_api_text'])) {
      $definitions['search_api_text']['class'] = ViewsAutocompleteFiltersSearchApiText::class;
    }
    // Extend SearchApiFulltext filter handler with autocomplete capabilities.
    if (isset($definitions['search_api_fulltext'])) {
      $definitions['search_api_fulltext']['class'] = ViewsAutocompleteFiltersSearchApiFulltext::class;
    }
  }

}
