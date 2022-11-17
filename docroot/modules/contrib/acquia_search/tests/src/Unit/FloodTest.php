<?php

declare(strict_types=1);

namespace Drupal\Tests\acquia_search\Unit;

use Drupal\acquia_search\Helper\Flood;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Flood\FloodInterface;
use Psr\Log\LoggerInterface;

/**
 * @group acquia_search
 * @coversDefaultClass \Drupal\acquia_search\Helper\Flood
 */
final class FloodTest extends AcquiaSearchTestCase {

  /**
   * @testWith ["admin/ping", false]
   *           ["select", true]
   *           ["update", true]
   *           ["mlt", false]
   *           ["update/extract", true]
   */
  public function testIsControlled(string $handler, bool $is_controlled): void {
    $core_flood = $this->createMock(FloodInterface::class);
    $core_flood->expects($is_controlled ? $this->once() : $this->never())
      ->method('isAllowed');
    $config_factory = $this->createMock(ConfigFactoryInterface::class);
    $config_factory->method('get')
      ->with('acquia_search.settings')
      ->willReturn($this->createMock(ImmutableConfig::class));
    $flood = new Flood(
      $core_flood,
      $config_factory,
      $this->createMock(LoggerInterface::class)
    );

    self::assertEquals(!$is_controlled, $flood->isAllowed($handler));
  }

  /**
   * @testWith [true, false]
   *           [false, true]
   *           [false, false]
   */
  public function testIsAllowed(bool $core_flood_allowed, bool $logging): void {
    $core_flood = $this->createMock(FloodInterface::class);
    $core_flood->expects($this->once())
      ->method('isAllowed')
      ->willReturn($core_flood_allowed);
    $config_factory = $this->createMock(ConfigFactoryInterface::class);
    $config = $this->createMock(ImmutableConfig::class);
    $config->method('get')
      ->willReturnMap([
        ['flood_logging', $logging],
      ]);
    $config_factory->method('get')
      ->with('acquia_search.settings')
      ->willReturn($config);
    $logger = $this->createMock(LoggerInterface::class);
    $logger->expects(!$core_flood_allowed && $logging ? $this->once() : $this->never())
      ->method('warning');

    $flood = new Flood(
      $core_flood,
      $config_factory,
      $logger
    );

    self::assertEquals($core_flood_allowed, $flood->isAllowed('select'));
  }

}
