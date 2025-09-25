<?php

namespace Drupal\taxonomy_import\Service;

/**
 * Interface for taxonomy term-related utils.
 */
interface TaxonomyUtilsInterface {

  /**
   * Loads a term given the vid and name.
   *
   * @param string $vid
   *   The vocabulary ID.
   * @param array $name
   *   The term name.
   *
   * @return object|null
   *   The term object.
   */
  public function loadTerm($vid, $name);

  /**
   * Updates the parent and description of a term.
   *
   * The term will only be updated if something has changed.
   *
   * @param string $vid
   *   The vocabulary ID.
   * @param \Drupal\taxonomy\Entity\Term $term
   *   The term.
   * @param int $parentId
   *   The new parent, possibly 0.
   * @param string $description
   *   The new description.
   * @param string $rowData
   *   The array of term data to parse.
   * @param string $customFields
   *   The custom field machine names matched in Drupal with source data.
   *
   * @return bool
   *   Whether the operation was successful or not.
   */
  public function updateTerm($vid, $term, $parentId, $description, $rowData, $customFields);

  /**
   * Create a vocabulary given the name.
   *
   * @param string $vocabularyName
   *   The name.
   *
   * @return \Drupal\taxonomy\Entity\Vocabulary|null
   *   The vocabulary or NULL if it couldn't be created.
   */
  public function createVocabulary($vocabularyName);

  /**
   * Create a term.
   *
   * @param string $vid
   *   The vocabulary ID.
   * @param string $name
   *   The name.
   * @param int $parentId
   *   The parent, possibly 0.
   * @param string $description
   *   The description.
   * @param string $rowData
   *   The array of term data to parse.
   * @param string $customFields
   *   The custom field machine names matched in Drupal with source data.
   *
   * @return bool
   *   Whether the term was created or not.
   */
  public function createTerm($vid, $name, $parentId, $description, $rowData, $customFields);

  /**
   * Returns the IDs of a term's parents.
   *
   * @param \Drupal\taxonomy\Entity\Term $term
   *   The term.
   *
   * @return array
   *   The IDs of the parents, if any.
   */
  public function getTermParentIds($term);

  /**
   * Saves an array of terms.
   *
   * @param string $vid
   *   The vocabulary ID.
   * @param array $rows
   *   This is an array of arrays, each with keys 'name', 'parent', and
   *   'description'.
   */
  public function saveTerms($vid, $rows, $forceNewTerms);

}
