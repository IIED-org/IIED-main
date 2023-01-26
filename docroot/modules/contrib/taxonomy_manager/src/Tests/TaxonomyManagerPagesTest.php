<?php

namespace Drupal\taxonomy_manager\Tests;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\taxonomy\Traits\TaxonomyTestTrait;

/**
 * All pages of the module are accessible. (Routing and menus are OK)
 *
 * @group taxonomy_manager
 */
class TaxonomyManagerPagesTest extends BrowserTestBase {
  use TaxonomyTestTrait;

  /**
   * Vocabulary object.
   *
   * @var \Drupal\taxonomy\VocabularyInterface
   */
  private $vocabulary;

  /**
   * Administrator user object.
   *
   * @var \Drupal\user\Entity\User|false
   */
  private $adminUser;

  /**
   * Modules to install.
   *
   * @var array
   */
  protected static $modules = ['taxonomy_manager'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->adminUser = $this->drupalCreateUser(['administer taxonomy']);
    $this->vocabulary = $this->createVocabulary();
  }

  /**
   * Configuration page is accessible.
   */
  public function testConfigurationPageIsAccessible() {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet("admin/config");
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseContains("Advanced settings for the Taxonomy Manager");
    $this->drupalLogout();
  }

  /**
   * The page listing vocabularies is accessible.
   */
  public function testVocabulariesListIsAccessible() {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet("admin/structure");
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseContains("Advanced settings for the Taxonomy Manager");

    $this->drupalGet("admin/structure/taxonomy_manager/voc");
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseContains("Administer vocabularies with the Taxonomy Manager");
    $this->drupalLogout();
  }

  /**
   * The page with term editing UI is accessible.
   */
  public function testTermsEditingPageIsAccessible() {
    $this->drupalLogin($this->adminUser);
    $voc_name = $this->vocabulary->label();
    // Check admin/structure/taxonomy_manager/voc/{$new_voc_name}.
    $this->drupalGet("admin/structure/taxonomy_manager/voc/$voc_name");
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseContains("Taxonomy Manager - $voc_name");
    $this->drupalLogout();
  }

}
