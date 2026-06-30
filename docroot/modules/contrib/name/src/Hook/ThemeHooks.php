<?php

declare(strict_types=1);

namespace Drupal\name\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations that register the module's theme definitions.
 *
 * @internal
 */
final class ThemeHooks {

  /**
   * Implements hook_theme().
   */
  // phpcs:ignore Drupal.Commenting.PostStatementComment.Found -- #[Hook] is a PHP attribute, not a trailing comment.
  #[Hook('theme')] // @phpstan-ignore attribute.notFound
  public function theme(): array {
    return [
      // Themes an individual name element.
      'name_item'                    => [
        'variables' => ['item' => [], 'format' => NULL, 'settings' => []],
      ],
      // This themes an element into the "name et al" format.
      'name_item_list'               => [
        'variables' => ['items' => [], 'settings' => []],
      ],
      // Themes the FAPI element.
      'name'                         => [
        'render element' => 'element',
      ],
      // Provides help for the recognized characters in the name_format()
      // format parameter.
      'name_format_parameter_help'   => [
        'variables' => ['tokens' => []],
      ],
    ];
  }

}
