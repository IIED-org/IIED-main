<?php

namespace Drupal\Tests\term_merge\Kernel;

use Drupal\term_merge\TermMerger;
use Drupal\Tests\field\Traits\EntityReferenceTestTrait;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;

/**
 * Tests term merging for nodes.
 *
 * @group term_merge
 */
class TermMergerNodeCrudTest extends MergeTermsTestBase {

  use NodeCreationTrait;
  use ContentTypeCreationTrait;
  use EntityReferenceTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'filter',
    'field',
    'node',
    'term_merge',
    'taxonomy',
    'text',
    'user',
    'system',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->installSchema('system', 'sequences');
    $this->installSchema('node', 'node_access');
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installConfig('node');
    $this->setUpContentType();
  }

  /**
   * Tests taxonomy term references are updated in a node after a term merge.
   *
   * @test
   */
  public function nodeReferencesAreUpdated() {
    $firstTerm = reset($this->terms);
    $node = $this->createNode(['field_terms' => ['target_id' => $firstTerm->id()]]);

    $sut = new TermMerger($this->entityTypeManager, \Drupal::service('term_reference_change.migrator'));
    $newTerm = $sut->mergeIntoNewTerm($this->terms, 'NewTerm');

    /** @var \Drupal\node\Entity\Node $loadedNode */
    $loadedNode = $this->entityTypeManager->getStorage('node')->load($node->id());
    $referencedTerms = $loadedNode->field_terms->getValue();
    self::assertCount(1, $referencedTerms);
    $firstReference = reset($referencedTerms);
    self::assertEquals($newTerm->id(), $firstReference['target_id']);
  }

  /**
   * Tests a node with both term references has a single value after a merge.
   *
   * @test
   */
  public function ifNodeReferencesBothTermsItWillOnlyReferenceTargetTermOnce() {
    $firstTerm = reset($this->terms);
    $lastTerm = end($this->terms);
    $values = [
      'field_terms' => ['target_id' => $firstTerm->id()],
      ['target_id' => $lastTerm->id()],
    ];
    $node = $this->createNode($values);

    $sut = new TermMerger($this->entityTypeManager, \Drupal::service('term_reference_change.migrator'));
    $newTerm = $sut->mergeIntoNewTerm($this->terms, 'NewTerm');

    /** @var \Drupal\node\Entity\Node $loadedNode */
    $loadedNode = $this->entityTypeManager->getStorage('node')->load($node->id());
    $referencedTerms = $loadedNode->field_terms->getValue();
    self::assertCount(1, $referencedTerms);
    $firstReference = reset($referencedTerms);
    self::assertEquals($newTerm->id(), $firstReference['target_id']);
  }

  /**
   * Set up a content type for testing purposes.
   */
  private function setUpContentType() {
    $bundle = 'page';
    $this->createContentType([
      'type' => $bundle,
      'name' => 'Basic page',
      'display_submitted' => FALSE,
    ]);

    $entityType = 'node';
    $fieldName = 'field_terms';
    $fieldLabel = 'Terms';
    $targetEntityType = 'taxonomy_term';
    $this->createEntityReferenceField($entityType, $bundle, $fieldName, $fieldLabel, $targetEntityType);
  }

  /**
   * {@inheritdoc}
   */
  protected function numberOfTermsToSetUp() {
    return 2;
  }

}
