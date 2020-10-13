<?php

namespace Drupal\term_merge;

use Drupal\taxonomy\TermInterface;

/**
 * Provides an interface for a term merger service.
 */
interface TermMergerInterface {

  /**
   * Merges two or more terms into a new term.
   *
   * @param \Drupal\taxonomy\TermInterface[] $termsToMerge
   *   The terms to merge.
   * @param string $newTermLabel
   *   The label of the new term.
   *
   * @return \Drupal\taxonomy\TermInterface
   *   The newly created term.
   */
  public function mergeIntoNewTerm(array $termsToMerge, $newTermLabel);

  /**
   * Merges one or more terms into an existing term.
   *
   * @param array $termsToMerge
   *   The terms to merge.
   * @param \Drupal\taxonomy\TermInterface $targetTerm
   *   The term to merge them into.
   */
  public function mergeIntoTerm(array $termsToMerge, TermInterface $targetTerm);

}
