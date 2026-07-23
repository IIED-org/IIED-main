<?php

namespace Drupal\Tests\purge\Unit\Logger;

use Drupal\purge\Logger\PurgeLoggerAwareTrait;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\Group;
use Psr\Log\LoggerInterface;

/**
 * Tests the PurgeLoggerAwareTrait trait.
 *
 * @coversDefaultClass \Drupal\purge\Logger\PurgeLoggerAwareTrait
 * @group purge
 */
#[CoversTrait(PurgeLoggerAwareTrait::class)]
#[Group('purge')]
class PurgeLoggerAwareTraitTest extends UnitTestCase {

  /**
   * The mocked logger.
   *
   * @var \PHPUnit\Framework\MockObject\MockObject|\Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->logger = $this->createStub(LoggerInterface::class);
  }

  /**
   * Create a test object that uses the trait.
   */
  protected function createTraitObject(): object {
    return new class() {
      use PurgeLoggerAwareTrait;
    };
  }

  /**
   * Tests the logger() method returns the set logger.
   */
  public function testLogger(): void {
    $object = $this->createTraitObject();
    $object->setLogger($this->logger);
    $this->assertEquals($this->logger, $object->logger());
  }

  /**
   * Tests the logger() method throws exception when logger is not set.
   */
  public function testLoggerUnset(): void {
    $object = $this->createTraitObject();
    $this->expectException(\LogicException::class);
    $this->expectExceptionMessage('Logger unavailable, call ::setLogger().');
    $object->logger();
  }

}
