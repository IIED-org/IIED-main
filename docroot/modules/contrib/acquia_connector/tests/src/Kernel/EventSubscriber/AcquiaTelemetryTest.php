<?php

declare(strict_types=1);

namespace Drupal\Tests\acquia_connector\Kernel\EventSubscriber;

use Drupal\acquia_connector\EventSubscriber\KernelTerminate\AcquiaTelemetry;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Serialization\Json;
use Drupal\Component\Uuid\Php as PhpUuid;
use Drupal\Tests\acquia_connector\Kernel\AcquiaConnectorTestBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\KernelEvent;

/**
 * @coversDefaultClass \Drupal\acquia_connector\EventSubscriber\KernelTerminate\AcquiaTelemetry
 * @group acquia_connector
 */
final class AcquiaTelemetryTest extends AcquiaConnectorTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->config('system.site')
      ->set('uuid', (new PhpUuid())->generate())
      ->save();
  }

  /**
   * Tests the telemetry events sent.
   */
  public function testTelemetry(): void {
    $state = $this->container->get('state');

    $request = Request::create('/');
    $this->container->get('http_kernel')->terminate(
      $request,
      $this->doRequest($request)
    );

    $events = $state->get('acquia_connector_test.telemetry_events', []);
    self::assertIsArray($events);
    self::assertCount(1, $events);
    $payload = [];
    parse_str($events[0], $payload);
    self::assertIsString($payload['api_key']);
    self::assertIsString('event', $payload['event']);

    $data = Json::decode($payload['event']);
    self::assertIsArray($data);
    self::assertEquals(
      ['event_type', 'user_id', 'event_properties'],
      array_keys($data),
    );
    self::assertEquals(
      ['extensions', 'php', 'drupal'],
      array_keys($data['event_properties'])
    );
    self::assertContains('acquia_connector', array_keys($data['event_properties']['extensions']));
    self::assertEquals(['version'], array_keys($data['event_properties']['php']));
    self::assertEquals(PHP_VERSION, $data['event_properties']['php']['version']);
    self::assertEquals(['version', 'core_enabled'], array_keys($data['event_properties']['drupal']));
    self::assertContains('user', array_keys($data['event_properties']['drupal']['core_enabled']));
  }

  /**
   * Tests the telemetry threshold period for sending events.
   */
  public function testTelemetryThresholdPeriod(): void {
    $state = $this->container->get('state');
    $current_time = time();
    $time = $this->createMock(TimeInterface::class);
    $time->method('getCurrentTime')->willReturn(
      // The first getCurrentTime() for checks.
      $current_time,
      // The call to getCurrentTime() for setting the timestamp.
      $current_time,
      // The second getCurrentTime() for checks.
      $current_time + 21600,
      // The third getCurrentTime() for checks.
      $current_time + 86400,
      // The fourth getCurrentTime() for checks.
      $current_time + 86401,
      // The call to getCurrentTime() for setting the timestamp.
      $current_time + 86401
    );

    $sut = new AcquiaTelemetry(
      $this->container->get('extension.list.module'),
      $this->container->get('http_client'),
      $this->container->get('config.factory'),
      $state,
      $time
    );
    $do_terminate = function () use ($sut) {
      $sut->onTerminateResponse(new KernelEvent(
        $this->container->get('http_kernel'),
        Request::create('/'),
        1
      ));
    };

    $do_terminate();
    self::assertEquals($current_time, $state->get('acquia_connector.telemetry.timestamp'));
    self::assertCount(1, $state->get('acquia_connector_test.telemetry_events'));

    $do_terminate();
    self::assertEquals($current_time, $state->get('acquia_connector.telemetry.timestamp'));
    self::assertCount(1, $state->get('acquia_connector_test.telemetry_events'));

    $do_terminate();
    self::assertEquals($current_time, $state->get('acquia_connector.telemetry.timestamp'));
    self::assertCount(1, $state->get('acquia_connector_test.telemetry_events'));

    $do_terminate();
    self::assertEquals($current_time + 86401, $state->get('acquia_connector.telemetry.timestamp'));
    self::assertCount(2, $state->get('acquia_connector_test.telemetry_events'));
  }

}
