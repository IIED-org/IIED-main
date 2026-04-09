<?php

namespace Drupal\search_api_autocomplete_test\Plugin\search_api_autocomplete\suggester;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\search_api_autocomplete\Attribute\SearchApiAutocompleteSuggester;

/**
 * Defines a hidden suggester plugin.
 */
#[SearchApiAutocompleteSuggester(
  id: 'search_api_autocomplete_test_hidden',
  label: new TranslatableMarkup('Hidden suggester'),
  description: new TranslatableMarkup('Hidden suggester description'),
  no_ui: TRUE,
)]
class HiddenTestSuggester extends TestSuggester {
}
