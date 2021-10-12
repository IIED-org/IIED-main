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
class ResponsiveMenuTest extends BrowserTestBase {

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
   * Tests that the test content has been created.
   */
  public function testExistenceOfTestContent() {
    $this->drupalGet('/node/3');
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Tests that the horizontal menu block is showing.
   */
  public function testExistenceOfHorizontalMenu() {
    $this->drupalGet('/node/3');
    $this->assertSession()->elementContains('css', '.responsive-menu-block-wrapper', 'Menu item without children');
  }

}
