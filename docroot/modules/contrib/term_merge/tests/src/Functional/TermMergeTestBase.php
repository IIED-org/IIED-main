<?php

namespace Drupal\Tests\term_merge\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\field\Traits\EntityReferenceTestTrait;
use Drupal\Tests\taxonomy\Functional\TaxonomyTestTrait;

/**
 * Provides a base class for Term Merge functional tests.
 */
abstract class TermMergeTestBase extends BrowserTestBase {

  use TaxonomyTestTrait;
  use EntityReferenceTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['node', 'taxonomy', 'term_merge'];

  /**
   * The content type.
   *
   * @var \Drupal\node\Entity\NodeType
   */
  protected $contentType;

  /**
   * The vocabulary.
   *
   * @var \Drupal\taxonomy\Entity\Vocabulary
   */
  protected $vocabulary;

  /**
   * The logged in user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->contentType = $this->drupalCreateContentType();
    $this->vocabulary = $this->createVocabulary();
    $this->createEntityReferenceField('node', $this->contentType->id(), 'field_tags', 'Tags', 'taxonomy_term', 'default', [], -1);
  }

}
