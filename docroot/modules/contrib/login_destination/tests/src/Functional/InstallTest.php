<?php

namespace Drupal\Tests\login_destination\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests module installation.
 *
 * @group login_destination
 */
class InstallTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'classy';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [];

  /**
   * Module handler to ensure installed modules.
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
   * Tests that the module is installable.
   */
  public function testInstallation() {
    $this->assertFalse($this->moduleHandler->moduleExists('login_destination'));
    $this->assertTrue($this->moduleInstaller->install(['login_destination']));
  }

  /**
   * Tests that the module is installable with admin_toolbar_tools.
   */
  public function testInstallationWithAdminToolbar() {
    $this->assertFalse($this->moduleHandler->moduleExists('admin_toolbar'));
    $this->assertFalse($this->moduleHandler->moduleExists('admin_toolbar_tools'));
    $this->assertFalse($this->moduleHandler->moduleExists('login_destination'));
    $this->assertTrue($this->moduleInstaller->install([
      'admin_toolbar',
      'admin_toolbar_tools',
      'login_destination',
    ]));

    // Workaround https://www.drupal.org/node/2021959
    // See \Drupal\Core\Test\FunctionalTestSetupTrait::rebuildContainer.
    unset($this->moduleHandler);
    $this->rebuildContainer();
    $this->moduleHandler = $this->container->get('module_handler');

    // Ensure that all specified modules were installed.
    $this->assertTrue($this->moduleHandler->moduleExists('admin_toolbar'));
    $this->assertTrue($this->moduleHandler->moduleExists('admin_toolbar_tools'));
    $this->assertTrue($this->moduleHandler->moduleExists('login_destination'));

    // Login as admin and ensure that there are no errors.
    $admin = $this->drupalCreateUser([
      'access toolbar',
      'access administration pages',
    ]);
    $this->drupalLogin($admin);

    // Assert that expanded links are present in the HTML.
    $this->assertSession()->responseContains('class="toolbar-icon toolbar-icon-user-admin-index"');
  }

}
