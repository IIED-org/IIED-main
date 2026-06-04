<?php

declare(strict_types=1);

namespace Drupal\name\Utility;

/**
 * Walks a name format string and produces condition-tagged piece arrays.
 *
 * A "piece" is an associative array with two keys:
 *   - 'value' (string): the resolved, modifier-transformed token value.
 *   - 'conditions' (string): accumulated condition characters.
 *
 * The lexer intentionally carries no state; all inputs are passed as
 * arguments. Bracket groups are resolved recursively via
 * NameFormatAssembler::assemble().
 *
 * @internal
 */
final class NameFormatLexer {

  /**
   * Walks a format string and produces an array of condition-tagged pieces.
   *
   * @param string $format
   *   The format string or segment to parse.
   * @param array $tokens
   *   The token map.
   *
   * @return array[]
   *   The generated pieces.
   */
  public static function tokenize(string $format, array $tokens): array {
    $pieces     = [];
    $modifiers  = '';
    $conditions = '';

    for ($i = 0; $i < strlen($format); $i++) {
      $char      = $format[$i];
      $last_char = ($i > 0) ? $format[$i - 1] : FALSE;

      if ($char === '\\') {
        continue;
      }
      if ($last_char === '\\') {
        $pieces[]   = self::buildPiece($char, $modifiers, $conditions);
        $modifiers  = '';
        $conditions = '';
        continue;
      }

      if (self::isModifierChar($char)) {
        $modifiers .= $char;
        continue;
      }

      if (self::isConditionChar($char)) {
        $conditions .= $char;
        continue;
      }

      if ($char === '(' || $char === ')') {
        $result     = self::processBracketGroup($format, $i, $tokens, $modifiers, $conditions);
        $pieces[]   = $result['piece'];
        $i         += $result['advance'];
        $modifiers  = '';
        $conditions = '';
        continue;
      }

      $pieces[]   = self::buildPiece(
        NameFormatTokens::resolveValue($char, $tokens),
        $modifiers,
        $conditions,
      );
      $modifiers  = '';
      $conditions = '';
    }

    return $pieces;
  }

  /**
   * Processes a bracketed segment or preserves an unmatched bracket.
   *
   * When an opening bracket is found and a matching closing bracket exists,
   * the inner segment is recursively tokenized and its conditions applied.
   * Unmatched brackets are preserved as literal characters.
   *
   * @param string $format
   *   The full format string being parsed.
   * @param int $position
   *   The current position of the bracket character.
   * @param array $tokens
   *   The token map.
   * @param string $modifiers
   *   Accumulated modifier characters.
   * @param string $conditions
   *   Accumulated condition characters.
   *
   * @return array{piece: array, advance: int}
   *   The assembled piece and the number of extra characters to advance past.
   */
  public static function processBracketGroup(
    string $format,
    int $position,
    array $tokens,
    string $modifiers,
    string $conditions,
  ): array {
    $char = $format[$position];
    if ($char !== '(') {
      return [
        'piece'   => self::buildPiece($char, $modifiers, $conditions),
        'advance' => 0,
      ];
    }

    $remaining_string = substr($format, $position);
    $closing_bracket  = self::closingBracketPosition($remaining_string);
    if ($closing_bracket !== FALSE) {
      $segment    = substr($format, $position + 1, $closing_bracket - 1);
      $sub_string = NameFormatAssembler::assemble(self::tokenize($segment, $tokens));

      return [
        'piece'   => self::buildPiece($sub_string, $modifiers, $conditions),
        'advance' => $closing_bracket,
      ];
    }

    return [
      'piece'   => self::buildPiece($char, $modifiers, $conditions),
      'advance' => 0,
    ];
  }

  /**
   * Returns the closing bracket position matching the first opening bracket.
   *
   * Escaped brackets (\( and \)) are treated as plain characters during the
   * depth scan.
   *
   * @param string $string
   *   The string starting with the opening bracket character.
   *
   * @return int|false
   *   The zero-based position of the closing bracket, or FALSE when not found.
   */
  public static function closingBracketPosition(string $string): int|false {
    $depth  = 0;
    $string = str_replace(['\(', '\)'], ['__', '__'], $string);

    for ($i = 0; $i < strlen($string); $i++) {
      $char = $string[$i];
      if ($char === '(') {
        $depth++;
      }
      elseif ($char === ')') {
        $depth--;
        if ($depth === 0) {
          return $i;
        }
      }
    }

    return FALSE;
  }

  /**
   * Returns TRUE when the character is a modifier.
   *
   * @param string $char
   *   A single format character.
   *
   * @return bool
   *   TRUE when the character is a modifier, otherwise FALSE.
   */
  public static function isModifierChar(string $char): bool {
    return in_array($char, ['L', 'U', 'F', 'T', 'S', 'G', 'B', 'b'], TRUE);
  }

  /**
   * Returns TRUE when the character is a condition flag.
   *
   * @param string $char
   *   A single format character.
   *
   * @return bool
   *   TRUE when the character is a condition, otherwise FALSE.
   */
  public static function isConditionChar(string $char): bool {
    return in_array($char, ['=', '^', '|', '+', '-', '~'], TRUE);
  }

  /**
   * Builds a single piece array after applying modifiers.
   *
   * @param string $value
   *   The resolved token value.
   * @param string $modifiers
   *   Accumulated modifier characters.
   * @param string $conditions
   *   Accumulated condition characters.
   *
   * @return array{value: string, conditions: string}
   *   The piece.
   */
  private static function buildPiece(
    string $value,
    string $modifiers,
    string $conditions,
  ): array {
    return [
      'value'      => NameFormatModifiers::apply($value, $modifiers),
      'conditions' => $conditions,
    ];
  }

}
