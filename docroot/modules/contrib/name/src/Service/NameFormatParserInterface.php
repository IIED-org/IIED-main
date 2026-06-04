<?php

declare(strict_types=1);

namespace Drupal\name\Service;

/**
 * Parses name component arrays into formatted strings.
 */
interface NameFormatParserInterface {

  /**
   * Parses a name component array into the given format.
   *
   * @param array<string, mixed> $name_components
   *   Keyed array of name components.
   * @param string $format
   *   The name format pattern to generate the name.
   * @param array<string, mixed> $settings
   *   Parser settings (sep1, sep2, sep3, markup).
   *
   * @return mixed
   *   Renderable output per markup mode.
   */
  public function parse(array $name_components, string $format = '', array $settings = []): mixed;

  /**
   * Supported markup options.
   *
   * @return array<string, \Drupal\Core\StringTranslation\TranslatableMarkup|string>
   *   Keyed markup options.
   */
  public function getMarkupOptions(): array;

  /**
   * Supported format tokens.
   *
   * @return array<string, \Drupal\Core\StringTranslation\TranslatableMarkup|string>
   *   Tokens keyed by letter.
   */
  public function tokenHelp(bool $describe = TRUE): array;

  /**
   * Renderable array of format token help.
   *
   * @return array<string, mixed>
   *   A render array.
   */
  public function renderableTokenHelp(): array;

}
