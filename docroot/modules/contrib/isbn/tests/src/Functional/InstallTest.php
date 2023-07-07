<?php

namespace Drupal\Tests\isbn\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests module installation.
 *
 * @group isbn
 */
class InstallTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [];

  /**
   * Module handler, used to check which modules are installed.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  public $moduleHandler;

  /**
   * Module installer.
   *
   * @var \Drupal\Core\Extension\ModuleInstallerInterface
   */
  public $moduleInstaller;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    $this->moduleHandler = $this->container->get('module_handler');
    $this->moduleInstaller = $this->container->get('module_installer');
  }

  /**
   * Tests module installation.
   */
  public function testInstallation() {
    $this->assertFalse($this->moduleHandler->moduleExists('isbn'));
    $this->assertTrue($this->moduleInstaller->install(['isbn']));
    $this->assertTrue($this->moduleHandler->moduleExists('isbn'));
  }

}
