<?php

declare(strict_types=1);

namespace Drupal\name\Service;

use Drupal\Core\Field\FieldDefinitionInterface;

/**
 * Sample name generation for development and field defaults.
 */
interface GeneratorInterface {

  /**
   * Generates random sample names.
   *
   * @return array<int, array<string, mixed>>
   *   Name component arrays.
   */
  public function generateSampleNames($limit = 3, ?FieldDefinitionInterface $field_definition = NULL);

  /**
   * Loads preconfigured example names.
   *
   * @return array<int, array<string, mixed>>
   *   Name component arrays.
   */
  public function loadSampleValues($limit = 3, ?FieldDefinitionInterface $field_definition = NULL, $random = FALSE);

}
