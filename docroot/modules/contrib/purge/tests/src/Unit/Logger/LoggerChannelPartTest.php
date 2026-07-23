<?php

namespace Drupal\Tests\purge\Unit\Logger;

use Drupal\Core\Logger\RfcLogLevel;
use Drupal\purge\Logger\LoggerChannelPart;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

/**
 * Tests the LoggerChannelPart class.
 *
 * @coversDefaultClass \Drupal\purge\Logger\LoggerChannelPart
 * @group purge
 */
#[CoversClass(LoggerChannelPart::class)]
#[Group('purge')]
class LoggerChannelPartTest extends UnitTestCase {

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
    $this->loggerChannelPurge = $this->createMock(LoggerInterface::class);
  }

  /**
   * Helper to all severity methods.
   */
  private function helperForSeverityMethods($id, array $grants, $output, $severity): void {
    $occurrence = is_null($output) ? $this->never() : $this->once();
    $level_translation = [
      LogLevel::EMERGENCY => RfcLogLevel::EMERGENCY,
      LogLevel::ALERT => RfcLogLevel::ALERT,
      LogLevel::CRITICAL => RfcLogLevel::CRITICAL,
      LogLevel::ERROR => RfcLogLevel::ERROR,
      LogLevel::WARNING => RfcLogLevel::WARNING,
      LogLevel::NOTICE => RfcLogLevel::NOTICE,
      LogLevel::INFO => RfcLogLevel::INFO,
      LogLevel::DEBUG => RfcLogLevel::DEBUG,
    ];
    $this->loggerChannelPurge
      ->expects($occurrence)
      ->method('log')
      ->with(
        $this->equalTo($level_translation[$severity]),
        $this->stringContains('@purge_channel_part: @replaceme'),
        $this->callback(function ($subject) use ($id, $output) {
          return ($subject['@purge_channel_part'] === $id) && ($subject['@replaceme'] === $output);
        })
      );
    $part = new LoggerChannelPart($this->loggerChannelPurge, $id, $grants);
    $part->$severity('@replaceme', ['@replaceme' => $output]);
  }

  /**
   * Tests that the class implements LoggerInterface.
   */
  public function testInstance(): void {
    $logger = $this->createStub(LoggerInterface::class);
    $part = new LoggerChannelPart($logger, 'id', []);
    $this->assertInstanceOf(LoggerInterface::class, $part);
  }

  /**
   * Tests retrieving the configured grants.
   *
   * @dataProvider providerTestGetGrants
   */
  #[DataProvider('providerTestGetGrants')]
  public function testGetGrants(array $grants): void {
    $part = new LoggerChannelPart($this->loggerChannelPurge, 'id', $grants);
    $this->assertEquals(count($grants), count($part->getGrants()));
    $this->assertEquals($grants, $part->getGrants());
    foreach ($part->getGrants() as $k => $v) {
      $this->assertTrue(is_int($k));
      $this->assertTrue(is_int($v));
    }
  }

  /**
   * Provides test data for testGetGrants().
   */
  public static function providerTestGetGrants(): array {
    return [
      [[]],
      [[RfcLogLevel::EMERGENCY]],
      [[RfcLogLevel::ALERT]],
      [[RfcLogLevel::CRITICAL]],
      [[RfcLogLevel::ERROR]],
      [[RfcLogLevel::WARNING]],
      [[RfcLogLevel::NOTICE]],
      [[RfcLogLevel::INFO]],
      [[RfcLogLevel::INFO, RfcLogLevel::DEBUG]],
      [[RfcLogLevel::DEBUG]],
    ];
  }

  /**
   * Tests the emergency log level.
   *
   * @dataProvider providerTestEmergency
   */
  #[DataProvider('providerTestEmergency')]
  public function testEmergency($id, array $grants, $output): void {
    $this->helperForSeverityMethods($id, $grants, $output, 'emergency');
  }

  /**
   * Provides test data for testEmergency().
   */
  public static function providerTestEmergency(): array {
    return [
      ['good', [RfcLogLevel::EMERGENCY], 'bazinga!'],
      ['bad', [-1], NULL],
    ];
  }

  /**
   * Tests the alert log level.
   *
   * @dataProvider providerTestAlert
   */
  #[DataProvider('providerTestAlert')]
  public function testAlert($id, array $grants, $output): void {
    $this->helperForSeverityMethods($id, $grants, $output, 'alert');
  }

  /**
   * Provides test data for testAlert().
   */
  public static function providerTestAlert(): array {
    return [
      ['good', [RfcLogLevel::ALERT], 'bazinga!'],
      ['bad', [-1], NULL],
    ];
  }

  /**
   * Tests the critical log level.
   *
   * @dataProvider providerTestCritical
   */
  #[DataProvider('providerTestCritical')]
  public function testCritical($id, array $grants, $output): void {
    $this->helperForSeverityMethods($id, $grants, $output, 'critical');
  }

  /**
   * Provides test data for testCritical().
   */
  public static function providerTestCritical(): array {
    return [
      ['good', [RfcLogLevel::CRITICAL], 'bazinga!'],
      ['bad', [-1], NULL],
    ];
  }

  /**
   * Tests the error log level.
   *
   * @dataProvider providerTestError
   */
  #[DataProvider('providerTestError')]
  public function testError($id, array $grants, $output): void {
    $this->helperForSeverityMethods($id, $grants, $output, 'error');
  }

  /**
   * Provides test data for testError().
   */
  public static function providerTestError(): array {
    return [
      ['good', [RfcLogLevel::ERROR], 'bazinga!'],
      ['bad', [-1], NULL],
    ];
  }

  /**
   * Tests the warning log level.
   *
   * @dataProvider providerTestWarning
   */
  #[DataProvider('providerTestWarning')]
  public function testWarning($id, array $grants, $output): void {
    $this->helperForSeverityMethods($id, $grants, $output, 'warning');
  }

  /**
   * Provides test data for testWarning().
   */
  public static function providerTestWarning(): array {
    return [
      ['good', [RfcLogLevel::WARNING], 'bazinga!'],
      ['bad', [-1], NULL],
    ];
  }

  /**
   * Tests the notice log level.
   *
   * @dataProvider providerTestNotice
   */
  #[DataProvider('providerTestNotice')]
  public function testNotice($id, array $grants, $output): void {
    $this->helperForSeverityMethods($id, $grants, $output, 'notice');
  }

  /**
   * Provides test data for testNotice().
   */
  public static function providerTestNotice(): array {
    return [
      ['good', [RfcLogLevel::NOTICE], 'bazinga!'],
      ['bad', [-1], NULL],
    ];
  }

  /**
   * Tests the info log level.
   *
   * @dataProvider providerTestInfo
   */
  #[DataProvider('providerTestInfo')]
  public function testInfo($id, array $grants, $output): void {
    $this->helperForSeverityMethods($id, $grants, $output, 'info');
  }

  /**
   * Provides test data for testInfo().
   */
  public static function providerTestInfo(): array {
    return [
      ['good', [RfcLogLevel::INFO], 'bazinga!'],
      ['bad', [-1], NULL],
    ];
  }

  /**
   * Tests the debug log level.
   *
   * @dataProvider providerTestDebug
   */
  #[DataProvider('providerTestDebug')]
  public function testDebug($id, array $grants, $output): void {
    $this->helperForSeverityMethods($id, $grants, $output, 'debug');
  }

  /**
   * Provides test data for testDebug().
   */
  public static function providerTestDebug(): array {
    return [
      ['good', [RfcLogLevel::DEBUG], 'bazinga!'],
      ['bad', [-1], NULL],
    ];
  }

  /**
   * Tests the generic log method.
   *
   * @dataProvider providerTestLog
   */
  #[DataProvider('providerTestLog')]
  public function testLog($id, $message, $output): void {
    $this->loggerChannelPurge
      ->expects($this->once())
      ->method('log')
      ->with(
        $this->equalTo(RfcLogLevel::DEBUG),
        $this->stringContains('@purge_channel_part: ' . $message),
        $this->callback(function ($subject) use ($id, $output) {
          return ($subject['@purge_channel_part'] === $id) && ($subject['@replaceme'] === $output);
        })
      );
    $part = new LoggerChannelPart($this->loggerChannelPurge, $id, [
      RfcLogLevel::DEBUG,
    ]);
    $part->log(LogLevel::DEBUG, $message, ['@replaceme' => $output]);
  }

  /**
   * Provides test data for testLog().
   */
  public static function providerTestLog(): array {
    return [
      ['id1', 'message @placeholder', ['@placeholder' => 'foo']],
      ['id2', 'message @placeholder', ['@placeholder' => 'bar']],
      ['id3', 'message @placeholder', ['@placeholder' => 'baz']],
    ];
  }

}
