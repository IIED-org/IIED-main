<?php

declare(strict_types=1);

namespace Drupal\name\Service;

/**
 * Options and patterns from name_format and name_list_format config entities.
 *
 * @internal
 */
interface FormatOptionInterface {

  /**
   * Returns sort options for custom name_format entities.
   *
   * @return array<string, string>
   *   Machine name keyed labels.
   */
  public function getCustomFormatOptions(): array;

  /**
   * Returns sort options for custom name_list_format entities.
   *
   * @return array<string, string>
   *   Machine name keyed labels.
   */
  public function getCustomListFormatOptions(): array;

  /**
   * Coerces legacy / untyped format ids for config entity load().
   *
   * For deprecated helpers and theme preprocess only. Accepts scalars and
   * \\Stringable that stringify to non-empty text; otherwise NULL.
   *
   * @param mixed $raw
   *   A machine name from legacy callers or theme variables.
   *
   * @return string|null
   *   Non-empty machine name, or NULL when load() should not run.
   */
  public static function normalizeLegacyFormatMachineName(mixed $raw): ?string;

  /**
   * Loads a format pattern by machine name.
   */
  public function getFormatPatternByMachineName(string $machine_name): ?string;

}
