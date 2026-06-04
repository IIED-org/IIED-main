<?php

declare(strict_types=1);

namespace Drupal\name\Service;

use Drupal\Core\Field\FieldDefinitionInterface;

/**
 * Options lists for name field components (titles, generational suffixes).
 */
interface NameOptionInterface {

  /**
   * Regular expression for finding the vocabulary token in option strings.
   */
  public const VOCABULARY_REGEX = '/^\[vocabulary:([0-9a-z\_]{1,})\]/';

  /**
   * Options for a name component.
   *
   * @return array<string, string>
   *   Options keyed by value.
   */
  public function getOptions(FieldDefinitionInterface $field, string $component): array;

}
