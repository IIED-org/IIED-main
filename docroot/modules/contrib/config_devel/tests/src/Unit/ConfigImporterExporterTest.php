<?php

namespace Drupal\Tests\config_devel\Unit;

use Prophecy\PhpUnit\ProphecyTrait;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\ProxyClass\Lock\PersistentDatabaseLockBackend;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\ProxyClass\Extension\ModuleInstaller;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\StringTranslation\TranslationManager;
use Drupal\Core\Extension\ModuleExtensionList;
use org\bovigo\vfs\vfsStream;
use Drupal\Component\Serialization\Yaml;

use Drupal\config_devel\ConfigImporterExporter;

/**
 * @coversDefaultClass \Drupal\config_devel\ConfigImporterExporter
 * @group config_devel
 */
class ConfigImporterExporterTest extends ConfigDevelTestBase {

  use ProphecyTrait;
  /**
   * Test ConfigImporterExporter::writeBackConfig().
   */
  public function testWriteBackConfig() {
    $config_data = array(
      'id' => $this->randomMachineName(),
      'langcode' => 'en',
      'uuid' => '836769f4-6791-402d-9046-cc06e20be87f',
    );

    $config = $this->createMock('\Drupal\Core\Config\Config');
    $config->expects($this->any())
      ->method('getName')
      ->will($this->returnValue($this->randomMachineName()));
    $config->expects($this->any())
      ->method('get')
      ->will($this->returnValue($config_data));

    $file_names = array(
      vfsStream::url('public://' . $this->randomMachineName() . '.yml'),
      vfsStream::url('public://' . $this->randomMachineName() . '.yml'),
    );

    $configDevelSubscriber = new ConfigImporterExporter(
      $this->configFactory,
      $this->prophesize(StorageInterface::class)->reveal(),
      $this->configManager,
      $this->eventDispatcher,
      $this->prophesize(PersistentDatabaseLockBackend::class)->reveal(),
      $this->prophesize(TypedConfigManagerInterface::class)->reveal(),
      $this->prophesize(ModuleHandlerInterface::class)->reveal(),
      $this->prophesize(ModuleInstaller::class)->reveal(),
      $this->prophesize(ThemeHandlerInterface::class)->reveal(),
      $this->prophesize(TranslationManager::class)->reveal(),
      $this->prophesize(ModuleExtensionList::class)->reveal()
    );

    $configDevelSubscriber->writeBackConfig($config, $file_names);

    $data = $config_data;
    unset($data['uuid']);
    unset($data['_core']);

    foreach ($file_names as $file_name) {
      $this->assertEquals($data, Yaml::decode(file_get_contents($file_name)));
    }
  }

}

if (!defined('DRUPAL_MINIMUM_PHP')) {
  define('DRUPAL_MINIMUM_PHP', '7.3.0');
}
