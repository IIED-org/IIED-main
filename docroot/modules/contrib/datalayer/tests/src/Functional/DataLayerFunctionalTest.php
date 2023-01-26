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
   * Modules to install.
   *
   * @var array
   */
  public static $modules = [
    'datalayer',
    'dynamic_page_cache',
    'node',
    'page_cache',
    'taxonomy',
    'test_page_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

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
    $this->drupalGet('user');
    $assert = $this->assertSession();
    $assert->responseContains('window.dataLayer = window.dataLayer || []; window.dataLayer.push({');
  }

  /**
   * Test DataLayer JS language settings.
   */
  public function testDataLayerJsLanguageSettings() {
    $this->drupalGet('node');
    $assert = $this->assertSession();
    $assert->responseContains('"dataLayer":{"defaultLang"');
  }

  /**
   * Tests basic admin form functionality.
   */
  public function testAdminSettingsForm() {
    // Check default form field values.
    $assert = $this->assertSession();
    $this->drupalGet('admin/config/search/datalayer');
    $assert->pageTextContains('Include "data layer helper" library');
    $assert->checkboxNotChecked('lib_helper');
    $assert->pageTextNotContains('Data Layer Helper Library is enabled but the library is not installed at /libraries/data-layer-helper/dist/data-layer-helper.js. See: data-layer-helper on GitHub.');

    // Update form field to ensure config value changes.
    $this->submitForm(['lib_helper' => '1'], 'Save configuration');
    $assert = $this->assertSession();
    $assert->checkboxChecked('lib_helper');
    $assert->pageTextContains('Data Layer Helper Library is enabled but the library is not installed at /libraries/data-layer-helper/dist/data-layer-helper.js. See: data-layer-helper on GitHub.');
  }

  /**
   * Test that user data output into the datalayer has appropriate cachability.
   */
  public function testUserDataNotCached() {
    $this->setContainerParameter('http.response.debug_cacheability_headers', TRUE);
    $this->rebuildContainer();
    $this->resetAll();

    // Expose user details on all pages.
    $this->config('datalayer.settings')
      ->set('expose_user_details', '*')
      ->save();

    // Login and view the page as a user, verify the userUid is set in the
    // datalayer.
    $user1 = $this->drupalCreateUser();
    $this->drupalLogin($user1);

    $this->drupalGet('/test-page');
    $assert = $this->assertSession();
    $assert->responseContains('"dataLayer":{"defaultLang"');
    $assert->responseContains('"userUid":"' . $user1->id() . '"');

    // Repeat as a different user and verify the userUid data is not cached.
    $user2 = $this->drupalCreateUser();
    $this->drupalLogin($user2);
    $this->drupalGet('/test-page');
    $assert = $this->assertSession();
    $assert->responseHeaderEquals('X-Drupal-Dynamic-Cache', 'UNCACHEABLE');
    $assert->responseContains('"dataLayer":{"defaultLang"');
    $assert->responseContains('"userUid":"' . $user2->id() . '"');
  }

  /**
   * Test the 'remove_from_admin_routes' setting.
   */
  public function testAdminRouteSetting() {
    // Expose datalayer on admin routes.
    $this->config('datalayer.settings')
      ->set('remove_from_admin_routes', FALSE)
      ->save();

    $this->drupalGet('/admin');
    $this->assertSession()->responseContains('"dataLayer":{"defaultLang"');

    // Disable datalayer on admin routes.
    $this->config('datalayer.settings')
      ->set('remove_from_admin_routes', TRUE)
      ->save();

    $this->drupalGet('/admin');
    $this->assertSession()->responseNotContains('"dataLayer":{"defaultLang"');

    // Still appears on non-admin routes.
    $this->drupalGet('');
    $this->assertSession()->responseContains('"dataLayer":{"defaultLang"');
  }

}
