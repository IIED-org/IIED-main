<?php

namespace Drupal\taxonomy_manager\Tests;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the configuration form.
 *
 * @group taxonomy_manager
 */
class TaxonomyManagerConfigTest extends BrowserTestBase {

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

    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests configuration options of the taxonomy_manager module.
   */
  public function testTaxonomyManagerConfiguration() {
    // Make a POST request to
    // admin/config/user-interface/taxonomy-manager-settings.
    $edit = [];
    $edit['taxonomy_manager_disable_mouseover'] = '1';
    $edit['taxonomy_manager_pager_tree_page_size'] = '50';
    $this->drupalGet('admin/config/user-interface/taxonomy-manager-settings');
    $this->assertSession()->statusCodeEquals(200);
    $this->submitForm($edit, 'Save configuration');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('The configuration options have been saved.');
  }

}
