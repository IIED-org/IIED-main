<?php

declare(strict_types=1);

namespace Drupal\name\Utility;

use Drupal\Component\Utility\Html;

/**
 * Stateless helpers for name field components (keys, layout, sanitization).
 *
 * @internal
 */
final class NameComponents {

  /**
   * Core name component columns (machine name => machine name).
   *
   * @return array<string, string>
   *   Keyed list of component machine names.
   */
  public static function coreKeys(): array {
    return [
      'title' => 'title',
      'given' => 'given',
      'middle' => 'middle',
      'family' => 'family',
      'credentials' => 'credentials',
      'generational' => 'generational',
    ];
  }

  /**
   * Applies component order and visibility for a cultural layout.
   *
   * @param array $element
   *   A name sub-element tree (e.g. $element['_name']).
   * @param string $layout
   *   Layout machine name: default, asian, eastern, or german.
   */
  public static function applyLayout(array &$element, string $layout = 'default'): void {
    $weights = [
      'asian' => [
        'family' => 1,
        'middle' => 2,
        'given' => 3,
        'title' => 4,
        // The 'generational' value is removed from the display.
        'generational' => 5,
        'credentials' => 6,
      ],
      'eastern' => [
        'title' => 1,
        'family' => 2,
        'given' => 3,
        'middle' => 4,
        'generational' => 5,
        'credentials' => 6,
      ],
      'german' => [
        'title' => 1,
        'credentials' => 2,
        'given' => 3,
        'middle' => 4,
        'family' => 5,
        // The 'generational' value is removed from the display.
        'generational' => 7,
      ],
    ];
    if (isset($weights[$layout])) {
      foreach ($weights[$layout] as $component => $weight) {
        if (isset($element[$component])) {
          $element[$component]['#weight'] = $weight;
        }
      }
    }

    if ($layout === 'asian' || $layout === 'german') {
      if (isset($element['generational'])) {
        $element['generational']['#default_value'] = '';
        $element['generational']['#access'] = FALSE;
      }
    }
  }

  /**
   * Sanitizes a name component value or full string.
   *
   * @param mixed $item
   *   A string or a component value array.
   * @param string|null $column
   *   When $item is an array, the component key to read.
   * @param string $type
   *   Default (escaped), plain (strip_tags), or raw (unchanged).
   *
   * @return string
   *   Sanitized or raw string.
   */
  public static function sanitizeValue(mixed $item, ?string $column = NULL, string $type = 'default'): string {
    $safe_key = 'safe' . ($type === 'default' ? '' : '_' . $type);
    if (is_array($item) && isset($item[$safe_key])) {
      return $item[$safe_key][$column];
    }

    $value = is_array($item) ? (string) $item[$column] : $item;
    return match ($type) {
      'plain' => strip_tags((string) $value),
      'raw' => (string) $value,
      default => Html::escape((string) $value),
    };
  }

}
