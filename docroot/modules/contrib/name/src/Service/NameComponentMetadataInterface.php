<?php

declare(strict_types=1);

namespace Drupal\name\Service;

/**
 * Translated labels and formatter output options for name components.
 *
 * @internal
 */
interface NameComponentMetadataInterface {

  /**
   * Returns translated labels for core name components.
   *
   * @param string[]|null $intersect
   *   Keys to include; empty or NULL returns all components.
   *
   * @return array<string, \Drupal\Core\StringTranslation\TranslatableMarkup|string>
   *   Keyed component labels.
   */
  public function getTranslations(?array $intersect = NULL): array;

  /**
   * Labels for formatter output type identifiers.
   *
   * @return array<string, \Drupal\Core\StringTranslation\TranslatableMarkup|string>
   *   Keys are output type machine names; values are labels.
   */
  public function getFormatterOutputTypes(): array;

  /**
   * Select-option labels for formatter output settings.
   *
   * @return array<string, \Drupal\Core\StringTranslation\TranslatableMarkup|string>
   *   Options suitable for #options on form elements.
   */
  public function getFormatterOutputOptions(): array;

}
