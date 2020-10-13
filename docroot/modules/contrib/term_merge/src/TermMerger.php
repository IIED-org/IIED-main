<?php

namespace Drupal\term_merge;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\taxonomy\TermInterface;
use Drupal\term_reference_change\ReferenceMigrator;

/**
 * Implements TermMergerInterface to provide a term merger service.
 */
class TermMerger implements TermMergerInterface {

  /**
   * The taxonomy term storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  private $termStorage;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * The term reference migration service.
   *
   * @var \Drupal\term_reference_change\ReferenceMigrator
   */
  private $migrator;

  /**
   * TermMerger constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   * @param \Drupal\term_reference_change\ReferenceMigrator $migrator
   *   The reference migration service.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, ReferenceMigrator $migrator) {
    $this->entityTypeManager = $entityTypeManager;

    $this->termStorage = $this->entityTypeManager->getStorage('taxonomy_term');
    $this->migrator = $migrator;
  }

  /**
   * {@inheritdoc}
   */
  public function mergeIntoNewTerm(array $termsToMerge, $newTermLabel) {
    $this->validateTerms($termsToMerge);

    $firstTerm = reset($termsToMerge);
    $values = [
      'name' => $newTermLabel,
      'vid' => $firstTerm->bundle(),
    ];

    /** @var \Drupal\taxonomy\TermInterface $newTerm */
    $newTerm = $this->termStorage->create($values);

    $this->mergeIntoTerm($termsToMerge, $newTerm);

    return $newTerm;
  }

  /**
   * {@inheritdoc}
   */
  public function mergeIntoTerm(array $termsToMerge, TermInterface $targetTerm) {
    $this->validateTerms($termsToMerge);

    // We have to save the term to make sure we've got an id to reference.
    if ($targetTerm->isNew()) {
      $targetTerm->save();
    }

    $firstTerm = reset($termsToMerge);
    if ($firstTerm->bundle() !== $targetTerm->bundle()) {
      throw new \RuntimeException('The target term must be in the same vocabulary as the terms being merged');
    }

    $this->migrateReferences($termsToMerge, $targetTerm);

    $this->termStorage->delete($termsToMerge);
  }

  /**
   * Asserts that all passed in terms are valid.
   *
   * @param \Drupal\taxonomy\TermInterface[] $termsToAssert
   *   The array to assert.
   */
  private function validateTerms(array $termsToAssert) {
    $this->assertTermsNotEmpty($termsToAssert);
    $this->assertAllTermsHaveSameVocabulary($termsToAssert);
  }

  /**
   * Asserts that all terms have the same vocabulary.
   *
   * @param \Drupal\taxonomy\TermInterface[] $termsToAssert
   *   The array to assert.
   */
  private function assertAllTermsHaveSameVocabulary(array $termsToAssert) {
    $vocabulary = '';

    foreach ($termsToAssert as $term) {
      if (empty($vocabulary)) {
        $vocabulary = $term->bundle();
      }

      if ($vocabulary !== $term->bundle()) {
        throw new \RuntimeException('Only merges within the same vocabulary are supported');
      }
    }
  }

  /**
   * Asserts that the termsToAssert variable is not empty.
   *
   * @param \Drupal\taxonomy\TermInterface[] $termsToAssert
   *   The array to assert.
   */
  private function assertTermsNotEmpty(array $termsToAssert) {
    if (empty($termsToAssert)) {
      throw new \RuntimeException('You must provide at least 1 term');
    }
  }

  /**
   * Updates the term references on all entities referencing multiple terms.
   *
   * @param \Drupal\taxonomy\TermInterface[] $fromTerms
   *   The terms to migrate away from.
   * @param \Drupal\taxonomy\TermInterface $toTerm
   *   The term to migrate to.
   */
  private function migrateReferences(array $fromTerms, TermInterface $toTerm) {
    foreach ($fromTerms as $fromTerm) {
      $this->migrator->migrateReference($fromTerm, $toTerm);
    }
  }

}
