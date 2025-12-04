<?php

declare(strict_types=1);

namespace Drupal\Tests\acquia_search\Unit;

use Drupal\acquia_connector\Settings;
use Drupal\acquia_connector\Subscription;
use Drupal\acquia_search\AcquiaSearchApiClient;
use Drupal\acquia_search\AcquiaSearchEvents;
use Drupal\acquia_search\Event\AcquiaPossibleCoresEvent;
use Drupal\acquia_search\PreferredCoreServiceFactory;
use Drupal\Component\Datetime\Time;
use Drupal\Component\Serialization\Json;
use Drupal\Component\Uuid\Php as PhpUuid;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Http\ClientFactory;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @group acquia_search
 * @group orca_public
 * @coversDefaultClass \Drupal\acquia_search\PreferredCoreService
 */
final class PreferredCoreServiceTest extends AcquiaSearchTestCase {

  private function createSubscription(): Subscription {
    $subscription = $this->createMock(Subscription::class);
    $settings = $this->createMock(Settings::class);
    $settings->method('getIdentifier')->willReturn('ABC123');
    $settings->method('getSecretKey')->willReturn('FooBar');
    $settings->method('getApplicationUuid')->willReturn((new PhpUuid())->generate());
    $subscription->method('getSettings')->willReturn($settings);
    $subscription->method('isActive')->willReturn(TRUE);
    $subscription->method('getSubscription')->willReturn([
      'acquia_search' => [
        'api_host' => 'https://localhost',
      ],
    ]);
    return $subscription;
  }

  private function createApiClient(Subscription $subscription, ClientFactory $client_factory): AcquiaSearchApiClient {
    $lock = $this->createMock(LockBackendInterface::class);
    $lock->method('acquire')
      ->with('acquia_search_get_search_indexes')
      ->willReturn(TRUE);
    $cache_default = $this->getMockBuilder('Drupal\Core\Cache\MemoryBackend')->disableOriginalConstructor()->getMock();

    // Effectively disable the memory cache.
    $cache_default->method('get')->willReturn(FALSE);

    return new AcquiaSearchApiClient(
      $this->createMock(LoggerChannelInterface::class),
      $subscription,
      $client_factory,
      $cache_default,
      new Time(new RequestStack()),
      $lock
    );
  }

  /**
   * @dataProvider availableCoresData
   */
  public function testGetListOfAvailableCores(array $indexes, array $expected): void {
    drupal_static_reset('acquia_search_available_cores');
    $client = new Client([
      'handler' => HandlerStack::create(
        new MockHandler([
          new Response(200, [], Json::encode($indexes)),
        ])
      ),
    ]);
    $client_factory = $this->createMock(ClientFactory::class);
    $client_factory->method('fromOptions')->willReturn($client);

    $subscription = $this->createSubscription();
    $acquia_search_api_client = $this->createApiClient($subscription, $client_factory);
    $sut = new PreferredCoreServiceFactory(
      $this->createMock(EventDispatcherInterface::class),
      $subscription,
      $acquia_search_api_client,
      $this->createMock(ModuleHandlerInterface::class)
    );
    self::assertEquals(array_keys($expected), $sut->get('foobar')->getListOfAvailableCores());
  }

  /**
   * @dataProvider availableCoresData
   */
  public function testGetAvailableCores(array $indexes, array $expected): void {
    $client = new Client([
      'handler' => HandlerStack::create(
        new MockHandler([
          new Response(200, [], Json::encode($indexes)),
        ])
      ),
    ]);
    $client_factory = $this->createMock(ClientFactory::class);
    $client_factory->method('fromOptions')->willReturn($client);

    $subscription = $this->createSubscription();
    $acquia_search_api_client = $this->createApiClient($subscription, $client_factory);
    $sut = new PreferredCoreServiceFactory(
      $this->createMock(EventDispatcherInterface::class),
      $subscription,
      $acquia_search_api_client,
      $this->createMock(ModuleHandlerInterface::class)
    );
    self::assertEquals($expected, $sut->get('foobar')->getAvailableCores());
  }

  public function testGetListOfPossibleCores(): void {
    $client = new Client([
      'handler' => HandlerStack::create(
        new MockHandler()
      ),
    ]);
    $client_factory = $this->createMock(ClientFactory::class);
    $client_factory->method('fromOptions')->willReturn($client);

    $event_dispatcher = new EventDispatcher();
    $event_dispatcher->addListener(
      AcquiaSearchEvents::GET_POSSIBLE_CORES,
      function (AcquiaPossibleCoresEvent $event) {
        $event->addPossibleCore('FooBarBaz');
        $event->setReadOnly(FALSE);
      }
    );

    $subscription = $this->createSubscription();
    $acquia_search_api_client = $this->createApiClient($subscription, $client_factory);
    $sut = new PreferredCoreServiceFactory(
      $event_dispatcher,
      $subscription,
      $acquia_search_api_client,
      $this->createMock(ModuleHandlerInterface::class)
    );
    self::assertEquals(['FooBarBaz'], $sut->get('foobar')->getListOfPossibleCores());
  }

  /**
   * @dataProvider availableCoresData
   */
  public function testGetPreferredCore(array $indexes, array $expected): void {
    $client = new Client([
      'handler' => HandlerStack::create(
        new MockHandler([
          new Response(200, [], Json::encode($indexes)),
        ])
      ),
    ]);
    $client_factory = $this->createMock(ClientFactory::class);
    $client_factory->method('fromOptions')->willReturn($client);

    $event_dispatcher = new EventDispatcher();
    $event_dispatcher->addListener(
      AcquiaSearchEvents::GET_POSSIBLE_CORES,
      function (AcquiaPossibleCoresEvent $event) {
        $event->addPossibleCore('FooBarBaz');
        $event->setReadOnly(FALSE);
      }
    );

    $subscription = $this->createSubscription();

    $acquia_search_api_client = $this->createApiClient($subscription, $client_factory);
    $factory = new PreferredCoreServiceFactory(
      $event_dispatcher,
      $subscription,
      $acquia_search_api_client,
      $this->createMock(ModuleHandlerInterface::class)
    );
    $sut = $factory->get('foobar');
    $result = $sut->getPreferredCore();
    if (count($indexes) === 0) {
      self::assertNull($result);
      self::assertFalse($sut->isPreferredCoreAvailable());
      self::assertNull($sut->getPreferredCoreHostname());
    }
    else {
      self::assertArrayHasKey($result['core_id'], $expected);
      self::assertEquals($expected[$result['core_id']], $result);
      self::assertTrue($sut->isPreferredCoreAvailable());
      self::assertEquals(
        $expected[$result['core_id']]['balancer'],
        $sut->getPreferredCoreHostname()
      );
      self::assertFalse($sut->isReadOnly());
    }
  }

  public static function availableCoresData() {
    yield [[], []];
    yield [
      [
        'data' => [
          [
            'id' => 'FooBarBaz',
            'attributes' => [
              'url' => 'https://localhost:443/solr/FooBarBaz/',
            ],
          ],
        ],
      ],
      [
        'FooBarBaz' => [
          'balancer' => 'localhost',
          'core_id' => 'FooBarBaz',
          'data' => [
            'id' => 'FooBarBaz',
            'attributes' => [
              'url' => 'https://localhost:443/solr/FooBarBaz/',
            ],
          ],
        ],
      ],
    ];
  }

}
