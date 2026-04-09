<?php

namespace Drupal\search_api_autocomplete\Attribute;

use Drupal\Component\Plugin\Attribute\Plugin;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines an autocompletion search.
 *
 * @see \Drupal\search_api_autocomplete\Search\SearchPluginInterface
 * @see \Drupal\search_api_autocomplete\Search\SearchPluginManager
 * @see \Drupal\search_api_autocomplete\Search\SearchPluginBase
 * @see plugin_api
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class SearchApiAutocompleteSearch extends Plugin {

  /**
   * Constructs a new class instance.
   *
   * @param string $id
   *   The search plugin ID.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup|null $label
   *   (optional) The human-readable name of the search plugin.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup|null $description
   *   (optional) The search description.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup|null $group_label
   *   (optional) The search's group's label.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup|null $group_description
   *   (optional) The search's group's description.
   * @param string|null $index
   *   (optional) The search's index ID.
   * @param string|null $provider
   *   (optional) The provider of the plugin.
   * @param class-string|null $deriver
   *   (optional) The deriver class.
   * @param bool $no_ui
   *   (optional) TRUE to hide the plugin in the UI.
   */
  public function __construct(
    public readonly string $id,
    public readonly ?TranslatableMarkup $label = NULL,
    public readonly ?TranslatableMarkup $description = NULL,
    public readonly ?TranslatableMarkup $group_label = NULL,
    public readonly ?TranslatableMarkup $group_description = NULL,
    public readonly ?string $index = NULL,
    ?string $provider = NULL,
    public readonly ?string $deriver = NULL,
    public readonly bool $no_ui = FALSE,
  ) {
    $this->provider = $provider;
  }

}
