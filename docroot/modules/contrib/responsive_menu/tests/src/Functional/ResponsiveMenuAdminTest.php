<?php

namespace Drupal\Tests\responsive_menu\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Class SettingsPageTest.
 *
 * @package Drupal\Tests\responsive_menu\Functional
 *
 * @group responsive_menu
 */
class ResponsiveMenuAdminTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['responsive_menu_test'];

  /**
   * {@inheritdoc}
   */
  protected $profile = 'minimal';

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Permissions for user that will be logged-in for test.
   *
   * @var array
   */
  protected static $userPermissions = [
    'access content',
    'administer site configuration',
  ];

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function setUp(): void {
    parent::setUp();
    $account = $this->drupalCreateUser(static::$userPermissions);
    $this->drupalLogin($account);
  }

  /**
   * Tests that a user with the correct permissions can access the admin page.
   */
  public function testAccessAdminPage() {
    $this->drupalGet('/admin/config/user-interface/responsive-menu');
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Tests that the menu depth control works for the horizontal menu.
   */
  public function testMenuDepthSetting() {
    $this->drupalGet('/admin/config/user-interface/responsive-menu');
    $this->getSession()->getPage()->selectFieldOption('horizontal_depth', '1');
    $this->getSession()->getPage()->pressButton('Save configuration');
    $this->drupalGet('/node/1');
    $horizontal_menu = $this->getSession()->getPage()->findById('horizontal-menu');
    $link = $horizontal_menu->findLink('A sibling on the second level');
    $this->assertTrue(empty($link), 'A second level link was found even though the menu depth was set to 1.');
  }

  /**
   * Tests that the required mmenu library has been found on the status page.
   */
  public function testLibrariesStatus() {
    $this->drupalGet('/admin/reports/status');
    $this->assertSession()->pageTextNotContains('The mmenu library must be installed at');
    $this->assertSession()->pageTextContains('Responsive menu: mmenu library');
  }

  /**
   * Tests that #3137461 is fixed.
   *
   * There should be no warning if there are no breakpoints defined.
   */
  public function testWarningWithNoBreakpoints() {
    \Drupal::service('theme_installer')->install(['responsive_menu_theme_test_nobp']);
    \Drupal::configFactory()->getEditable('system.theme')->set('default', 'responsive_menu_theme_test_nobp')->save();
    $this->drupalGet('/admin/config/user-interface/responsive-menu');
    $this->assertSession()->pageTextNotContains('Warning: htmlspecialchars() expects parameter 1 to be string, object given');
  }

  /**
   * Tests that #3143984 is fixed.
   *
   * There should be a new checkbox to enable IE11 support and if enabled
   * should add the mmenu.polyfills.js file.
   */
  public function testPolyfillsInclusion() {
    $this->drupalGet('/admin/config/user-interface/responsive-menu');
    $this->getSession()->getPage()->checkField('use_polyfills');
    $this->getSession()->getPage()->pressButton('Save configuration');
    $this->drupalGet('/node/1');
    $this->assertSession()->elementContains('css', 'body', 'mmenu.polyfills.js');
  }

}
