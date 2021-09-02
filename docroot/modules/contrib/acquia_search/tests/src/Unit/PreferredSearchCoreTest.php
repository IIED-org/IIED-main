<?php

namespace Drupal\Tests\acquia_search\Unit;

use Drupal\acquia_search\Helper\Storage;
use Drupal\acquia_search\PreferredSearchCore;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\State\State;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;

/**
 * @coversDefaultClass \Drupal\acquia_search\PreferredSearchCore
 * @group Acquia Search Solr
 */
class PreferredSearchCoreTest extends UnitTestCase {

  /**
   * The Acquia Search Solr module config.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy
   */
  protected $config;

  /**
   * The Drupal module handler.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy
   */
  protected $moduleHandler;

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

    $config_factory = $this->prophesize(ConfigFactoryInterface::class);
    $config = $this->prophesize(ImmutableConfig::class);
    $config->get('override_search_core')->willReturn(NULL);
    $config->get('read_only')->willReturn(FALSE);
    $this->config = $config;

    $config_factory->get('acquia_search.settings')
      ->willReturn($config->reveal());

    $module_handler = $this->prophesize(ModuleHandlerInterface::class);
    $module_handler->alter(Argument::any(), Argument::any(), Argument::any())->willReturn(NULL);
    $this->moduleHandler = $module_handler;

    $container = new ContainerBuilder();
    $container->set('state', $state->reveal());
    $container->set('config.factory', $config_factory->reveal());
    $container->set('module_handler', $module_handler->reveal());
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
   * Tests non hosted site.
   */
  public function testNonHosted() {

    $storage = new Storage();

    $ah_env = '';
    $sites_folder_name = 'default';
    $ah_db_role = '';
    $available_cores = [];
    $storage->setIdentifier('');
    $core_service = new PreferredSearchCore(Storage::getIdentifier(), $ah_env, $sites_folder_name, $ah_db_role, $available_cores);
    $this->assertEquals([], $core_service->getListOfPossibleCores());
    $this->assertFalse($core_service->isPreferredCoreAvailable());
    $this->assertNull($core_service->getPreferredCore());
    $this->assertNull($core_service->getPreferredCoreId());
    $this->assertNull($core_service->getPreferredCoreHostname());

    $storage->setIdentifier('ABC-12345');
    $this->assertEquals('ABC-12345', Storage::getIdentifier());
    $core_service = new PreferredSearchCore(Storage::getIdentifier(), $ah_env, $sites_folder_name, $ah_db_role, $available_cores);
    $this->assertEquals([], $core_service->getListOfPossibleCores());
    $this->assertEquals([], $core_service->getListOfAvailableCores());
    $this->assertFalse($core_service->isPreferredCoreAvailable());
    $this->assertNull($core_service->getPreferredCore());
    $this->assertNull($core_service->getPreferredCoreId());
    $this->assertNull($core_service->getPreferredCoreHostname());

  }

  /**
   * Tests search core on a dev environment.
   */
  public function testDevSearchCore() {

    $storage = new Storage();

    $ah_env = 'dev';
    $sites_folder_name = 'default';
    $ah_db_role = 'database';
    $storage->setIdentifier('ABC-12345');
    $this->assertEquals('ABC-12345', Storage::getIdentifier());

    $this->config->get('override_search_core')->willReturn(NULL);
    $this->assertNull(Storage::getSearchCoreOverride());
    $this->config->reveal();
    $available_cores = $this->getAvailableSearchCores();

    $core_service = new PreferredSearchCore(Storage::getIdentifier(), $ah_env, $sites_folder_name, $ah_db_role, $available_cores);
    $this->assertEquals([
      'ABC-12345.dev.database',
      'ABC-12345.dev.default',
    ], $core_service->getListOfPossibleCores());
    $this->assertEquals([
      'ABC-12345.prod.default',
      'ABC-12345.dev.default',
    ], $core_service->getListOfAvailableCores());
    $this->assertTrue($core_service->isPreferredCoreAvailable());
    $this->assertEquals($available_cores['ABC-12345.dev.default'], $core_service->getPreferredCore());
    $this->assertEquals('ABC-12345.dev.default', $core_service->getPreferredCoreId());
    $this->assertEquals('dev.example.com', $core_service->getPreferredCoreHostname());

  }

