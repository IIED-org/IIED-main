<?php

namespace Drupal\Tests\acquia_search\Unit;

use Drupal\acquia_search\Client\Solarium\AcquiaGuzzle;
use Drupal\Tests\UnitTestCase;

/**
 * Tests whether Acquia Guzzle client has debug option or not.
 *
 * @group acquia_search
 */
class AcquiaGuzzleDebugTest extends UnitTestCase {

  /**
   * Tests that debug is false in default setup.
   */
  public function testDebugNotAvailableOnDefault(): void {
    $client = new AcquiaGuzzle();
    self::assertFalse($client->getConfig('debug'));
  }

  /**
   * Tests that debug is true when env var is set.
   */
  public function testDebugAvailableWithEnvVar(): void {
    putenv("ACQUIA_GUZZLE_DEBUG=ENABLED");
    $client = new AcquiaGuzzle();
    self::assertTrue($client->getConfig('debug'));
  }

  /**
   * Tests that debug is true when superglobal is set.
   */
  public function testDebugAvailableWithGet(): void {
    // phpcs:ignore
    $_GET['debug'] = TRUE;
    $client = new AcquiaGuzzle();
    self::assertTrue($client->getConfig('debug'));
  }

}
