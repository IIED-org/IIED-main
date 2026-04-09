<?php

namespace Drupal\search_api_autocomplete\Attribute;

use Drupal\Component\Plugin\Attribute\Plugin;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines an autocomplete suggester plugin.
 *
 * @see \Drupal\search_api_autocomplete\Suggester\SuggesterInterface
 * @see \Drupal\search_api_autocomplete\Suggester\SuggesterManager
 * @see \Drupal\search_api_autocomplete\Suggester\SuggesterPluginBase
 * @see plugin_api
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class SearchApiAutocompleteSuggester extends Plugin {

  /**
   * Constructs a new class instance.
   *
   * @param string $id
   *   The suggester plugin ID.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup|null $label
   *   (optional) The human-readable name of the suggester plugin.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup|null $description
   *   (optional) The suggester description.
   * @param int $default_weight
   *   (optional) The default weight for this suggester.
   * @param class-string|null $deriver
   *   (optional) The deriver class.
   * @param bool $no_ui
   *   (optional) TRUE to hide the plugin in the UI.
   */
  public function __construct(
    public readonly string $id,
    public readonly ?TranslatableMarkup $label = NULL,
    public readonly ?TranslatableMarkup $description = NULL,
    public readonly int $default_weight = 0,
    public readonly ?string $deriver = NULL,
    public readonly bool $no_ui = FALSE,
  ) {}

}