  /**
   * Tests search core for a production environment.
   */
  public function testProductionSearchCore() {

    $storage = new Storage();

    $ah_env = 'prod';
    $sites_folder_name = 'default';
    $ah_db_role = 'database';
    $storage->setIdentifier('ABC-12345');
    $this->assertEquals('ABC-12345', Storage::getIdentifier());

    $this->config->get('override_search_core')->willReturn(NULL);
    $this->assertNull(Storage::getSearchCoreOverride());
    $this->config->reveal();
    $available_cores = $this->getAvailableSearchCores();

    $core_service = new PreferredSearchCore(Storage::getIdentifier(), $ah_env, $sites_folder_name, $ah_db_role, $available_cores);
    $this->assertEquals([
      'ABC-12345.prod.database',
      'ABC-12345.prod.default',
    ], $core_service->getListOfPossibleCores());
    $this->assertEquals([
      'ABC-12345.prod.default',
      'ABC-12345.dev.default',
    ], $core_service->getListOfAvailableCores());
    $this->assertTrue($core_service->isPreferredCoreAvailable());
    $this->assertEquals($available_cores['ABC-12345.prod.default'], $core_service->getPreferredCore());
    $this->assertEquals('ABC-12345.prod.default', $core_service->getPreferredCoreId());
    $this->assertEquals('prod.example.com', $core_service->getPreferredCoreHostname());

  }

  /**
   * Tests the Default Search Core config option.
   *
   * Managed by the override_search_core config value.
   */
  public function testSearchCoreOverride() {

    $storage = new Storage();

    $ah_env = '';
    $sites_folder_name = 'default';
    $ah_db_role = '';

    $storage->setIdentifier('ABC-12345');
    $this->assertEquals('ABC-12345', Storage::getIdentifier());
    $this->config->get('override_search_core')->willReturn('ABC-12345.dev.default');
    $this->assertEquals('ABC-12345.dev.default', Storage::getSearchCoreOverride());
    $this->config->reveal();
    $available_cores = [];
    $core_service = new PreferredSearchCore(Storage::getIdentifier(), $ah_env, $sites_folder_name, $ah_db_role, $available_cores);
    $this->assertEquals(['ABC-12345.dev.default'], $core_service->getListOfPossibleCores());
    $this->assertEquals([], $core_service->getListOfAvailableCores());
    $this->assertFalse($core_service->isPreferredCoreAvailable());
    $this->assertNull($core_service->getPreferredCore());
    $this->assertNull($core_service->getPreferredCoreId());
    $this->assertNull($core_service->getPreferredCoreHostname());

    $available_cores = $this->getAvailableSearchCores();
    $core_service = new PreferredSearchCore(Storage::getIdentifier(), $ah_env, $sites_folder_name, $ah_db_role, $available_cores);
    $this->assertEquals(['ABC-12345.dev.default'], $core_service->getListOfPossibleCores());
    $this->assertEquals([
      'ABC-12345.prod.default',
      'ABC-12345.dev.default',
    ], $core_service->getListOfAvailableCores());
    $this->assertTrue($core_service->isPreferredCoreAvailable());
    $this->assertEquals($available_cores['ABC-12345.dev.default'], $core_service->getPreferredCore());
    $this->assertEquals('ABC-12345.dev.default', $core_service->getPreferredCoreId());
    $this->assertEquals('dev.example.com', $core_service->getPreferredCoreHostname());

  }

