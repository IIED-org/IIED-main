<?php

declare(strict_types=1);

namespace Drupal\Tests\acquia_search\Unit;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Tests\UnitTestCase;

abstract class AcquiaSearchTestCase extends UnitTestCase {

  protected function createMockContainer(callable $configure): void {
    $container = new ContainerBuilder();
    $services = $configure();
    foreach ($services as $id => $service) {
      $container->set($id, $service);
    }
    \Drupal::setContainer($container);
  }

}
