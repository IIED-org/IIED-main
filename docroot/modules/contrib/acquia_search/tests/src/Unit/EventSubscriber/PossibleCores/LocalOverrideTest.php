<?php

declare(strict_types=1);

namespace Drupal\Tests\acquia_search\Unit\EventSubscriber\PossibleCores;

use Drupal\acquia_connector\Subscription;
use Drupal\acquia_search\Event\AcquiaPossibleCoresEvent;
use Drupal\acquia_search\EventSubscriber\PossibleCores\LocalOverride;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
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
      $this->createConfigFactoryMock('', '')
    );
    $sut->onGetPossibleCores(new AcquiaPossibleCoresEvent('foobar', []));
  }

  /**
   * @dataProvider configData
   */
  public function testOnGetPossibleCores(string $config_override_core, string $config_solr_override_core, string $settings_override_core, array $server_overrides, array $expected): void {
    $subscription = $this->createMock(Subscription::class);
    $subscription->expects($this->never())
      ->method('getProvider');
    $subscription->expects($this->never())
      ->method('getSettings');

    $settings = [
      'acquia_search' => [],
    ];

    if ($settings_override_core !== '') {
      $settings['acquia_search']['override_search_core'] = $settings_override_core;
    }
    if ($server_overrides !== []) {
      $settings['acquia_search']['server_overrides'] = $server_overrides;
    }
    new Settings($settings);

    $sut = new LocalOverride(
      $subscription,
      $this->createConfigFactoryMock($config_override_core, $config_solr_override_core)
    );
    $event = new AcquiaPossibleCoresEvent('foobar', []);
    $sut->onGetPossibleCores($event);
    self::assertEquals($expected, $event->getPossibleCores());
    self::assertEquals(FALSE, $event->isReadOnly());
  }

  public static function configData() {
    yield 'config_override_core foo' => [
      'foo',
      '',
      '',
      [],
      ['foo'],
    ];
    yield 'config_solr_override_core bar' => [
      '',
      'bar',
      '',
      [],
      ['bar'],
    ];
    yield 'settings_override_core baz' => [
      '',
      '',
      'baz',
      [],
      ['baz'],
    ];
    yield 'config_override_core foo settings_override_core baz' => [
      'foo',
      '',
      'baz',
      [],
      ['foo', 'baz'],
    ];
    yield 'config_solr_override_core bar settings_override_core baz' => [
      '',
      'bar',
      'baz',
      [],
      ['bar', 'baz'],
    ];
    yield 'server overrides' => [
      '',
      '',
      '',
      [
        'foobar' => 'baz',
      ],
      ['baz'],
    ];
    yield 'server_overrides not set' => [
      '',
      '',
      '',
      [
        'bazBar' => 'baz',
      ],
      [],
    ];
    yield 'server_overrides precedence over settings_override_core' => [
      '',
      '',
      'bar',
      [
        'foobar' => 'baz',
      ],
      ['baz'],
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
      $this->createConfigFactoryMock('', '')
    );
    $event = new AcquiaPossibleCoresEvent('foobar', []);
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
