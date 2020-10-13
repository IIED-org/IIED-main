<?php

namespace Drupal\Tests\term_merge\Kernel\TestDoubles;

use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\TermInterface;
use Drupal\term_merge\TermMergerInterface;

/**
 * Dummy TermMerger class used for testing purposes.
 */
class TermMergerDummy implements TermMergerInterface {

  /**
   * {@inheritdoc}
   */
  public function mergeIntoNewTerm(array $termsToMerge, $newTermLabel) {
    return new Term([], 'taxonomy_term');
  }

  /**
   * {@inheritdoc}
   */
  public function mergeIntoTerm(array $termsToMerge, TermInterface $targetTerm) {
    // Deliberately left empty because dummies don't do anything.
  }

}
