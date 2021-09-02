<?php

namespace Drupal\Tests\acquia_search\Unit\Helper;

use Drupal\acquia_search\Helper\Storage;
use Drupal\Component\Uuid\Php;
use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\State\State;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;

/**
 * @coversDefaultClass \Drupal\acquia_search\Helper\Storage
 * @group Acquia Search Solr
 */
class StorageTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $state = $this->prophesize(State::class);
    $state->set(Argument::type('string'), Argument::type('string'))
      ->will(function ($arguments) use ($state) {
        $state->get($arguments[0], Argument::any())
          ->willReturn($arguments[1]);
      });
    $state->get('acquia_search.version')->willReturn(NULL);
    $state->deleteMultiple([
      'acquia_search.api_key',
      'acquia_search.identifier',
      'acquia_search.uuid',
      'acquia_search.version',
    ])->willReturn();

    $config_factory = $this->prophesize(ConfigFactoryInterface::class);
    $config = $this->prophesize(ImmutableConfig::class);
    $config->get('override_search_core')->willReturn('ABC-12345.env.db');
    $config->get('read_only')->willReturn(TRUE);
    $config->get('api_host')->willReturn('https://example.com');
    $config->get('extract_query_handler_option')->willReturn('some/value');
    $config_editable = $this->prophesize(Config::class);
    $config_editable->set('api_host', 'https://example.com')->willReturn($config_editable->reveal());
    $config_editable->save()->willReturn($config_editable);
    $config_factory->get('acquia_search.settings')->willReturn($config->reveal());
    $config_factory->getEditable('acquia_search.settings')->willReturn($config_editable->reveal());

    $config_factory->get('acquia_search.settings')
      ->willReturn($config->reveal());

    $entension_list_module = $this->prophesize(ModuleExtensionList::class);
    $entension_list_module->getExtensionInfo('acquia_search')->willReturn(['version' => 'testing-3.x']);

    $container = new ContainerBuilder();
    $container->set('state', $state->reveal());
    $container->set('config.factory', $config_factory->reveal());
    $container->set('extension.list.module', $entension_list_module->reveal());
    \Drupal::setContainer($container);
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown() {
    parent::tearDown();
    $container = new ContainerBuilder();
    \Drupal::setContainer($container);
  }

  /**
   * Tests storage.
   */
  public function testStorage() {

    $storage = new Storage();

    $host = 'https://example.com';
    $storage->setApiHost($host);
    $this->assertEquals($host, Storage::getApiHost());

    $key = $this->randomMachineName(20);
    $storage->setApiKey($key);
    $this->assertEquals($key, Storage::getApiKey());

    $id = $this->randomMachineName();
    $storage->setIdentifier($id);
    $this->assertEquals($id, Storage::getIdentifier());

    $uuid = new Php();
    $uuid = $uuid->generate();
    $storage->setUuid($uuid);
    $this->assertEquals($uuid, Storage::getUuid());

    $this->assertEquals(Storage::getVersion(), 'testing-3.x');

    $storage->deleteAllData();

    $this->assertEquals('ABC-12345.env.db', Storage::getSearchCoreOverride());
    $this->assertTrue(Storage::isReadOnly());

    $this->assertEquals($storage->getExtractQueryHandlerOption(), 'some/value');

  }

}

if (!defined('DRUPAL_MINIMUM_PHP')) {
  define('DRUPAL_MINIMUM_PHP', '7.3.0');
}
