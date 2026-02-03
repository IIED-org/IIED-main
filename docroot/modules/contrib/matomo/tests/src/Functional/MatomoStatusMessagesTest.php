<?php

declare(strict_types=1);

namespace Drupal\Tests\matomo\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Test status messages functionality of Matomo module.
 *
 * @group Matomo
 */
class MatomoStatusMessagesTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'matomo',
    'matomo_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Admin user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $permissions = [
      'access administration pages',
      'administer matomo',
    ];

    // User to set up matomo.
    $this->adminUser = $this->drupalCreateUser($permissions);
  }

  /**
   * Tests if status messages tracking is properly added to the page.
   */
  public function testMatomoStatusMessages(): void {
    $site_id = '1';
    $this->config('matomo.settings')->set('site_id', $site_id)->save();
    $this->config('matomo.settings')->set('url_http', 'http://www.example.com/matomo/')->save();
    $this->config('matomo.settings')->set('url_https', 'https://www.example.com/matomo/')->save();

    // Enable logging of errors only.
    $this->config('matomo.settings')->set('track.messages', ['error' => 'error'])->save();
    $this->drupalGet('user/login');

    $this->submitForm([], 'Log in');
    $this->assertSession()->responseContains('_paq.push(["trackEvent", "Messages", "Error message", "Username field is required."]);');
    $this->assertSession()->responseContains('_paq.push(["trackEvent", "Messages", "Error message", "Password field is required."]);');

    // Testing this Drupal::messenger() requires an extra test module.
    $this->drupalGet('matomo-test/drupal-messenger-add-message');
    $this->assertSession()->responseNotContains('_paq.push(["trackEvent", "Messages", "Status message", "Example status message."]);');
    $this->assertSession()->responseNotContains('_paq.push(["trackEvent", "Messages", "Warning message", "Example warning message."]);');
    $this->assertSession()->responseContains('_paq.push(["trackEvent", "Messages", "Error message", "Example error message."]);');
    $this->assertSession()->responseContains('_paq.push(["trackEvent", "Messages", "Error message", "Example error message with html tags and link."]);');

    // Enable logging of status, warnings and errors.
    $this->config('matomo.settings')->set('track.messages', [
      'status' => 'status',
      'warning' => 'warning',
      'error' => 'error',
    ])->save();

    $this->drupalGet('matomo-test/drupal-messenger-add-message');
    $this->assertSession()->responseContains('_paq.push(["trackEvent", "Messages", "Status message", "Example status message."]);');
    $this->assertSession()->responseContains('_paq.push(["trackEvent", "Messages", "Warning message", "Example warning message."]);');
    $this->assertSession()->responseContains('_paq.push(["trackEvent", "Messages", "Error message", "Example error message."]);');
    $this->assertSession()->responseContains('_paq.push(["trackEvent", "Messages", "Error message", "Example error message with html tags and link."]);');
  }

}
