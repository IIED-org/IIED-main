<?php

declare(strict_types=1);

namespace Drupal\name\Utility;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Unicode;

/**
 * Applies string-transformation modifiers to a single token value.
 *
 * Modifier characters are L, U, F, G, T, S, B, and b.
 * When the value is wrapped in a single outer <span>, the span is
 * temporarily removed, the modifiers are applied to the inner text, and
 * the span is re-applied to preserve markup-mode wrappers.
 *
 * @internal
 */
final class NameFormatModifiers {

  /**
   * Default word-boundary pattern used by the B/b modifiers.
   */
  const BOUNDARY_REGEXP = '/[\b,\s]/';

  /**
   * Applies case, trim, escape, and word-split modifiers to a string.
   *
   * @param string $string
   *   The value to modify.
   * @param string $modifiers
   *   A sequence of modifier characters (L, U, F, G, T, S, B, b).
   * @param string $boundary_regexp
   *   Word-boundary pattern used by B and b.
   *
   * @return string
   *   The modified string.
   */
  public static function apply(
    string $string,
    string $modifiers,
    string $boundary_regexp = self::BOUNDARY_REGEXP,
  ): string {
    if (!strlen($string) || !$modifiers) {
      return $string;
    }

    [$prefix, $string, $suffix] = self::stripSpanWrapper($string);
    $handlers                   = self::modifierHandlers($boundary_regexp);

    for ($j = 0; $j < strlen($modifiers); $j++) {
      $string = self::applySingleModifier($string, $modifiers[$j], $handlers);
    }

    return $prefix . $string . $suffix;
  }

  /**
   * Extracts a single wrapping span and returns wrapper + core string.
   *
   * @return array{0: string, 1: string, 2: string}
   *   Prefix, core value, and suffix.
   */
  private static function stripSpanWrapper(string $string): array {
    if (preg_match('/^(<span[^>]*>)(.*)(<\/span>)$/i', $string, $matches)) {
      return [$matches[1], $matches[2], $matches[3]];
    }

    return ['', $string, ''];
  }

  /**
   * Returns handlers for each supported modifier character.
   *
   * @param string $boundary_regexp
   *   Word-boundary pattern used by B and b.
   *
   * @return array<string, \Closure>
   *   Modifier handlers keyed by modifier character.
   */
  private static function modifierHandlers(string $boundary_regexp): array {
    return [
      'L' => static fn (string $value): string => mb_strtolower($value),
      'U' => static fn (string $value): string => mb_strtoupper($value),
      'F' => static fn (string $value): string => Unicode::ucfirst($value),
      'G' => static fn (string $value): string => Unicode::ucwords($value),
      'T' => static fn (string $value): string => trim(preg_replace('/\s+/', ' ', $value)),
      'S' => static fn (string $value): string => Html::escape($value),
      'B' => static function (string $value) use ($boundary_regexp): string {
        $parts = preg_split($boundary_regexp, $value);
        return (string) array_shift($parts);
      },
      'b' => static function (string $value) use ($boundary_regexp): string {
        $parts = preg_split($boundary_regexp, $value);
        return (string) array_pop($parts);
      },
    ];
  }

  /**
   * Applies one modifier character to the provided string.
   *
   * @param string $string
   *   The value to modify.
   * @param string $modifier
   *   A single modifier character (L, U, F, G, T, S, B, b).
   * @param array<string, \Closure> $handlers
   *   Modifier handlers keyed by modifier character.
   *
   * @return string
   *   The modified string, or the original when the modifier is unknown.
   */
  private static function applySingleModifier(
    string $string,
    string $modifier,
    array $handlers,
  ): string {
    if (!isset($handlers[$modifier])) {
      return $string;
    }

    return $handlers[$modifier]($string);
  }

}
