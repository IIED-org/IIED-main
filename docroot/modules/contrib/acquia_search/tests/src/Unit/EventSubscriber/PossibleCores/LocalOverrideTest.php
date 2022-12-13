<?php

declare(strict_types=1);

namespace Drupal\Tests\acquia_search\Unit\EventSubscriber\PossibleCores;

use Drupal\acquia_connector\Subscription;
use Drupal\acquia_search\Event\AcquiaPossibleCoresEvent;
use Drupal\acquia_search\EventSubscriber\PossibleCores\LocalOverride;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Site\Settings;
use Drupal\Tests\acquia_search\Unit\AcquiaSearchTestCase;

/**
 * @group acquia_search
 * @coversDefaultClass \Drupal\acquia_search\EventSubscriber\PossibleCores\LocalOverride
 */
final class LocalOverrideTest extends AcquiaSearchTestCase {

  public function testNothingIfProviderIsAcquiaCloud(): void {
    $subscription = $this->createMock(Subscription::class);
    $subscription->expects($this->once())
      ->method('getProvider')
      ->willReturn('acquia_cloud');

    $sut = new LocalOverride(
      $subscription,
      $this->createConfigFactoryMock('', ''),
      $this->createMock(MessengerInterface::class)
    );
    $sut->onGetPossibleCores(new AcquiaPossibleCoresEvent([]));
  }

  /**
   * @dataProvider configData
   */
  public function testOnGetPossibleCores(string $config_override_core, string $config_solr_override_core, string $settings_override_core, array $expected): void {
    $subscription = $this->createMock(Subscription::class);
    $subscription->expects($this->once())
      ->method('getProvider')
      ->willReturn('core_state');
    $subscription->expects($this->never())
      ->method('getSettings');

    new Settings([
      'acquia_search' => [
        'override_search_core' => $settings_override_core,
      ],
    ]);

    $messenger = $this->createMock(MessengerInterface::class);
    if ($config_override_core !== '' || $config_solr_override_core !== '') {
      $messenger->expects($this->once())
        ->method('addWarning');
    }
    else {
      $messenger->expects($this->never())
        ->method('addWarning');
    }

    $sut = new LocalOverride(
      $subscription,
      $this->createConfigFactoryMock($config_override_core, $config_solr_override_core),
      $messenger
    );
    $event = new AcquiaPossibleCoresEvent([]);
    $sut->onGetPossibleCores($event);
    self::assertEquals($expected, $event->getPossibleCores());
  }

  public function configData() {
    yield [
      'foo',
      'bar',
      'baz',
      ['foo', 'bar', 'baz'],
    ];
    yield [
      '',
      'bar',
      'baz',
      ['bar', 'baz'],
    ];
    yield [
      '',
      '',
      'baz',
      ['baz'],
    ];
  }

  /**
   * @testWith [true]
   *           [false]
   */
  public function testWithOnlyReadOnly(bool $readonly): void {
    $subscription = $this->createMock(Subscription::class);
    $subscription->expects($this->once())
      ->method('getProvider')
      ->willReturn('core_state');
    $subscription->expects($this->never())
      ->method('getSettings');

    new Settings([
      'acquia_search' => [
        'read_only' => $readonly,
      ],
    ]);

    $sut = new LocalOverride(
      $subscription,
      $this->createConfigFactoryMock('', ''),
      $this->createMock(MessengerInterface::class)
    );
    $event = new AcquiaPossibleCoresEvent([]);
    $sut->onGetPossibleCores($event);
    self::assertEquals([], $event->getPossibleCores());
    self::assertEquals($readonly, $event->isReadOnly());
  }

  private function createConfigFactoryMock(string $override_core, string $solr_override_core): ConfigFactoryInterface {
    $search_settings = $this->createMock(ImmutableConfig::class);
    $search_settings
      ->method('get')
      ->with('override_search_core')
      ->willReturn($override_core);
    $search_solr_settings = $this->createMock(ImmutableConfig::class);
    $search_solr_settings
      ->method('get')
      ->with('override_search_core')
      ->willReturn($solr_override_core);
    $config_factory = $this->createMock(ConfigFactoryInterface::class);
    $config_factory
      ->method('get')
      ->willReturnMap([
        ['acquia_search.settings', $search_settings],
        ['acquia_search_solr.settings', $search_solr_settings],
      ]);
    return $config_factory;
  }

}
