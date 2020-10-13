<?php

namespace Drupal\Tests\term_merge\Kernel;

use Drupal\taxonomy\TermInterface;
use Drupal\term_merge\TermMerger;

/**
 * Tests term merging for taxonomy terms.
 *
 * @group term_merge
 */
class TermMergerTermCrudTest extends MergeTermsTestBase {

  /**
   * Returns possible merge options that can be selected in the interface.
   *
   * @return array
   *   An array of options. Each option has contains the following values:
   *   - methodName: the selected method for merging to the target term.
   *   - target: a string representing the target taxonomy term.
   */
  public function mergeTermFunctionsProvider() {

    $functions['::mergeIntoNewTerm'] = [
      'methodName' => 'mergeIntoNewTerm',
      'target' => 'new term',
    ];

    $functions['::mergeIntoTerm'] = [
      'methodName' => 'mergeIntoTerm',
      'target' => '',
    ];

    return $functions;
  }

  /**
   * Tests only taxonomy terms in the same vocabulary can be merged.
   *
   * @param string $methodName
   *   The merge method being tested.
   * @param string $target
   *   The label for the taxonomy term target.
   *
   * @test
   * @dataProvider mergeTermFunctionsProvider
   * @expectedException \RuntimeException
   * @expectedExceptionMessage Only merges within the same vocabulary are supported
   */
  public function canOnlyMergeTermsInTheSameVocabulary($methodName, $target) {
    $vocab2 = $this->createVocabulary();
    $term3 = $this->createTerm($vocab2);

    $terms = [reset($this->terms), $term3];

    $sut = $this->createSubjectUnderTest();

    $sut->{$methodName}($terms, $this->prepareTarget($target));
  }

  /**
   * Tests the form validation for the minimum required input.
   *
   * @param string $methodName
   *   The merge method being tested.
   * @param string $target
   *   The label for the taxonomy term target.
   *
   * @test
   * @dataProvider mergeTermFunctionsProvider
   * @expectedException \RuntimeException
   * @expectedExceptionMessage You must provide at least 1 term
   */
  public function minimumTermsValidation($methodName, $target) {
    $sut = $this->createSubjectUnderTest();

    $sut->{$methodName}([], $this->prepareTarget($target));
  }

  /**
   * Tests a newly created term is available when merging to a new term.
   *
   * @test
   */
  public function mergeIntoNewTermCreatesNewTerm() {
    $sut = $this->createSubjectUnderTest();

    $termLabel = 'newTerm';
    $term = $sut->mergeIntoNewTerm($this->terms, $termLabel);

    self::assertTrue($term instanceof TermInterface);
    self::assertSame($termLabel, $term->label());
    // Id is only set if the term has been saved.
    self::assertNotNull($term->id());
  }

  /**
   * Tests the validation for the target term being in the same vocabulary.
   *
   * @test
   * @expectedException \RuntimeException
   * @expectedExceptionMessage The target term must be in the same vocabulary as the terms being merged
   */
  public function existingTermMustBeInSameVocabularyAsMergedTerms() {
    $sut = $this->createSubjectUnderTest();

    $term = $this->createTerm($this->createVocabulary());

    $sut->mergeIntoTerm($this->terms, $term);
  }

  /**
   * Tests a taxonomy term that is passed to the migration is saved correctly.
   *
   * @test
   */
  public function mergeIntoTermSavesTermIfNewTermIsPassedIn() {
    $sut = $this->createSubjectUnderTest();
    $values = [
      'name' => 'Unsaved term',
      'vid' => $this->vocabulary->id(),
    ];
    /** @var \Drupal\taxonomy\TermInterface $term */
    $term = $this->entityTypeManager->getStorage('taxonomy_term')->create($values);
    self::assertEmpty($term->id());

    $sut->mergeIntoTerm($this->terms, $term);

    self::assertNotEmpty($term->id());
  }

  /**
   * Tests the merged terms are deleted after the migration.
   *
   * @param string $methodName
   *   The merge method being tested.
   * @param string $target
   *   The label for the taxonomy term target.
   *
   * @test
   * @dataProvider mergeTermFunctionsProvider
   */
  public function mergedTermsAreDeleted($methodName, $target) {
    $sut = $this->createSubjectUnderTest();

    $sut->{$methodName}($this->terms, $this->prepareTarget($target));

    $termStorage = $this->entityTypeManager->getStorage('taxonomy_term');
    $termIds = array_keys($this->terms);
    self::assertEquals([], $termStorage->loadMultiple($termIds));
  }

  /**
   * Creates the class used for merging terms.
   *
   * @return \Drupal\term_merge\TermMerger
   *   The class used for merging terms
   */
  private function createSubjectUnderTest() {
    $sut = new TermMerger($this->entityTypeManager, \Drupal::service('term_reference_change.migrator'));
    return $sut;
  }

  /**
   * {@inheritdoc}
   */
  protected function numberOfTermsToSetUp() {
    return 2;
  }

}
