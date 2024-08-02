<?php

declare(strict_types=1);

namespace Drupal\Tests\acquia_search\Unit\EventSubscriber\PossibleCores;

use Drupal\acquia_connector\Settings;
use Drupal\acquia_connector\Subscription;
use Drupal\acquia_search\AcquiaSearchApiClient;
use Drupal\acquia_search\Event\AcquiaPossibleCoresEvent;
use Drupal\acquia_search\EventSubscriber\PossibleCores\DefaultCore;
use Drupal\Tests\acquia_search\Unit\AcquiaSearchTestCase;

/**
 * @group acquia_search
 * @coversDefaultClass \Drupal\acquia_search\EventSubscriber\PossibleCores\DefaultCore
 */
final class DefaultCoreTest extends AcquiaSearchTestCase {

  public function testNothingIfProviderIsAcquiaCloud(): void {
    $subscription = $this->createMock(Subscription::class);
    $subscription->expects($this->once())
      ->method('getProvider')
      ->willReturn('acquia_cloud');
    $subscription->expects($this->never())
      ->method('hasCredentials');

    $sut = new DefaultCore(
      $subscription,
      $this->createMock(AcquiaSearchApiClient::class),
      'sites/default'
    );
    $sut->onGetPossibleCores(new AcquiaPossibleCoresEvent('foobar', []));
  }

  public function testNothingIfNoSubscriptionCredentials(): void {
    $subscription = $this->createMock(Subscription::class);
    $subscription->expects($this->once())
      ->method('getProvider')
      ->willReturn('core_state');
    $subscription->expects($this->once())
      ->method('hasCredentials')
      ->willReturn(FALSE);

    $client = $this->createMock(AcquiaSearchApiClient::class);
    $client->expects($this->never())
      ->method('getSearchIndexes');

    $sut = new DefaultCore(
      $subscription,
      $client,
      'sites/default'
    );
    $sut->onGetPossibleCores(new AcquiaPossibleCoresEvent('foobar', []));
  }

  public function testNothingIfNoIndexes(): void {
    $subscription = $this->createMock(Subscription::class);
    $subscription->expects($this->once())
      ->method('getProvider')
      ->willReturn('core_state');
    $subscription->expects($this->once())
      ->method('hasCredentials')
      ->willReturn(TRUE);
    $subscription->expects($this->never())
      ->method('getSettings');

    $client = $this->createMock(AcquiaSearchApiClient::class);
    $client->expects($this->once())
      ->method('getSearchIndexes')
      ->willReturn(FALSE);

    $sut = new DefaultCore(
      $subscription,
      $client,
      'sites/default'
    );
    $sut->onGetPossibleCores(new AcquiaPossibleCoresEvent('foobar', []));
  }

  /**
   * @dataProvider searchIndexesData
   */
  public function testOnGetPossibleCores(array $indexes, array $expected): void {
    $subscription = $this->createMock(Subscription::class);
    $subscription->expects($this->once())
      ->method('getProvider')
      ->willReturn('core_state');
    $subscription->expects($this->once())
      ->method('hasCredentials')
      ->willReturn(TRUE);

    $settings = $this->createMock(Settings::class);
    $settings->expects($this->once())
      ->method('getIdentifier')
      ->willReturn('abc123');

    $subscription->expects($this->once())
      ->method('getSettings')
      ->willReturn($settings);

    $client = $this->createMock(AcquiaSearchApiClient::class);
    $client->expects($this->once())
      ->method('getSearchIndexes')
      ->willReturn($indexes);

    $sut = new DefaultCore(
      $subscription,
      $client,
      'sites/default'
    );
    $event = new AcquiaPossibleCoresEvent('foobar', []);
    $sut->onGetPossibleCores($event);
    self::assertEquals($expected, $event->getPossibleCores());
  }

  public static function searchIndexesData() {
    yield [
      [],
      [],
    ];
    yield [
      [
        'foobarprodbaz' => ['core_id' => 'foobarprodbaz'],
        'prod_foobarbaz' => ['core_id' => 'prod_foobarbaz'],
      ],
      ['foobarprodbaz'],
    ];
    yield [
      [
        'foobarprodbaz' => ['core_id' => 'foobarprodbaz'],
        'prod_foobarbaz' => ['core_id' => 'prod_foobarbaz'],
        'abc123.prod' => ['core_id' => 'abc123.prod'],
      ],
      ['abc123.prod'],
    ];
    yield [
      [
        'foobarprodbaz' => ['core_id' => 'foobarprodbaz'],
        'prod_foobarbaz' => ['core_id' => 'prod_foobarbaz'],
        // @todo should generic prod have priority over site name?
        // 'abc123.prod' => ['core_id' => 'abc123.prod'],
        'abc123.prod.othersite' => ['core_id' => 'abc123.prod.othersite'],
        'abc123.prod.default' => ['core_id' => 'abc123.prod.default'],
      ],
      ['abc123.prod.default'],
    ];
  }

}
