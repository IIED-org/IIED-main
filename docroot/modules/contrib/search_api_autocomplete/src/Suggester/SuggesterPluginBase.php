<?php

namespace Drupal\search_api_autocomplete\Suggester;

use Drupal\search_api_autocomplete\Plugin\PluginBase;
use Drupal\search_api_autocomplete\SearchInterface;

/**
 * Provides a base class for suggester plugins.
 *
 * Plugins extending this class need to define a plugin definition array using the SearchApiAutocompleteSuggester attribute.
 *
 * A complete plugin definition should be written as in this example:
 *
 * @code
 * #[SearchApiAutocompleteSuggester(
 *   id: 'my_suggester',
 *   label: new TranslatableMarkup('My Suggester'),
 *   description: new TranslatableMarkup('Creates suggestions based on internet memes.'),
 *   default_weight: -10,
 * )]
 * @endcode
 *
 * @see \Drupal\search_api_autocomplete\Attribute\SearchApiAutocompleteSuggester
 * @see \Drupal\search_api_autocomplete\Suggester\SuggesterInterface
 * @see \Drupal\search_api_autocomplete\Suggester\SuggesterManager
 * @see plugin_api
 * @see hook_search_api_autocomplete_suggester_info_alter()
 */
abstract class SuggesterPluginBase extends PluginBase implements SuggesterInterface {

  /**
   * {@inheritdoc}
   */
  public function alterAutocompleteElement(array &$element) {}

  /**
   * {@inheritdoc}
   */
  public static function supportsSearch(SearchInterface $search) {
    return TRUE;
  }

}
