<?php

namespace Drupal\Tests\cloudflarepurger\Unit;

use Drupal\cloudflare\State;
use Drupal\cloudflarepurger\Plugin\Purge\DiagnosticCheck\DailyTagPurgeLimitCheck;
use Drupal\purge\Plugin\Purge\DiagnosticCheck\DiagnosticCheckInterface;

/**
 * Tests that purge_requirements() passes on our diagnostic checks.
 *
 * @group cloudflarepurger
 */
class DailyTagPurgeLimitCheckTest extends DiagnosticCheckTestBase {

  /**
   * Tests that DailyTagPurgeLimitCheck responds as expected.
   *
   * @param int $api_rate
   *   The currentAPI rate to test.
   * @param int $expected_severity
   *   The expected diagnostic severity.
   *
   * @dataProvider dailyTagPurgeLimitCheckProvider
   */
  public function testDailyTagPurgeLimitCheck($api_rate, $expected_severity) {
    $this->drupalState->set(State::TAG_PURGE_DAILY_COUNT, $api_rate);
    $this->drupalState->set(State::TAG_PURGE_DAILY_COUNT_START, new \DateTime());

    $api_rate_limit_check = new DailyTagPurgeLimitCheck([], '23123', 'this is a definition', $this->cloudflareState, TRUE);
    $actual_severity = $api_rate_limit_check->run();
    $this->assertEquals($expected_severity, $actual_severity);
  }

  /**
   * Data provider for validating DailyTagPurgeLimitCheck.
   *
   * @return array[]
   *   Returns per data set an array with:
   *     - count of daily tag purge requests
   *     - expected status returned by diagnostic check
   */
  public function dailyTagPurgeLimitCheckProvider() {
    return [
      [NULL, DiagnosticCheckInterface::SEVERITY_OK],
      [0, DiagnosticCheckInterface::SEVERITY_OK],
      [1, DiagnosticCheckInterface::SEVERITY_OK],
      [22499, DiagnosticCheckInterface::SEVERITY_OK],
      [22500, DiagnosticCheckInterface::SEVERITY_WARNING],
      [22501, DiagnosticCheckInterface::SEVERITY_WARNING],
      [22502, DiagnosticCheckInterface::SEVERITY_WARNING],
      [29999, DiagnosticCheckInterface::SEVERITY_WARNING],
      [30000, DiagnosticCheckInterface::SEVERITY_ERROR],
      [30001, DiagnosticCheckInterface::SEVERITY_ERROR],
      [30002, DiagnosticCheckInterface::SEVERITY_ERROR],
    ];
  }

}