  /**
   * Tests hook_acquia_search_get_list_of_possible_cores_alter.
   */
  public function testGetListOfPossibleCoresAlterEmpty() {

    $storage = new Storage();

    $ah_env = '';
    $sites_folder_name = 'default';
    $ah_db_role = '';

    $storage->setIdentifier('');
    $this->assertEquals('', Storage::getIdentifier());
    $this->assertNull(Storage::getSearchCoreOverride());
    $available_cores = [];
    $core_service = new PreferredSearchCore(Storage::getIdentifier(), $ah_env, $sites_folder_name, $ah_db_role, $available_cores);

    $expected = [];
    $context = [
      'ah_env' => '',
      'ah_db_role' => '',
      'identifier' => '',
      'sites_foldername' => 'default',
    ];
    $this->moduleHandler->alter('acquia_search_get_list_of_possible_cores', $expected, $context)
      ->shouldBeCalledOnce();
    $this->moduleHandler->reveal();

    $core_service->getListOfPossibleCores();
  }

  /**
   * Tests hook_acquia_search_get_list_of_possible_cores_alter.
   */
  public function testGetListOfPossibleCoresAlterNonHosted() {

    $storage = new Storage();

    $ah_env = '';
    $sites_folder_name = 'default';
    $ah_db_role = '';

    $storage->setIdentifier('ABC-12345');
    $this->assertEquals('ABC-12345', Storage::getIdentifier());
    $this->assertNull(Storage::getSearchCoreOverride());
    $available_cores = [];
    $core_service = new PreferredSearchCore(Storage::getIdentifier(), $ah_env, $sites_folder_name, $ah_db_role, $available_cores);
    $expected = [];
    $context = [
      'ah_env' => '',
      'ah_db_role' => '',
      'identifier' => 'ABC-12345',
      'sites_foldername' => 'default',
    ];
    $this->moduleHandler->alter('acquia_search_get_list_of_possible_cores', $expected, $context)
      ->shouldBeCalledOnce();
    $this->moduleHandler->reveal();

    $core_service->getListOfPossibleCores();

  }

  /**
   * Tests hook_acquia_search_get_list_of_possible_cores_alter.
   */
  public function testGetListOfPossibleCoresAlterHostedDev() {

    $storage = new Storage();

    $storage->setIdentifier('ABC-12345');
    $this->assertEquals('ABC-12345', Storage::getIdentifier());
    $this->assertNull(Storage::getSearchCoreOverride());

    $ah_env = 'dev';
    $sites_folder_name = 'site_folder_1';
    $ah_db_role = 'site_1_db';
    $available_cores = $this->getAvailableSearchCores();
    $core_service = new PreferredSearchCore(Storage::getIdentifier(), $ah_env, $sites_folder_name, $ah_db_role, $available_cores);

    $expected = ['ABC-12345.dev.site_1_db', 'ABC-12345.dev.sitefolder1'];
    $context = [
      'ah_env' => 'dev',
      'ah_db_role' => 'site_1_db',
      'identifier' => 'ABC-12345',
      'sites_foldername' => 'sitefolder1',
    ];
    $this->moduleHandler->alter('acquia_search_get_list_of_possible_cores', $expected, $context)->shouldBeCalledOnce();
    $this->moduleHandler->reveal();

    $core_service->getListOfPossibleCores();

  }

  /**
   * Helper method to return mocked search cores.
   *
   * @return array
   *   Available search cores.
   */
  protected function getAvailableSearchCores(): array {
    return [
      'ABC-12345.prod.default' => [
        'balancer' => 'prod.example.com',
        'core_id' => 'ABC-12345.prod.default',
        'data' => [
          'key' => 'ABC-12345.prod.default',
          'secret_key' => 'secret_key',
          'product_policies' => ['salt' => 'salt'],
          'host' => 'prod.example.com',
        ],
      ],
      'ABC-12345.dev.default' => [
        'balancer' => 'dev.example.com',
        'core_id' => 'ABC-12345.dev.default',
        'data' => [
          'key' => 'ABC-12345.dev.default',
          'secret_key' => 'secret_key',
          'product_policies' => ['salt' => 'salt'],
          'host' => 'dev.example.com',
        ],
      ],
    ];
  }

}
