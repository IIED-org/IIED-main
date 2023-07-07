<?php

declare(strict_types=1);

namespace Drupal\Tests\acquia_search\Unit;

use Drupal\acquia_connector\Settings;
use Drupal\acquia_connector\Subscription;
use Drupal\acquia_search\AcquiaSearchApiClient;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Http\ClientFactory;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Core\Logger\LoggerChannelInterface;

/**
 * @group acquia_search
 */
final class AcquiaSearchApiClientTest extends AcquiaSearchTestCase {

  /**
   * Tests that `false` does not get cached for `getSearchIndexKeys`.
   */
  public function testGetSearchIndexKeysOnInvalid(): void {
    $subscription_settings = $this->createMock(Settings::class);
    $subscription_settings->method('getIdentifier')->willReturn('ABC-123');
    $subscription = $this->createMock(Subscription::class);
    $subscription->method('getSettings')->willReturn($subscription_settings);

    $client_factory = $this->createMock(ClientFactory::class);
    $cache_backend = $this->createMock(CacheBackendInterface::class);
    $cache_backend->expects($this->once())
      ->method('get')
      ->with('acquia_search.indexes.ABC-123.foo')
      ->willReturn(NULL);
    $cache_backend->expects($this->never())
      ->method('set');

    $time = $this->createMock(TimeInterface::class);
    $lock = $this->createMock(LockBackendInterface::class);
    $sut = new AcquiaSearchApiClient(
      $this->createMock(LoggerChannelInterface::class),
      $subscription,
      $client_factory,
      $cache_backend,
      $time,
      $lock
    );
    self::assertFalse($sut->getSearchIndexKeys('foo'));
  }

}
