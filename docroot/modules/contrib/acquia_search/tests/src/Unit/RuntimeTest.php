<?php

declare(strict_types=1);

namespace Drupal\Tests\acquia_search\Unit;

use Drupal\acquia_search\Helper\Runtime;
use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\search_api\ServerInterface;

/**
 * Tests the Runtime class.
 *
 * @group acquia_search
 * @coversDefaultClass \Drupal\acquia_search\Helper\Runtime
 */
final class RuntimeTest extends AcquiaSearchTestCase {

  /**
   * Tests shouldEnforceReadOnlyMode().
   *
   * @testWith [false]
   *           [true]
   */
  public function testShouldEnforceReadOnlyMode(bool $expected): void {
    $this->createMockContainer(function () use ($expected) {
      $config = $this->createMock(Config::class);
      $config->expects($this->once())
        ->method('get')
        ->with('read_only')
        ->willReturn($expected);
      $config_factory = $this->createMock(ConfigFactoryInterface::class);
      $config_factory->expects($this->once())
        ->method('get')
        ->with('acquia_search.settings')
        ->willReturn($config);

      $module_handler = $this->createMock(ModuleHandlerInterface::class);
      $module_handler->expects($this->once())
        ->method('alter')
        ->with('acquia_search_should_enforce_read_only');

      return [
        'config.factory' => $config_factory,
        'module_handler' => $module_handler,
      ];
    });

    self::assertEquals($expected, Runtime::shouldEnforceReadOnlyMode());
  }

  /**
   * Tests isAcquiaServer.
   *
   * @testWith ["standard", false]
   *           ["solr_acquia_connector", true]
   */
  public function testIsAcquiaServer(string $connector, bool $expected): void {
    $server = $this->createMock(ServerInterface::class);
    $server->expects($this->once())
      ->method('getBackendConfig')
      ->willReturn([
        'connector' => $connector,
      ]);
    self::assertEquals($expected, Runtime::isAcquiaServer($server));
  }

}
