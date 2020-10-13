<?php

namespace Drupal\Tests\term_merge\Kernel\TestDoubles;

use Drupal\taxonomy\TermInterface;

/**
 * A term merge test class that keeps a list of called functions.
 */
class TermMergerSpy extends TermMergerMock {

  private $functionCalls = [];

  /**
   * {@inheritdoc}
   */
  public function mergeIntoNewTerm(array $termsToMerge, $newTermLabel) {
    $this->functionCalls[__FUNCTION__] = [$termsToMerge, $newTermLabel];
    return parent::mergeIntoNewTerm($termsToMerge, $newTermLabel);
  }

  /**
   * {@inheritdoc}
   */
  public function mergeIntoTerm(array $termsToMerge, TermInterface $targetTerm) {
    $this->functionCalls[__FUNCTION__] = [$termsToMerge, $targetTerm];
    parent::mergeIntoTerm($termsToMerge, $targetTerm);
  }

  /**
   * Checks a function was called on the object.
   */
  public function assertFunctionCalled($function) {
    if (!isset($this->functionCalls[$function])) {
      throw new \Exception("{$function} was not called");
    }
  }

  /**
   * Returns an array of called function names.
   *
   * @return string[]
   *   An array of called function names.
   */
  public function calledFunctions() {
    return array_keys($this->functionCalls);
  }

}
