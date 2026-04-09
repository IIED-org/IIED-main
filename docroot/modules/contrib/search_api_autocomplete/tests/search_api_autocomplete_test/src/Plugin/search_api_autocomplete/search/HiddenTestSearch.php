<?php

namespace Drupal\search_api_autocomplete_test\Plugin\search_api_autocomplete\search;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\search_api_autocomplete\Attribute\SearchApiAutocompleteSearch;

/**
 * Defines a hidden search plugin.
 */
#[SearchApiAutocompleteSearch(
  id: 'search_api_autocomplete_test_hidden',
  label: new TranslatableMarkup('Hidden search'),
  description: new TranslatableMarkup('Hidden search description'),
  group_label: new TranslatableMarkup('Hidden group'),
  group_description: new TranslatableMarkup('Hidden group description'),
  index: 'autocomplete_search_index',
  no_ui: TRUE,
)]
class HiddenTestSearch extends TestSearch {
}
