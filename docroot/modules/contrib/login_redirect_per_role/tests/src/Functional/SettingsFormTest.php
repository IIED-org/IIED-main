<?php

namespace Drupal\Tests\login_redirect_per_role\Functional;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the Redirect URL settings form.
 *
 * @group login_redirect_per_role
 */
class SettingsFormTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['login_redirect_per_role_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * User with administer site configuration rights.
   *
   * @var \Drupal\user\Entity\User|false
   */
  protected $adminUser;

  /**
   * A regular user.
   *
   * @var \Drupal\user\Entity\User|false
   */
  protected $webUser;

  /**
   * The ID of the admin role.
   *
   * @var string
   */
  protected $adminRole;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->webUser = $this->createUser();
    $this->adminRole = $this->drupalCreateRole(['administer site configuration']);
    $this->adminUser = $this->createUser([], NULL, FALSE, ['roles' => $this->adminRole]);
  }

  /**
   * Tests the Redirect URL settings form.
   */
  public function testSettingsForm() {
    // Login as an admin user.
    $this->drupalLogin($this->adminUser);

    $this->drupalGet(Url::fromRoute('login_redirect_per_role.redirect_url_admin_settings'));
    $this->submitForm([
      'login[authenticated][redirect_url]' => '/user',
      'login[authenticated][allow_destination]' => TRUE,
      'login[authenticated][weight]' => 10,
      // Test that <front> works as URL.
      "login[$this->adminRole][redirect_url]" => '<front>',
      "login[$this->adminRole][allow_destination]" => TRUE,
      "login[$this->adminRole][weight]" => 1,
    ], 'Save configuration');

    $this->assertSession()->pageTextContains('The configuration options have been saved.');

    // Check if config is updated.
    $this->assertEquals([
      'login' => [
        'authenticated' => [
          'redirect_url' => '/user',
          'allow_destination' => TRUE,
          'weight' => 10,
        ],
        $this->adminRole => [
          'redirect_url' => '<front>',
          'allow_destination' => TRUE,
          'weight' => 1,
        ],
      ],
      'logout' => [
        'authenticated' => [
          'redirect_url' => '',
          'allow_destination' => FALSE,
          'weight' => 0,
        ],
        $this->adminRole => [
          'redirect_url' => '',
          'allow_destination' => FALSE,
          'weight' => 0,
        ],
      ],
    ], $this->config('login_redirect_per_role.settings')->get());

    // Check that URL validation works.
    $this->drupalGet(Url::fromRoute('login_redirect_per_role.redirect_url_admin_settings'));
    $this->submitForm([
      'login[authenticated][redirect_url]' => '/admin',
      'logout[authenticated][redirect_url]' => '/admin',
    ], 'Save configuration');

    $this->assertSession()->responseContains(new FormattableMarkup('<strong>@action:</strong> Redirect URL for "@role" role is invalid or you do not have access to it.', [
      '@action' => 'Login redirect',
      '@role' => 'Authenticated user',
    ]));

    $this->assertSession()->responseContains(new FormattableMarkup('<strong>@action:</strong> Redirect URL for "@role" role is invalid or you do not have access to it.', [
      '@action' => 'Logout redirect',
      '@role' => 'Authenticated user',
    ]));

    // Check URL validation with tokens.
    $this->drupalGet(Url::fromRoute('login_redirect_per_role.redirect_url_admin_settings'));
    $this->submitForm([
      'login[authenticated][redirect_url]' => '[site:valid-path]',
      'logout[authenticated][redirect_url]' => '[site:invalid-path]',
    ], 'Save configuration');

    $this->assertSession()->responseNotContains(new FormattableMarkup('<strong>@action:</strong> Redirect URL for "@role" role is invalid or you do not have access to it.', [
      '@action' => 'Login redirect',
      '@role' => 'Authenticated user',
    ]));

    $this->assertSession()->responseContains(new FormattableMarkup('<strong>@action:</strong> Redirect URL for "@role" role is invalid or you do not have access to it.', [
      '@action' => 'Logout redirect',
      '@role' => 'Authenticated user',
    ]));

    // Check that tokens are validated.
    $this->drupalGet(Url::fromRoute('login_redirect_per_role.redirect_url_admin_settings'));
    $this->submitForm([
      'login[authenticated][redirect_url]' => '[site:invalid-token]',
    ], 'Save configuration');

    $this->assertSession()->responseContains(new FormattableMarkup('%name is using the following invalid tokens: @invalid-tokens.', [
      '%name' => 'Redirect URL',
      '@invalid-tokens' => '[site:invalid-token]',
    ]));

    $this->drupalGet(Url::fromRoute('login_redirect_per_role.redirect_url_admin_settings'));
    $this->submitForm([
      "logout[$this->adminRole][redirect_url]" => '/user',
      "logout[$this->adminRole][allow_destination]" => TRUE,
    ], 'Save configuration');

    // Check if login config is still .
    $this->assertEquals([
      'login' => [
        'authenticated' => [
          'redirect_url' => '/user',
          'allow_destination' => TRUE,
          'weight' => 10,
        ],
        $this->adminRole => [
          'redirect_url' => '<front>',
          'allow_destination' => TRUE,
          'weight' => 1,
        ],
      ],
      'logout' => [
        'authenticated' => [
          'redirect_url' => '',
          'allow_destination' => FALSE,
          'weight' => 0,
        ],
        $this->adminRole => [
          'redirect_url' => '/user',
          'allow_destination' => TRUE,
          'weight' => 0,
        ],
      ],
    ], $this->config('login_redirect_per_role.settings')->get());
  }

  /**
   * Test access to the settings form.
   */
  public function testAccess() {
    // An anonymous user doens't have the right permission to access the
    // settings form.
    $this->drupalGet(Url::fromRoute('login_redirect_per_role.redirect_url_admin_settings'));
    $this->assertSession()->statusCodeEquals(403);

    // Login as a regular user.
    $this->drupalLogin($this->webUser);
    // The web user doens't have the 'administer site configuration' permission.
    $this->drupalGet(Url::fromRoute('login_redirect_per_role.redirect_url_admin_settings'));
    $this->assertSession()->statusCodeEquals(403);

    // Login as an admin user.
    $this->drupalLogin($this->adminUser);
    // The admin user has the 'administer site configuration' permission.
    $this->drupalGet(Url::fromRoute('login_redirect_per_role.redirect_url_admin_settings'));
    $this->assertSession()->statusCodeEquals(200);
  }

}
