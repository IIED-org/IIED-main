<?php

declare(strict_types=1);

namespace Drupal\Tests\matomo\Functional;

use Drupal\Component\Utility\Html;
use Drupal\Tests\BrowserTestBase;

/**
 * Test php filter functionality of Matomo module.
 *
 * @group Matomo
 */
class MatomoPhpFilterTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'matomo',
    'php',
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
   * Delegated admin user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $delegatedAdminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Administrator with all permissions.
    $permissions_admin_user = [
      'access administration pages',
      'administer matomo',
      'use php for matomo tracking visibility',
    ];
    $this->adminUser = $this->drupalCreateUser($permissions_admin_user);

    // Administrator who cannot configure tracking visibility with PHP.
    $permissions_delegated_admin_user = [
      'access administration pages',
      'administer matomo',
    ];
    $this->delegatedAdminUser = $this->drupalCreateUser($permissions_delegated_admin_user);
  }

  /**
   * Tests if PHP module integration works.
   */
  public function testMatomoPhpFilter(): void {
    $site_id = '1';
    $this->drupalLogin($this->adminUser);

    $this->drupalGet('admin/config/system/matomo');

    $edit = [];
    $edit['matomo_site_id'] = $site_id;
    $edit['matomo_url_http'] = 'http://www.example.com/matomo/';
    $edit['matomo_url_https'] = 'https://www.example.com/matomo/';
    // Skip url check errors in automated tests.
    $edit['matomo_visibility_request_path_mode'] = 2;
    $edit['matomo_visibility_request_path_pages'] = '<?php return 0; ?>';
    $this->submitForm($edit, 'Save configuration');

    // Compare saved setting with posted setting.
    $matomo_visibility_request_path_pages = \Drupal::config('matomo.settings')->get('visibility.request_path_pages');
    $this->assertEquals('<?php return 0; ?>', $matomo_visibility_request_path_pages, '[testMatomoPhpFilter]: PHP code snippet is intact.');

    // Check tracking code visibility.
    $this->config('matomo.settings')->set('visibility.request_path_pages', '<?php return TRUE; ?>')->save();
    $this->drupalGet('');
    $this->assertSession()->responseContains('u+"matomo.php"');
    $this->drupalGet('admin');
    $this->assertSession()->responseContains('u+"matomo.php"');

    $this->config('matomo.settings')->set('visibility.request_path_pages', '<?php return FALSE; ?>')->save();
    $this->drupalGet('');
    $this->assertSession()->responseNotContains('u+"matomo.php"');

    // Test administration form.
    $this->config('matomo.settings')->set('visibility.request_path_pages', '<?php return TRUE; ?>')->save();
    $this->drupalGet('admin/config/system/matomo');
    $this->assertSession()->responseContains('Pages on which this PHP code returns <code>TRUE</code> (experts only)');
    $this->assertSession()->responseContains(Html::escape('<?php return TRUE; ?>'));

    // Login the delegated user and check if fields are visible.
    $this->drupalLogin($this->delegatedAdminUser);
    $this->drupalGet('admin/config/system/matomo');
    $this->assertSession()->responseNotContains('Pages on which this PHP code returns <code>TRUE</code> (experts only)');
    $this->assertSession()->responseNotContains(Html::escape('<?php return TRUE; ?>'));

    // Set a different value and verify that this is still the same after the
    // post.
    $this->config('matomo.settings')->set('visibility.request_path_pages', '<?php return 0; ?>')->save();

    $this->drupalGet('admin/config/system/matomo');

    $edit = [];
    $edit['matomo_site_id'] = $site_id;
    $edit['matomo_url_http'] = 'http://www.example.com/matomo/';
    $edit['matomo_url_https'] = 'https://www.example.com/matomo/';
    $this->submitForm($edit, 'Save configuration');

    // Compare saved setting with posted setting.
    $matomo_visibility_request_path_mode = $this->config('matomo.settings')->get('visibility.request_path_mode');
    $matomo_visibility_request_path_pages = $this->config('matomo.settings')->get('visibility.request_path_pages');
    $this->assertEquals(2, $matomo_visibility_request_path_mode, '[testMatomoPhpFilter]: Pages on which this PHP code returns TRUE is selected.');
    $this->assertEquals('<?php return 0; ?>', $matomo_visibility_request_path_pages, '[testMatomoPhpFilter]: PHP code snippet is intact.');
  }

}
