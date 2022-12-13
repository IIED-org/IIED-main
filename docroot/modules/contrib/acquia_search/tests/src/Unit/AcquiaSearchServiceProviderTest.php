<?php

declare(strict_types=1);

namespace Drupal\Tests\acquia_search\Unit;

use Drupal\acquia_search\AcquiaSearchServiceProvider;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\Definition;

/**
 * @group acquia_search
 * @coversDefaultClass \Drupal\acquia_search\AcquiaSearchServiceProvider
 */
final class AcquiaSearchServiceProviderTest extends UnitTestCase {

  /**
   * @covers ::register
   */
  public function testRegister(): void {
    $container = $this->createMock(ContainerBuilder::class);
    // There is no way to mock a constant.
    if (version_compare(\Drupal::VERSION, '9.0', '<')) {
      $container
        ->expects($this->exactly(2))
        ->method('getDefinition')
        ->willReturnMap([
          ['acquia_search.possible_cores.acquia_hosting', $this->createMock(Definition::class)],
          ['acquia_search.possible_cores.default_core', $this->createMock(Definition::class)],
        ]);
    }
    else {
      $container
        ->expects($this->never())
        ->method('getDefinition');
    }

    $sut = new AcquiaSearchServiceProvider();
    $sut->register($container);
  }

}
