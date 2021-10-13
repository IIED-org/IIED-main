<?php

namespace Drupal\Tests\responsive_menu\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Class ResponsiveMenuHooks.
 *
 * @package Drupal\Tests\responsive_menu\Functional
 *
 * @group responsive_menu
 */
class ResponsiveMenuHooks extends BrowserTestBase {

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
   * Tests that hook_responsive_menu_off_canvas_output_alter works.
   *
   * When using bartik as the theme the hook should trigger and disable the
   * module output in page_bottom.
   */
  public function testWarningWithNoBreakpoints() {
    \Drupal::service('theme_installer')->install(['bartik']);
    \Drupal::configFactory()->getEditable('system.theme')->set('default', 'bartik')->save();
    $this->drupalGet('/node/1');
    $this->assertSession()->elementNotExists('css', '#off-canvas-wrapper');
  }

}
