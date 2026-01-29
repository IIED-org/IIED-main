<?php

declare(strict_types=1);

namespace Drupal\Tests\matomo\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the configuration form.
 *
 * @group matomo
 */
class AdminSettingsFormTest extends BrowserTestBase {

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
   * A test administrator.
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

    $this->adminUser = $this->drupalCreateUser($permissions);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests the fields that allow to configure tracking of specific pages.
   *
   * @param string $pages
   *   A list of pages that should be tracked. One page per line.
   * @param bool $validation_error_expected
   *   TRUE if a validation error should be thrown. FALSE otherwise.
   *
   * @dataProvider pageTrackingProvider
   */
  public function testPageTracking($pages, $validation_error_expected): void {
    $edit = [
      'matomo_site_id' => '1',
      'matomo_url_http' => 'http://www.example.com/matomo/',
      'matomo_url_https' => 'https://www.example.com/matomo/',
      'matomo_visibility_request_path_mode' => 0,
      'matomo_visibility_request_path_pages' => $pages,
    ];
    $this->drupalGet('admin/config/system/matomo');
    $this->submitForm($edit, 'Save configuration');
    $has_validation_error = (bool) $this->getSession()->getPage()->find('css', '#edit-matomo-visibility-request-path-pages.error');
    $this->assertEquals($validation_error_expected, $has_validation_error);
  }

  /**
   * Provides test data for ::testPageTracking().
   *
   * @return array
   *   An array of test cases, each test case is an array with two values:
   *   0. A string containing a list of pages to track.
   *   1. A boolean indicating whether or not a validation error is expected to
   *      be thrown.
   */
  public static function pageTrackingProvider(): array {
    // @codingStandardsIgnoreStart
    return [
      [
        // No validation error should be thrown for an empty page list.
        '',
        FALSE,
      ],
      [
        // No validation error should be thrown for a list of valid pages.
        <<<'TXT'
/node/1
/blog/*/view
/shop
<front>
TXT
        ,
        FALSE,
      ],
      [
        // A validation error should be thrown if one of the pages doesn't start
        // with a slash.
        <<<'TXT'
/node/1
/blog/*/view
shop
<front>
TXT
        ,
        TRUE,
      ],
    ];
    // @codingStandardsIgnoreEnd
  }

}
