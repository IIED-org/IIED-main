<?php

declare(strict_types=1);

namespace Drupal\name\Utility;

/**
 * Facade: converts a token map and a format string into a plain string.
 *
 * The format-string parsing pipeline is split across three focused utility
 * classes. This class re-exports their public APIs as static methods to
 * preserve backwards compatibility for code that calls NameFormatParser
 * directly and for NameFormatParserService subclasses.
 *
 * Pipeline: NameFormatTokens::build() (components to token map),
 * NameFormatLexer::tokenize() (format string to pieces), and
 * NameFormatAssembler::assemble() (pieces to plain string).
 *
 * @see \Drupal\name\Utility\NameFormatLexer
 * @see \Drupal\name\Utility\NameFormatModifiers
 * @see \Drupal\name\Utility\NameFormatAssembler
 * @see \Drupal\name\Utility\NameFormatTokens
 */
final class NameFormatParser {

  /**
   * Default word-boundary pattern used by the B/b modifiers.
   *
   * Re-exported from NameFormatModifiers for backwards compatibility.
   */
  const BOUNDARY_REGEXP = NameFormatModifiers::BOUNDARY_REGEXP;

  /**
   * Converts a format string and token map into a formatted string.
   *
   * Escaped backslashes in the format string are neutralized to a tab
   * placeholder before tokenization; any remaining literal double-backslashes
   * in assembled token values are also converted by the assembler.
   *
   * @param string $format
   *   The name format pattern.
   * @param array $tokens
   *   Token map as returned by NameFormatTokens::build().
   *
   * @return string
   *   The formatted string.
   */
  public static function format(string $format, array $tokens): string {
    if (empty($format)) {
      return '';
    }

    // Neutralize escaped backslashes so the tokenizer does not treat them
    // as escape prefixes.
    $format = str_replace('\\\\', "\t", $format);

    return NameFormatAssembler::assemble(
      NameFormatLexer::tokenize($format, $tokens),
    );
  }

  /**
   * Applies case, trim, escape, and word-split modifiers to a string.
   *
   * Delegates to NameFormatModifiers::apply().
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
  public static function applyModifiers(
    string $string,
    string $modifiers,
    string $boundary_regexp = self::BOUNDARY_REGEXP,
  ): string {
    return NameFormatModifiers::apply($string, $modifiers, $boundary_regexp);
  }

  /**
   * Returns the closing bracket position matching the first opening bracket.
   *
   * Delegates to NameFormatLexer::closingBracketPosition().
   *
   * @param string $string
   *   The string starting with the opening bracket character.
   *
   * @return int|false
   *   The zero-based position of the closing bracket, or FALSE when not found.
   */
  public static function closingBracketPosition(string $string): int|false {
    return NameFormatLexer::closingBracketPosition($string);
  }

  /**
   * Returns TRUE when the character is a modifier.
   *
   * Delegates to NameFormatLexer::isModifierChar().
   *
   * @param string $char
   *   A single format character.
   *
   * @return bool
   *   TRUE when the character is a modifier, otherwise FALSE.
   */
  public static function isModifierChar(string $char): bool {
    return NameFormatLexer::isModifierChar($char);
  }

  /**
   * Returns TRUE when the character is a condition flag.
   *
   * Delegates to NameFormatLexer::isConditionChar().
   *
   * @param string $char
   *   A single format character.
   *
   * @return bool
   *   TRUE when the character is a condition, otherwise FALSE.
   */
  public static function isConditionChar(string $char): bool {
    return NameFormatLexer::isConditionChar($char);
  }

  /**
   * Resolves a single format character to its token value.
   *
   * Delegates to NameFormatTokens::resolveValue().
   *
   * @param string $char
   *   A single format character.
   * @param array $tokens
   *   The token map.
   *
   * @return string
   *   The resolved value or the original character.
   */
  public static function resolveTokenValue(string $char, array $tokens): string {
    return NameFormatTokens::resolveValue($char, $tokens);
  }

  /**
   * Applies conditional inclusion rules to a list of pieces.
   *
   * Delegates to NameFormatAssembler::assemble().
   *
   * @param array[] $pieces
   *   Each piece is an array with 'value' and 'conditions' keys.
   *
   * @return string
   *   The assembled output string.
   */
  public static function applyConditions(array $pieces): string {
    return NameFormatAssembler::assemble($pieces);
  }

  /**
   * Returns TRUE when the piece's conditions are satisfied by its neighbors.
   *
   * Delegates to NameFormatAssembler::pieceConditionMet().
   *
   * @param string $conditions
   *   Accumulated condition characters for the piece.
   * @param string|false $last_component
   *   The preceding piece value, or FALSE when there is none.
   * @param string|false $next_component
   *   The following piece value, or FALSE when there is none.
   *
   * @return bool
   *   TRUE when the conditions match the surrounding pieces.
   */
  public static function pieceConditionMet(
    string $conditions,
    string|false $last_component,
    string|false $next_component,
  ): bool {
    return NameFormatAssembler::pieceConditionMet(
      $conditions,
      $last_component,
      $next_component,
    );
  }

  /**
   * Processes a bracketed segment or preserves an unmatched bracket.
   *
   * Delegates to NameFormatLexer::processBracketGroup().
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
    return NameFormatLexer::processBracketGroup(
      $format,
      $position,
      $tokens,
      $modifiers,
      $conditions,
    );
  }

}
