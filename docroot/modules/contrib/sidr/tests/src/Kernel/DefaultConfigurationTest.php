<?php

namespace Drupal\Tests\sidr\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Test the module configurations.
 *
 * @group sidr
 */
class DefaultConfigurationTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['sidr'];

  /**
   * Tests the default configuration values.
   */
  public function testDefaultConfigurationValues() {
    // Installing the configuration file.
    $this->installConfig(self::$modules);
    $sidr_settings = $this->container
      ->get('config.factory')
      ->get('sidr.settings');

    $this->assertSame('dark', $sidr_settings->get('sidr_theme'));
    $this->assertSame(TRUE, $sidr_settings->get('close_on_blur'));
    $this->assertSame(TRUE, $sidr_settings->get('close_on_escape'));
  }

}
