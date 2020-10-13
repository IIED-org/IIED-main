<?php

namespace Drupal\Tests\term_merge\Kernel\TestDoubles;

use Drupal\taxonomy\Entity\Term;

/**
 * Term merger mock class used for testing purposes.
 */
class TermMergerMock extends TermMergerDummy {

  /**
   * {@inheritdoc}
   */
  public function mergeIntoNewTerm(array $termsToMerge, $newTermLabel) {
    return new Term([], 'taxonomy_term');
  }

}
