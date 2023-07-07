<?php

namespace Drupal\Tests\isbn\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests module uninstallation.
 *
 * @group isbn
 */
class UninstallTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['isbn'];

  /**
   * Tests module uninstallation.
   */
  public function testUninstall() {
    // Confirm that isbn has been installed.
    $module_handler = $this->container->get('module_handler');
    $this->assertTrue($module_handler->moduleExists('isbn'));

    // Uninstall isbn.
    $this->container->get('module_installer')->uninstall(['isbn']);
    $this->assertFalse($module_handler->moduleExists('isbn'));
  }

}
