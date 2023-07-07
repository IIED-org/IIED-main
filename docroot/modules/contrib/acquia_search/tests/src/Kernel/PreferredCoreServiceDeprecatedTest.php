<?php

declare(strict_types=1);

namespace Drupal\Tests\acquia_search\Kernel;

use Drupal\acquia_search\PreferredCoreService;

/**
 * @group acquia_search
 */
final class PreferredCoreServiceDeprecatedTest extends AcquiaSearchTestBase {

  /**
   * Tests that `acquia_search.preferred_core` is deprecated.
   *
   * @group legacy
   */
  public function testDeprecation(): void {
    $this->expectDeprecation(
      'The "acquia_search.preferred_core" service is deprecated in acquia_search:3.1.x and is removed from acquia_search:4.0.x. Use the `acquia_search.preferred_core_factory` to retrieve a preferred core service.'
    );
    $sut = $this->container->get('acquia_search.preferred_core');
    self::assertInstanceOf(PreferredCoreService::class, $sut);
  }

}
