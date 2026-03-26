<?php

namespace Drupal\Tests\session_management\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Test class for user session feature testing.
 */
class SessionMonitorTest extends BrowserTestBase {

  /**
   * The theme to install as the default for testing.
   *
   * @var string
   */
  public $defaultTheme = 'stark';

  /**
   * List of module to install.
   *
   * @var string[]
   */
  protected static $modules = ['session_management', 'user'];

  /**
   * The admin user.
   *
   * @var \Drupal\user\Entity\User|false
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->adminUser = $this->createUser(['administer users']);
  }

  /**
   * Test to check Anonymous access of session menu tab.
   */
  public function testAnonymousAccessToSessionMonitorlink() {

    $enable_session_monitor = \Drupal::configFactory()->getEditable('session_management.settings')->set('enable_session_monitor', 1)->save();
    $this->drupalGet('user/1/mo_sessions');
    $this->assertSession()->statusCodeEquals(403);
    $this->assertSession()->responseContains('Access Denied');
  }

  // Test to verify accessibility of session menu tab after user login and.

  /**
   * Ensure other users' session menu tab remains inaccessible.
   */
  public function testAuthenticatedAccessToSessionMonitorlink() {

    $this->drupalLogin($this->adminUser);
    $user = $this->drupalCreateUser([]);

    // Check if user can access its own session table without
    // enabling the session monitor checkbox.
    $this->drupalGet('user/' . $this->adminUser->id() . '/mo_sessions');
    $this->assertSession()->statusCodeEquals(403);

    // Check if user can access its own session table with enabling the
    // session monitor checkbox.
    $enable_session_monitor = \Drupal::configFactory()->getEditable('session_management.settings')
      ->set('enable_session_monitor', 1)->save();
    $this->drupalGet('user/' . $this->adminUser->id() . '/mo_sessions');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Your Sessions');

    // Check if user can access another user session table.
    $this->drupalGet('user/' . $user->id() . '/mo_sessions');
    $this->assertSession()->statusCodeEquals(403);
    $this->assertSession()->pageTextContains('Access denied');
  }

}
