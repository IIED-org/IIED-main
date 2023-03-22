<?php

declare(strict_types=1);

namespace Drupal\Tests\acquia_search\Unit\EventSubscriber\PossibleCores;

use Drupal\acquia_connector\Subscription;
use Drupal\acquia_search\Event\AcquiaPossibleCoresEvent;
use Drupal\acquia_search\EventSubscriber\PossibleCores\LocalOverride;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Site\Settings;
use Drupal\Tests\acquia_search\Unit\AcquiaSearchTestCase;

/**
 * @group acquia_search
 * @coversDefaultClass \Drupal\acquia_search\EventSubscriber\PossibleCores\LocalOverride
 */
final class LocalOverrideTest extends AcquiaSearchTestCase {

  public function testIsCalledIfProviderIsAcquiaCloud(): void {
    $subscription = $this->createMock(Subscription::class);
    $subscription->expects($this->never())
      ->method('getProvider');

    $sut = new LocalOverride(
      $subscription,
      $this->createConfigFactoryMock('', ''),
      $this->createMock(RouteMatchInterface::class)
    );
    $sut->onGetPossibleCores(new AcquiaPossibleCoresEvent([]));
  }

  /**
   * @dataProvider configData
   */
  public function testOnGetPossibleCores(string $config_override_core, string $config_solr_override_core, string $settings_override_core, array $expected): void {
    $subscription = $this->createMock(Subscription::class);
    $subscription->expects($this->never())
      ->method('getProvider');
    $subscription->expects($this->never())
      ->method('getSettings');

    if ($settings_override_core !== '') {
      new Settings([
        'acquia_search' => [
          'override_search_core' => $settings_override_core,
        ],
      ]);
    }

    $sut = new LocalOverride(
      $subscription,
      $this->createConfigFactoryMock($config_override_core, $config_solr_override_core)
    );
    $event = new AcquiaPossibleCoresEvent([]);
    $sut->onGetPossibleCores($event);
    self::assertEquals($expected, $event->getPossibleCores());
    self::assertEquals(FALSE, $event->isReadOnly());
  }

  public function configData() {
    yield 'config_override_core foo' => [
      'foo',
      '',
      '',
      ['foo'],
    ];
    yield 'config_solr_override_core bar' => [
      '',
      'bar',
      '',
      ['bar'],
    ];
    yield 'settings_override_core baz' => [
      '',
      '',
      'baz',
      ['baz'],
    ];
    yield 'config_override_core foo settings_override_core baz' => [
      'foo',
      '',
      'baz',
      ['foo', 'baz'],
    ];
    yield 'config_solr_override_core bar settings_override_core baz' => [
      '',
      'bar',
      'baz',
      ['bar', 'baz'],
    ];
  }

  /**
   * @testWith [true]
   *           [false]
   */
  public function testWithOnlyReadOnly(bool $readonly): void {
    $subscription = $this->createMock(Subscription::class);
    $subscription->expects($this->never())
      ->method('getProvider');
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
      $this->createMock(MessengerInterface::class),
      $this->createMock(RouteMatchInterface::class)
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
