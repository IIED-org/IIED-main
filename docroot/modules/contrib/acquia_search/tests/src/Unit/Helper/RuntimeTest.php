<?php

namespace Drupal\Tests\acquia_search\Unit\Helper;

use Drupal\acquia_search\Helper\Runtime;
use Drupal\acquia_search\Helper\Storage;
use Drupal\Component\Datetime\Time;
use Drupal\Component\Uuid\Php;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\State\State;
use Drupal\search_api\Entity\Server;
use Drupal\Tests\UnitTestCase;
use GuzzleHttp\Client;
use Prophecy\Argument;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * @coversDefaultClass \Drupal\acquia_search\Helper\Runtime
 * @group Acquia Search Solr
 */
class RuntimeTest extends UnitTestCase {

  /**
   * Cache backend.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy
   */
  protected $cacheBackend;

  /**
   * The Acquia Search Solr module config.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy
   */
  protected $config;

  /**
   * GuzzleHttp Client.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy
   */
  protected $guzzleClient;

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
    $config->get('api_host')->willReturn('https://example.com');
    $config_editable = $this->prophesize(Config::class);
    $config_editable->set('api_host', 'https://example.com')->willReturn($config_editable->reveal());
    $config_editable->save()->willReturn($config_editable);
    $config_factory->get('acquia_search.settings')->willReturn($config->reveal());
    $config_factory->getEditable('acquia_search.settings')->willReturn($config_editable->reveal());
    $this->config = $config;

    $config_factory->get('acquia_search.settings')
      ->willReturn($config->reveal());

    // \Drupal::time().
    $time = $this->prophesize(Time::class);
    $time->getRequestTime()->willReturn(1234567000000);

    $module_handler = $this->prophesize(ModuleHandlerInterface::class);
    $this->moduleHandler = $module_handler;

    $container = new ContainerBuilder();
    $container->set('config.factory', $config_factory->reveal());
    $container->set('datetime.time', $time->reveal());
    $container->set('state', $state->reveal());
    $container->set('site.path', 'sites/default');
    $container->set('module_handler', $module_handler->reveal());
    \Drupal::setContainer($container);

    $storage = new Storage();
    $storage->setApiHost('https://example.com');
    $storage->setApiKey('XXXXXXXXXXyyyyyyyyyyXXXXXXXXXXyyyyyyyyyy');
    $storage->setIdentifier('ABC-12345');
    $uuid = new Php();
    $storage->setUuid($uuid->generate());

    $json = json_encode([
      [
        'key' => 'ABC-12345.prod.default',
        'secret_key' => 'secret_key',
        'product_policies' => ['salt' => 'salt'],
        'host' => 'example.com',
      ],
      [
        'key' => 'ABC-12345.dev.drupal8',
        'secret_key' => 'secret_key',
        'product_policies' => ['salt' => 'salt'],
        'host' => 'example.com',
      ],
    ]);
    $stream = $this->prophesize(StreamInterface::class);
    $stream->getSize()->willReturn(1000);
    $stream->read(1000)->willReturn($json);

    $response = $this->prophesize(ResponseInterface::class);
    $response->getStatusCode()->willReturn(200);
    $response->getBody()->willReturn($stream);

    $this->guzzleClient = $this->prophesize(Client::class);
    $this->guzzleClient->get(Argument::type('string'), Argument::any())->willReturn($response);
    $this->guzzleClient->get(Argument::any(), Argument::any())->willReturn();

    $container->set('http_client', $this->guzzleClient->reveal());

    $this->cacheBackend = $this->prophesize(CacheBackendInterface::class);
    $container->set('cache.default', $this->cacheBackend->reveal());

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
   * Tests shouldEnforceReadOnlyMode.
   *
   * @covers \Drupal\acquia_search\Helper\Runtime::shouldEnforceReadOnlyMode
   */
  public function testReadOnlyMode() {

    $storage = new Storage();
    $storage->setIdentifier('ABC-12345');

    $this->config->get('read_only')->willReturn(TRUE);
    $this->config->reveal();

    $this->moduleHandler->alter('acquia_search_should_enforce_read_only', Argument::exact(TRUE))
      ->shouldBeCalledOnce();
    $this->moduleHandler->reveal();

    $this->assertTrue(Runtime::shouldEnforceReadOnlyMode());

    $this->config->get('read_only')->willReturn(FALSE);
    $this->config->reveal();

    $this->moduleHandler->alter('acquia_search_should_enforce_read_only', Argument::exact(FALSE))
      ->shouldBeCalledOnce();
    $this->moduleHandler->reveal();

    $this->assertFalse(Runtime::shouldEnforceReadOnlyMode());

  }

