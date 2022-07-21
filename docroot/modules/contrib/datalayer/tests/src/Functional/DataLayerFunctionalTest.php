<?php

namespace Drupal\Tests\datalayer\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Functional test cases for datalayer module.
 *
 * @group DataLayer
 */
class DataLayerFunctionalTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public $profile = 'testing';

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['node', 'datalayer', 'taxonomy'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $admin_user = $this->drupalCreateUser([
      'access administration pages',
      'administer nodes',
      'administer site configuration',
    ]);
    $this->drupalLogin($admin_user);

  }

  /**
   * Test DataLayer variable output by name.
   *
   * This will be helpful when/if the variable name can be customized.
   *
   * @see https://www.drupal.org/node/2300577
   */
  public function testDataLayerVariableOutputByName() {
    $output = $this->drupalGet('node');
    $assert = $this->assertSession();
    $assert->pageTextContains('window.dataLayer = window.dataLayer || []; window.dataLayer.push({');
  }

  /**
   * Test DataLayer JS language settings.
   */
  public function testDataLayerJsLanguageSettings() {
    $output = $this->drupalGet('node');
    $assert = $this->assertSession();
    $assert->pageTextContains('"dataLayer":{"defaultLang"');
  }

  /**
   * Tests basic admin form functionality.
   */
  public function testAdminSettingsForm() {
    // Check default form field values.
    $assert = $this->assertSession();
    $this->drupalGet('admin/config/search/datalayer');
    $assert->pageTextContains('Include "data layer helper" library');
    $this->assertNoFieldChecked('lib_helper');
    $assert->pageTextNotContains('Data Layer Helper Library is enabled but the library is not installed at /libraries/data-layer-helper/dist/data-layer-helper.js. See: data-layer-helper on GitHub.');

    // Update form field to ensure config value changes.
    $this->drupalPostForm(NULL, ['lib_helper' => '1'], 'Save configuration');
    $this->assertFieldChecked('lib_helper');
    $assert->pageTextContains('Data Layer Helper Library is enabled but the library is not installed at /libraries/data-layer-helper/dist/data-layer-helper.js. See: data-layer-helper on GitHub.');
  }

}
