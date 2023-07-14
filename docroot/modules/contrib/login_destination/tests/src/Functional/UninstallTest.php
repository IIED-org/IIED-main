<?php

namespace Drupal\Tests\login_destination\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests module uninstallation.
 *
 * @group login_destination
 */
class UninstallTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'classy';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['login_destination'];

  /**
   * Tests module uninstallation.
   */
  public function testUninstall() {
    // Confirm that Login Destination has been installed.
    $module_handler = $this->container->get('module_handler');
    $this->assertTrue($module_handler->moduleExists('login_destination'));

    // Uninstall Login Destination.
    $this->assertTrue($this->container->get('module_installer')->uninstall(['login_destination']));
  }

}
