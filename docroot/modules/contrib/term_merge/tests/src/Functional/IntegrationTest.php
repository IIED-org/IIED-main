<?php

namespace Drupal\Tests\term_merge\Functional;

use Drupal\taxonomy\TermInterface;

/**
 * Tests the Term Merge module.
 *
 * @group term_merge
 */
class IntegrationTest extends TermMergeTestBase {

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $permissions = ['merge taxonomy terms', 'edit terms in ' . $this->vocabulary->id()];
    $this->user = $this->drupalCreateUser($permissions);
    $this->drupalLogin($this->user);
  }

  /**
   * Tests that a simple merge succeeds.
   */
  public function testMergeSucceeds() {
    $a = $this->createTerm($this->vocabulary);
    $b = $this->createTerm($this->vocabulary);
    $node = $this->drupalCreateNode([
      'type' => $this->contentType->id(),
      'field_tags' => [
        ['target_id' => $a->id()],
        ['target_id' => $b->id()],
      ],
    ]);

    $this->mergeAintoB($a, $b);

    /** @var \Drupal\node\NodeInterface $loadedNode */
    $loadedNode = \Drupal::entityTypeManager()->getStorage('node')->load($node->id());
    $expected = [['target_id' => $b->id()]];
    $this->assertEquals($expected, $loadedNode->get('field_tags')->getValue());
  }

  /**
   * Merges term A into term B.
   *
   * @param \Drupal\taxonomy\TermInterface $a
   *   Term A.
   * @param \Drupal\taxonomy\TermInterface $b
   *   Term B.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   * @throws \Behat\Mink\Exception\ExpectationException
   * @throws \Behat\Mink\Exception\ResponseTextException
   */
  private function mergeAintoB(TermInterface $a, TermInterface $b) {
    $this->drupalGet("/admin/structure/taxonomy/manage/{$this->vocabulary->id()}/merge");
    $this->assertSession()->statusCodeEquals(200);

    $this->getSession()
      ->getPage()
      ->selectFieldOption('Terms to merge', $a->id());
    $this->getSession()->getPage()->pressButton('Merge');
    $this->assertSession()->statusCodeEquals(200);

    $this->getSession()
      ->getPage()
      ->selectFieldOption('Existing term', $b->id());
    $this->getSession()->getPage()->pressButton('Submit');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()
      ->pageTextContains("You are about to merge 1 terms into existing term {$b->label()}. This action can't be undone. Are you sure you wish to continue with merging the terms below?");
    $this->getSession()->getPage()->pressButton('Confirm merge');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()
      ->pageTextContains("Successfully merged 1 terms into {$b->label()}");
  }

  /**
   * Tests that a merge with multiple references succeeds.
   */
  public function testRegression2976174() {
    $this->createEntityReferenceField('node', $this->contentType->id(), 'field_other_tags', 'Other tags', 'taxonomy_term', 'default', [], -1);
    $this->createEntityReferenceField('node', $this->contentType->id(), 'field_empty_tags', 'Empty tags', 'taxonomy_term', 'default', [], -1);

    $a = $this->createTerm($this->vocabulary);
    $b = $this->createTerm($this->vocabulary);
    $node = $this->drupalCreateNode([
      'type' => $this->contentType->id(),
      'field_tags' => [
        ['target_id' => $a->id()],
        ['target_id' => $b->id()],
      ],
      'field_other_tags' => [
        ['target_id' => $a->id()],
      ],
    ]);

    $this->mergeAintoB($a, $b);

    /** @var \Drupal\node\NodeInterface $loadedNode */
    $loadedNode = \Drupal::entityTypeManager()->getStorage('node')->load($node->id());
    $expected = [['target_id' => $b->id()]];
    $this->assertEquals($expected, $loadedNode->get('field_tags')->getValue());
    $this->assertEquals($expected, $loadedNode->get('field_other_tags')->getValue());
    $this->assertTrue($loadedNode->get('field_empty_tags')->isEmpty());
  }

}
