<?php

namespace Drupal\Tests\search404\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use WebDriver\Exception\UnexpectedAlertOpen;

/**
 * Tests the search404 javascript functionalities.
 *
 * @group search404
 */
class Search404FunctionalJavascriptTest extends WebDriverTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'test_page_test',
    'search404',
  ];

  /**
   * A user with admin permissions.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $adminUser;

  /**
   * A user with authenticated permissions.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->config('system.site')->set('page.front', '/test-page')->save();

    $this->user = $this->drupalCreateUser([]);
    $this->adminUser = $this->drupalCreateUser([]);
    $this->adminUser->addRole($this->createAdminRole('admin', 'admin'));
    $this->adminUser->save();
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Test to see if the 404 response is being escaped.
   */
  public function testSearch404ResponseEscaped() {
    $this->config('search404.settings')->set('search404_ignore_paths', '/404Page')->save();
    $this->config('search404.settings')->set('search404_page_title', '<script>alert("code injected")</script>')->save();
    try {
      $this->drupalGet('/404Page');
    }
    catch (UnexpectedAlertOpen $e) {
      // The alert fired and was NOT escaped, so the test fails:
      $this->assertTrue(FALSE);
    }
    // If we get here, the alert was escaped and the test passes:
    $this->assertTrue(TRUE);
  }

}
