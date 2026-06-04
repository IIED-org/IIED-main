<?php

declare(strict_types=1);

namespace Drupal\name\Service;

use Drupal\Core\Field\FieldDefinitionInterface;

/**
 * Autocomplete matches for name field components.
 */
interface AutocompleteInterface {

  /**
   * Gets matches for the autocompletion of name components.
   *
   * @return array<string, string>
   *   Matching suggestion labels keyed by value.
   */
  public function getMatches(FieldDefinitionInterface $field, string $target, string $string): array;

  /**
   * Combines array values into an associative array keyed by value.
   *
   * @param array<int, string> $values
   *   Values to combine.
   *
   * @return array<string, string>
   *   Combined values.
   */
  public function mapAssoc(array $values): array;

  /**
   * Finds matching stored values for a single component of a name field.
   *
   * Strictly scoped to the supplied component's column of the field storage
   * (same entity type + same field name) and filtered by entity view access.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field
   *   The field definition.
   * @param string $component
   *   Component machine name (for example, "given", "family").
   * @param string $term
   *   Case-insensitive search string typed by the user.
   * @param int $limit
   *   Maximum number of unique values to return.
   * @param string $mode
   *   Match mode: "starts_with" (default, indexable, recommended) or
   *   "contains" (substring match, non-indexable and slower on large sets).
   *
   * @return array<string, string>
   *   Matching values keyed by value.
   */
  public function findFieldValues(FieldDefinitionInterface $field, string $component, string $term, int $limit, string $mode = 'starts_with'): array;

}
