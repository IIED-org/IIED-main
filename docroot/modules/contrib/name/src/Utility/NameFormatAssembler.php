<?php

declare(strict_types=1);

namespace Drupal\name\Utility;

/**
 * Assembles condition-tagged format pieces into a final string.
 *
 * Each piece is an associative array with two keys:
 *   - 'value' (string): the resolved token value after modifiers.
 *   - 'conditions' (string): accumulated condition characters for the piece.
 *
 * Conditions determine whether a piece is included based on its neighbors:
 *   + - Include if both surrounding pieces are non-empty.
 *   - - Include if the preceding piece is non-empty.
 *   ~ - Include if the preceding piece is empty.
 *   = - Include if the following piece is non-empty.
 *   ^ - Include if the following piece is empty.
 *   | - Use the preceding piece unless empty; otherwise use this piece.
 *
 * @internal
 */
final class NameFormatAssembler {

  /**
   * Applies conditional inclusion rules to a list of pieces.
   *
   * Any literal double-backslashes (\\) remaining in assembled piece values
   * after tokenization are converted to tab characters to finalize
   * backslash-escape handling.
   *
   * @param array[] $pieces
   *   Each piece is an array with 'value' and 'conditions' keys.
   *
   * @return string
   *   The assembled output string.
   */
  public static function assemble(array $pieces): string {
    $count         = count($pieces);
    $parsed_pieces = [];

    foreach ($pieces as $i => $piece) {
      $component  = $piece['value'];
      $conditions = $piece['conditions'];

      $last_component = ($i > 0) ? $pieces[$i - 1]['value'] : FALSE;
      $next_component = ($i < $count - 1) ? $pieces[$i + 1]['value'] : FALSE;

      if (empty($conditions)) {
        $parsed_pieces[$i] = $component;
        continue;
      }

      if (self::pieceConditionMet($conditions, $last_component, $next_component)) {
        $parsed_pieces[$i] = $component;
      }

      // Fallback conditional overrides all other conditionals.
      if (str_contains($conditions, '|')) {
        if (!empty($last_component)) {
          unset($parsed_pieces[$i]);
        }
        if (empty($last_component)) {
          $parsed_pieces[$i] = $component;
        }
      }
    }

    return str_replace('\\\\', "\t", implode('', $parsed_pieces));
  }

  /**
   * Returns TRUE when the piece's conditions are satisfied by its neighbors.
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
    foreach (['+', '-', '~', '^', '='] as $condition) {
      if (
        str_contains($conditions, $condition) &&
        self::conditionMet($condition, $last_component, $next_component)
      ) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * Evaluates one condition character against neighboring pieces.
   */
  private static function conditionMet(
    string $condition,
    string|false $last_component,
    string|false $next_component,
  ): bool {
    return match ($condition) {
      '+' => self::plusConditionMet($last_component, $next_component),
      '-' => self::minusConditionMet($last_component),
      '~' => self::tildeConditionMet($last_component),
      '^' => self::caretConditionMet($next_component),
      '=' => self::equalsConditionMet($next_component),
      default => FALSE,
    };
  }

  /**
   * Returns TRUE when both neighboring pieces are non-empty.
   */
  private static function plusConditionMet(
    string|false $last_component,
    string|false $next_component,
  ): bool {
    return !empty($last_component) && !empty($next_component);
  }

  /**
   * Returns TRUE when the previous piece is non-empty.
   */
  private static function minusConditionMet(
    string|false $last_component,
  ): bool {
    return !empty($last_component);
  }

  /**
   * Returns TRUE when the previous piece is empty.
   */
  private static function tildeConditionMet(
    string|false $last_component,
  ): bool {
    return empty($last_component);
  }

  /**
   * Returns TRUE when the next piece is empty.
   */
  private static function caretConditionMet(
    string|false $next_component,
  ): bool {
    return empty($next_component);
  }

  /**
   * Returns TRUE when the next piece is non-empty.
   */
  private static function equalsConditionMet(
    string|false $next_component,
  ): bool {
    return !empty($next_component);
  }

}
