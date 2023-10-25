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
   * A user with admin permissions.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $adminUser;

  /**
   * Modules to install.
   *
   * @var array
   */
  protected static $modules = ['taxonomy_manager'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->adminUser = $this->drupalCreateUser([]);
    $this->adminUser->addRole($this->createAdminRole('admin', 'admin'));
    $this->adminUser->save();

    $this->vocabulary = $this->createVocabulary();
  }

  /**
   * Configuration page is accessible.
   */
  public function testConfigurationPageIsAccessible() {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet("admin/config");
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains("Advanced settings for the Taxonomy Manager");
    $this->drupalLogout();
  }

  /**
   * The page listing vocabularies is accessible.
   */
  public function testVocabulariesListIsAccessible() {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet("admin/structure");
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains("Taxonomy manager");
    $this->assertSession()->pageTextContains("Administer vocabularies with the Taxonomy Manager");

    $this->drupalGet("admin/structure/taxonomy_manager/voc");
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains("Taxonomy manager");
    $this->assertSession()->pageTextContains("Add new vocabulary");
    $this->drupalLogout();
  }

  /**
   * The page with term editing UI is accessible.
   */
  public function testTermsEditingPageIsAccessible() {
    $this->drupalLogin($this->adminUser);
    $vocId = $this->vocabulary->id();
    $vocLabel = $this->vocabulary->label();
    // Check admin/structure/taxonomy_manager/voc/{$new_voc_name}.
    $this->drupalGet("admin/structure/taxonomy_manager/voc/$vocId");
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseContains("Taxonomy Manager - $vocLabel");
    $this->drupalLogout();
  }

}
