<?php

declare(strict_types=1);

namespace Drupal\Tests\acquia_search\Unit\EventSubscriber\PossibleCores;

use Drupal\acquia_connector\Settings;
use Drupal\acquia_connector\Subscription;
use Drupal\acquia_search\Event\AcquiaPossibleCoresEvent;
use Drupal\acquia_search\EventSubscriber\PossibleCores\AcquiaHosting;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Database;
use Drupal\Tests\acquia_search\Unit\AcquiaSearchTestCase;

/**
 * @group acquia_search
 * @coversDefaultClass \Drupal\acquia_search\EventSubscriber\PossibleCores\AcquiaHosting
 */
final class AcquiaHostingTest extends AcquiaSearchTestCase {

  protected function setUp(): void {
    parent::setUp();
    foreach (array_keys(Database::getAllConnectionInfo()) as $key) {
      Database::removeConnection($key);
    }
  }

  public function testNothingIfProviderIsNotAcquiaCloud(): void {
    $subscription = $this->createMock(Subscription::class);
    $subscription->expects($this->once())
      ->method('getProvider')
      ->willReturn('core_state');
    $subscription->expects($this->never())
      ->method('getSettings');

    $sut = new AcquiaHosting(
      $this->createMock(Connection::class),
      $subscription,
      'foobar'
    );
    $sut->onGetPossibleCores(new AcquiaPossibleCoresEvent([]));
  }

  /**
   * @dataProvider acquiaCloudInfo
   */
  public function testOnGetPossibleCores(string $ah_environment, string $connection_db_name, string $ah_db_role, array $expected): void {
    $subscription = $this->createMock(Subscription::class);
    $subscription->expects($this->once())
      ->method('getProvider')
      ->willReturn('acquia_cloud');

    $settings = $this->createMock(Settings::class);
    $settings->method('getMetadata')
      ->with('AH_SITE_ENVIRONMENT')
      ->willReturn($ah_environment);
    $settings->method('getIdentifier')
      ->willReturn('abc123');
    $subscription->method('getSettings')
      ->willReturn($settings);

    $database = $this->createMock(Connection::class);
    $database->method('getConnectionOptions')
      ->willReturn([
        // This is the only key needed.
        'database' => $connection_db_name,
      ]);
    Database::addConnectionInfo('default', 'default', [
      'driver' => 'mysql',
      'database' => $connection_db_name,
    ]);
    if ($ah_db_role !== '') {
      Database::addConnectionInfo('sitename', 'default', [
        'driver' => 'mysql',
        'database' => $ah_db_role,
      ]);
    }

    $sut = new AcquiaHosting(
      $database,
      $subscription,
      'sites/default'
    );
    $event = new AcquiaPossibleCoresEvent([]);
    $sut->onGetPossibleCores($event);
    self::assertEquals($expected, $event->getPossibleCores());
  }

  public function acquiaCloudInfo() {
    yield 'prod' => [
      'prod',
      'foobar_db',
      'foobar_db',
      [
        'abc123.prod.sitename',
        'abc123.prod',
        'abc123.prod.default',
      ],
    ];
    yield 'test' => [
      'test',
      'foobar_db',
      'foobar_db',
      [
        'abc123.test.sitename',
        'abc123.test',
        'abc123.test.default',
      ],
    ];
    yield 'dev' => [
      'dev',
      'foobar_db',
      'foobar_db',
      [
        'abc123.dev.sitename',
        'abc123.dev',
        'abc123.dev.default',
      ],
    ];
    yield 'no site env' => [
      '',
      'foobar_db',
      'foobar_db',
      [],
    ];

    yield 'no ah db role' => [
      'dev',
      'foobar_db',
      'baz_db',
      [
        'abc123.dev',
        'abc123.dev.default',
      ],
    ];
  }

}
