<?php

declare(strict_types=1);

namespace Drupal\name\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Options and patterns from name_format and name_list_format config entities.
 *
 * @internal
 */
class FormatOptionService implements FormatOptionInterface {

  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * Returns sort options for custom name_format entities.
   *
   * @return array<string, string>
   *   Machine name keyed labels.
   */
  public function getCustomFormatOptions(): array {
    $options = [];
    foreach ($this->entityTypeManager->getStorage('name_format')->loadMultiple() as $format) {
      $options[$format->id()] = $format->label();
    }
    natcasesort($options);
    return $options;
  }

  /**
   * Returns sort options for custom name_list_format entities.
   *
   * @return array<string, string>
   *   Machine name keyed labels.
   */
  public function getCustomListFormatOptions(): array {
    $options = [];
    foreach ($this->entityTypeManager->getStorage('name_list_format')->loadMultiple() as $format) {
      $options[$format->id()] = $format->label();
    }
    natcasesort($options);
    return $options;
  }

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
  public static function normalizeLegacyFormatMachineName(mixed $raw): ?string {
    if ($raw === NULL) {
      return NULL;
    }
    if (is_array($raw) || is_resource($raw)) {
      return NULL;
    }
    if (is_object($raw)) {
      if (!$raw instanceof \Stringable) {
        return NULL;
      }
      $candidate = (string) $raw;
    }
    else {
      $candidate = (string) $raw;
    }

    return $candidate === '' ? NULL : $candidate;
  }

  /**
   * Loads a format pattern by machine name.
   */
  public function getFormatPatternByMachineName(string $machine_name): ?string {
    $entity = $this->entityTypeManager->getStorage('name_format')->load($machine_name);
    if ($entity) {
      return $entity->get('pattern');
    }

    return NULL;
  }

}
