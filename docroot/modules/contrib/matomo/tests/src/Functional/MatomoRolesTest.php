<?php

declare(strict_types=1);

namespace Drupal\Tests\matomo\Functional;

use Drupal\Core\Session\AccountInterface;
use Drupal\Tests\BrowserTestBase;

/**
 * Test roles functionality of Matomo module.
 *
 * @group Matomo
 */
class MatomoRolesTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'matomo',
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
   * Tests if roles based tracking works.
   */
  public function testMatomoRolesTracking(): void {
    $site_id = '1';
    $this->config('matomo.settings')->set('site_id', $site_id)->save();
    $this->config('matomo.settings')->set('url_http', 'http://www.example.com/matomo/')->save();
    $this->config('matomo.settings')->set('url_https', 'https://www.example.com/matomo/')->save();

    // Test if the default settings are working as expected.
    // Add to the selected roles only.
    $this->config('matomo.settings')->set('visibility.user_role_mode', 0)->save();
    // Enable tracking for all users.
    $this->config('matomo.settings')->set('visibility.user_role_roles', [])->save();

    // Check tracking code visibility.
    $this->expectPath('', [
      'access' => TRUE,
      'matomo_snippet' => TRUE,
    ]);
    $this->expectPath('admin', [
      'access' => FALSE,
      'matomo_snippet' => TRUE,
    ]);

    $this->drupalLogin($this->adminUser);

    $this->expectPath('', [
      'access' => TRUE,
      'matomo_snippet' => TRUE,
    ]);
    $this->expectPath('admin', [
      'access' => TRUE,
      'matomo_snippet' => FALSE,
    ]);

    // Test if the non-default settings are working as expected.
    // Enable tracking only for authenticated users.
    $this->config('matomo.settings')->set('visibility.user_role_roles', [
      AccountInterface::AUTHENTICATED_ROLE => AccountInterface::AUTHENTICATED_ROLE,
    ])->save();

    $this->expectPath('', [
      'access' => TRUE,
      'matomo_snippet' => TRUE,
    ]);

    $this->drupalLogout();

    $this->expectPath('', [
      'access' => TRUE,
      'matomo_snippet' => FALSE,
    ]);

    // Add to every role except the selected ones.
    $this->config('matomo.settings')->set('visibility.user_role_mode', 1)->save();
    // Enable tracking for all users.
    $this->config('matomo.settings')->set('visibility.user_role_roles', [])->save();

    // Check tracking code visibility.
    $this->expectPath('', [
      'access' => TRUE,
      'matomo_snippet' => TRUE,
    ]);
    $this->expectPath('admin', [
      'access' => FALSE,
      'matomo_snippet' => TRUE,
    ]);

    $this->drupalLogin($this->adminUser);

    $this->expectPath('', [
      'access' => TRUE,
      'matomo_snippet' => TRUE,
    ]);
    $this->expectPath('admin', [
      'access' => FALSE,
      'matomo_snippet' => FALSE,
    ]);

    // Disable tracking for authenticated users.
    $this->config('matomo.settings')->set('visibility.user_role_roles', [
      AccountInterface::AUTHENTICATED_ROLE => AccountInterface::AUTHENTICATED_ROLE,
    ])->save();

    $this->expectPath('', [
      'access' => TRUE,
      'matomo_snippet' => FALSE,
    ]);
    $this->expectPath('admin', [
      'access' => TRUE,
      'matomo_snippet' => FALSE,
    ]);

    $this->drupalLogout();

    $this->expectPath('admin', [
      'access' => TRUE,
      'matomo_snippet' => TRUE,
    ]);
  }

  /**
   * Test a path.
   *
   * @param string $path
   *   The tested path.
   * @param array $expectations_configuration
   *   An array of the expectation that will determine the assertions.
   */
  protected function expectPath(string $path, array $expectations_configuration): void {
    $this->drupalGet($path);
    $access = $expectations_configuration['access'];
    $matomo_snippet = $expectations_configuration['matomo_snippet'];

    if ($access && $matomo_snippet) {
      $this->assertSession()->responseContains('u+"matomo.php"');
    }
    elseif (!$access && $matomo_snippet) {
      $this->assertSession()->responseContains('"403/URL = "');
    }
    elseif ($access && !$matomo_snippet) {
      $this->assertSession()->responseNotContains('u+"matomo.php"');
    }
  }

}
