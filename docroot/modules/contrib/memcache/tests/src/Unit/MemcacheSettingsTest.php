<?php

namespace Drupal\Tests\memcache\Unit;

use Drupal\Core\Site\Settings;
use Drupal\memcache\MemcacheSettings;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\memcache\MemcacheSettings
 * @group memcache
 */
class MemcacheSettingsTest extends UnitTestCase {

  /**
   * Simple settings array to test against.
   *
   * @var array
   */
  protected $config = [];

  /**
   * The class under test.
   *
   * @var \Drupal\memcache\MemcacheSettings
   */
  protected $settings;

  /**
   * @covers ::__construct
   */
  protected function setUp(): void {
    parent::setUp();
    $this->config = [
      'memcache' => [
        'servers' => ['127.0.0.2:12345' => 'default'],
        'bin' => ['default' => 'default'],
      ],
      'hash_salt' => $this->randomMachineName(),
    ];
    $settings = new Settings($this->config);
    $this->settings = new MemcacheSettings($settings);
  }

  /**
   * @covers ::get
   */
  public function testGet() {
    // Test stored settings.
    $this->assertEquals($this->config['memcache']['servers'], $this->settings->get('servers'), 'The correct setting was not returned.');
    $this->assertEquals($this->config['memcache']['bin'], $this->settings->get('bin'), 'The correct setting was not returned.');

    // Test setting that isn't stored with default.
    $this->assertEquals('3', $this->settings->get('three', '3'), 'Default value for a setting not properly returned.');
    $this->assertNull($this->settings->get('nokey'), 'Non-null value returned for a setting that should not exist.');
  }

  /**
   * @covers ::getAll
   */
  public function testGetAll() {
    $this->assertEquals($this->config['memcache'], $this->settings->getAll());
  }

}
