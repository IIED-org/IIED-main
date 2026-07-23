<?php

namespace Drupal\Tests\purge\Unit\Logger;

use Drupal\purge\Logger\LoggerChannelPart;
use Drupal\purge\Logger\LoggerChannelPartFactory;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Psr\Log\LoggerInterface;

/**
 * Tests the LoggerChannelPartFactory class.
 *
 * @coversDefaultClass \Drupal\purge\Logger\LoggerChannelPartFactory
 * @group purge
 */
#[CoversClass(LoggerChannelPartFactory::class)]
#[Group('purge')]
class LoggerChannelPartFactoryTest extends UnitTestCase {

  /**
   * The tested factory.
   *
   * @var \Drupal\purge\Logger\LoggerChannelPartFactory
   */
  protected $loggerChannelPartFactory;

  /**
   * The mocked logger channel.
   *
   * @var \PHPUnit\Framework\MockObject\MockObject|\Psr\Log\LoggerInterface
   */
  protected $loggerChannelPurge;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->loggerChannelPurge = $this->createStub(LoggerInterface::class);
    $this->loggerChannelPartFactory = new LoggerChannelPartFactory($this->loggerChannelPurge);
  }

  /**
   * Tests creating a logger channel part.
   *
   * @dataProvider providerTestCreate
   */
  #[DataProvider('providerTestCreate')]
  public function testCreate($id, array $grants = []): void {
    $this->assertInstanceOf(
      LoggerChannelPart::class,
      $this->loggerChannelPartFactory->create($id, $grants)
    );
  }

  /**
   * Provides test data for testCreate().
   */
  public static function providerTestCreate(): array {
    return [
      ['foo', [0, 1, 2]],
      ['bar', [1, 2, 3]],
      ['baz', [2, 3, 4]],
    ];
  }

}
