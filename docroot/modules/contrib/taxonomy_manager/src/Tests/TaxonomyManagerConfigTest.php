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
   * Modules to install.
   *
   * @var array
   */
  protected static $modules = ['taxonomy_manager'];

  /**
   * Tests configuration options of the taxonomy_manager module.
   */
  public function testTaxonomyManagerConfiguration() {
    // Create a user with permission to administer taxonomy.
    $user = $this->drupalCreateUser(['administer taxonomy']);
    $this->drupalLogin($user);

    // Make a POST request to
    // admin/config/user-interface/taxonomy-manager-settings.
    $edit = [];
    $edit['taxonomy_manager_disable_mouseover'] = '1';
    $edit['taxonomy_manager_pager_tree_page_size'] = '50';
    $this->drupalGet('admin/config/user-interface/taxonomy-manager-settings');
    $this->submitForm($edit, $this->t('Save configuration'));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains($this->t('The configuration options have been saved.'));
  }

}
