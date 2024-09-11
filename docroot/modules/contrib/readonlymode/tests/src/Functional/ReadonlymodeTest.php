<?php

namespace Drupal\Tests\readonlymode\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests ReadOnlyMode.
 *
 * @package Drupal\Tests\readonlymode\Functional
 * @group readonlymode
 */
class ReadOnlyModeTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['readonlymode'];

  protected $profile = 'minimal';

  /**
   * {@inheritdoc}
   */
  public function testReadOnlyModeEnabled() {

    $account = $this->drupalCreateUser([], [], TRUE);
    $this->drupalLogin($account);
    $this->drupalGet('admin/config/development/maintenance');
    $this->assertSession()->responseContains('Read Only Mode');
  }

}
