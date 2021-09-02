<?php

namespace Drupal\Tests\acquia_search\Unit;

use Drupal\acquia_search\AcquiaSearchApiClient;
use Drupal\acquia_search\Helper\Storage;
use Drupal\Component\Datetime\Time;
use Drupal\Component\Uuid\Php;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Logger\LoggerChannel;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\State\State;
use Drupal\Tests\UnitTestCase;
use GuzzleHttp\Client;
use Prophecy\Argument;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * @coversDefaultClass \Drupal\acquia_search\AcquiaSearchApiClient
 * @group Acquia Search Solr
 */
class AcquiaSearchApiClientTest extends UnitTestCase {

  /**
   * GuzzleHttp Client.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy
   */
  protected $guzzleClient;

  /**
   * Cache backend.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy
   */
  protected $cacheBackend;

  /**
   * {@inheritdoc}
   */
  public function setUp() {

    parent::setUp();

    // \Drupal::state().
    $state = $this->prophesize(State::class);
    $state->set(Argument::type('string'), Argument::type('string'))
      ->will(function ($arguments) use ($state) {
        $state->get($arguments[0], Argument::any())->willReturn($arguments[1]);
      });
    $state->get('acquia_search.version')->willReturn(NULL);

    // \Drupal::time().
    $time = $this->prophesize(Time::class);
    $time->getRequestTime()->willReturn(1234567000000);

    // \Drupal::logger().
    $logger_factory = $this->prophesize(LoggerChannelFactory::class);
    $logger = $this->prophesize(LoggerChannel::class);
    $logger->error(Argument::any(), Argument::any())->willReturn();
    $logger_factory->get('acquia_search')->willReturn($logger->reveal());

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

    $container = new ContainerBuilder();
    $container->set('state', $state->reveal());
    $container->set('datetime.time', $time->reveal());
    $container->set('logger.factory', $logger_factory->reveal());
    $container->set('config.factory', $config_factory->reveal());
    \Drupal::setContainer($container);

    $storage = new Storage();
    $storage->setApiHost('https://example.com');
    $storage->setApiKey('XXXXXXXXXXyyyyyyyyyyXXXXXXXXXXyyyyyyyyyy');
    $storage->setIdentifier('WXYZ-12345');
    $uuid = new Php();
    $storage->setUuid($uuid->generate());

    $json = json_encode([
      [
        'key' => 'WXYZ-12345',
        'secret_key' => 'secret_key',
        'product_policies' => ['salt' => 'salt'],
        'host' => 'example.com',
      ],
      [
        'key' => 'WXYZ-12345.dev.drupal8',
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
    $uri = Storage::getApiHost() . '/v2/index/configure?network_id=' . Storage::getIdentifier();
    $this->guzzleClient->get($uri, Argument::any())->willReturn($response);
    $this->guzzleClient->get(Argument::any(), Argument::any())->willReturn();

    $this->cacheBackend = $this->prophesize(CacheBackendInterface::class);
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
   * Tests call to Acquia Search V3 API.
   */
  public function testAcquiaSearchApiCall() {

    $indexes = [
      'WXYZ-12345' => [
        'balancer' => 'example.com',
        'core_id' => 'WXYZ-12345',
        'data' => [
          'key' => 'WXYZ-12345',
          'secret_key' => 'secret_key',
          'product_policies' => ['salt' => 'salt'],
          'host' => 'example.com',
        ],
      ],
      'WXYZ-12345.dev.drupal8' => [
        'balancer' => 'example.com',
        'core_id' => 'WXYZ-12345.dev.drupal8',
        'data' => [
          'key' => 'WXYZ-12345.dev.drupal8',
          'secret_key' => 'secret_key',
          'product_policies' => ['salt' => 'salt'],
          'host' => 'example.com',
        ],
      ],
    ];

    $auth_info = [
      'host' => Storage::getApiHost(),
      'app_uuid' => Storage::getUuid(),
      'key' => Storage::getApiKey(),
    ];

    $this->assertEquals('WXYZ-12345', Storage::getIdentifier());

    $client = new AcquiaSearchApiClient($auth_info, $this->guzzleClient->reveal(), $this->cacheBackend->reveal());
    // No Network Id.
    $this->assertFalse($client->getSearchIndexes(''));
    $this->cacheBackend->set()->shouldNotBeCalled();
    $this->cacheBackend->get()->shouldNotBeCalled();

    // Invalid Network Id - cache FALSE for minute.
    $randomId = $this->randomMachineName();
    $this->cacheBackend->get('acquia_search.indexes.' . $randomId)->willReturn();
    $this->cacheBackend->set('acquia_search.indexes.' . $randomId, FALSE, \Drupal::time()->getRequestTime() + 60)->willReturn();
    $this->assertFalse($client->getSearchIndexes($randomId));

    // Valid Network Id - cache it for a day.
    $this->cacheBackend->get('acquia_search.indexes.WXYZ-12345')->willReturn();
    $this->cacheBackend->set('acquia_search.indexes.WXYZ-12345', Argument::any(), \Drupal::time()->getRequestTime() + 86400)->willReturn();
    $this->assertEquals($indexes, $client->getSearchIndexes('WXYZ-12345'));
    $this->cacheBackend->get('acquia_search.indexes.WXYZ-12345')->shouldHaveBeenCalledTimes(1);
    $this->cacheBackend->set('acquia_search.indexes.WXYZ-12345', Argument::any(), \Drupal::time()->getRequestTime() + 86400)->shouldHaveBeenCalledTimes(1);

  }

  /**
   * Tests invalidation if cache.
   */
  public function testAcquiaSearchApiCache() {

    $indexes = [
      'WXYZ-12345' => [
        'balancer' => 'example.com',
        'core_id' => 'WXYZ-12345',
        'data' => [
          'key' => 'WXYZ-12345',
          'secret_key' => 'secret_key',
          'product_policies' => ['salt' => 'salt'],
          'host' => 'example.com',
        ],
      ],
      'WXYZ-12345.dev.drupal8' => [
        'balancer' => 'example.com',
        'core_id' => 'WXYZ-12345.dev.drupal8',
        'data' => [
          'key' => 'WXYZ-12345.dev.drupal8',
          'secret_key' => 'secret_key',
          'product_policies' => ['salt' => 'salt'],
          'host' => 'example.com',
        ],
      ],
    ];

    $auth_info = [
      'host' => Storage::getApiHost(),
      'app_uuid' => Storage::getUuid(),
      'key' => Storage::getApiKey(),
    ];

    $this->assertEquals('WXYZ-12345', Storage::getIdentifier());

    $client = new AcquiaSearchApiClient($auth_info, $this->guzzleClient->reveal(), $this->cacheBackend->reveal());

    $fresh_cache = (object) [
      'data' => $indexes,
      'expire' => \Drupal::time()->getRequestTime() + (24 * 60 * 60),
    ];
    $this->cacheBackend->get('acquia_search.indexes.WXYZ-12345')->willReturn($fresh_cache);
    $client->getSearchIndexes('WXYZ-12345');

    // New cache should not have been set when there is already a valid cache.
    $this->cacheBackend->set('acquia_search.indexes.WXYZ-12345', $indexes, \Drupal::time()->getRequestTime() + (24 * 60 * 60))->shouldHaveBeenCalledTimes(0);

    $expired_cache = (object) [
      'data' => $indexes,
      'expire' => 0,
    ];
    $this->cacheBackend->get('acquia_search.indexes.WXYZ-12345')->willReturn($expired_cache);
    $client->getSearchIndexes('WXYZ-12345');

    // When the current cache value is expired, it should have set a new one.
    $this->cacheBackend->set('acquia_search.indexes.WXYZ-12345', $indexes, \Drupal::time()->getRequestTime() + (24 * 60 * 60))->shouldHaveBeenCalledTimes(1);
  }

}