  /**
   * Tests isAcquiaServer.
   *
   * @covers \Drupal\acquia_search\Helper\Runtime::isAcquiaServer
   */
  public function testIsAcquiaServer() {

    $server = $this->prophesize(Server::class);
    $server->getBackendConfig()->willReturn([]);
    $this->assertFalse(Runtime::isAcquiaServer($server->reveal()));
    $server->getBackendConfig()->willReturn(['connector']);
    $this->assertFalse(Runtime::isAcquiaServer($server->reveal()));
    $server->getBackendConfig()->willReturn(['connector' => NULL]);
    $this->assertFalse(Runtime::isAcquiaServer($server->reveal()));
    $server->getBackendConfig()->willReturn(['connector' => FALSE]);
    $this->assertFalse(Runtime::isAcquiaServer($server->reveal()));
    $server->getBackendConfig()->willReturn(['connector' => 0]);
    $this->assertFalse(Runtime::isAcquiaServer($server->reveal()));
    $server->getBackendConfig()->willReturn(['connector' => 1]);
    $this->assertFalse(Runtime::isAcquiaServer($server->reveal()));
    $server->getBackendConfig()->willReturn(['connector' => '0']);
    $this->assertFalse(Runtime::isAcquiaServer($server->reveal()));
    $server->getBackendConfig()->willReturn(['connector' => '1']);
    $this->assertFalse(Runtime::isAcquiaServer($server->reveal()));
    $server->getBackendConfig()->willReturn(['connector' => 'solr_acquia_connector']);
    $this->assertTrue(Runtime::isAcquiaServer($server->reveal()));

  }

  /**
   * Tests getAhDatabaseRole.
   *
   * @dataProvider getAhDatabaseRoleDataProvider
   *
   * @covers \Drupal\acquia_search\Helper\Runtime::getAhDatabaseRole
   */
  public function testGetAhDatabaseRole($options, $connection_ifno, $expected) {
    $role = Runtime::getAhDatabaseRole($options, $connection_ifno);
    $this->assertEquals($expected, $role);
  }

  /**
   * Data provider for testGetAhDatabaseRole test case.
   *
   * @return array
   *   Data sets.
   */
  public function getAhDatabaseRoleDataProvider() {
    return [
      [
        ['database' => 'acquia_search_8'],
        [
          'default' => [
            'default' => [
              'database' => 'acquia_search_8',
            ],
          ],
          'role_name_with_underscores' => [
            'default' => [
              'database' => 'acquia_search_8',
            ],
          ],
        ],
        'role_name_with_underscores',
      ],
      [
        ['database' => 'acquia_search_8'],
        [
          'default' => [
            'default' => [
              'database' => 'acquia_search_8',
            ],
          ],
          '_role_name_with_underscores' => [
            'default' => [
              'database' => 'acquia_search_8',
            ],
          ],
        ],
        '_role_name_with_underscores',
      ],
      [
        ['database' => 'acquia_search_8'],
        [
          'default' => [
            'default' => [
              'database' => 'acquia_search_8',
            ],
          ],
          '_role_nam__e_with_underscores__' => [
            'default' => [
              'database' => 'acquia_search_8',
            ],
          ],
        ],
        '_role_nam__e_with_underscores__',
      ],
      [
        ['database' => 'acquia_search_8'],
        [
          'default' => [
            'default' => [
              'database' => 'acquia_search_8',
            ],
          ],
          'wi\th//s  p\e?c..i}}a{l-c+h)ar(*s' => [
            'default' => [
              'database' => 'acquia_search_8',
            ],
          ],
        ],
        'withspecialchars',
      ],
      [
        ['database' => 'acquia_search_8'],
        [
          'default' => [
            'default' => [
              'database' => 'acquia_search_8',
            ],
          ],
        ],
        '',
      ],
    ];
  }

}
