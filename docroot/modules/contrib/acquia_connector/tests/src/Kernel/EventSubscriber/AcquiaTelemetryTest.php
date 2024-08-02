<?php

declare(strict_types=1);

namespace Drupal\Tests\acquia_connector\Kernel\EventSubscriber;

use Drupal\acquia_connector\Services\AcquiaTelemetryService;
use Drupal\Component\Uuid\Php as PhpUuid;
use Drupal\Tests\acquia_connector\Kernel\AcquiaConnectorTestBase;

/**
 * @coversDefaultClass \Drupal\acquia_connector\EventSubscriber\KernelTerminate\AcquiaTelemetry
 * @group acquia_connector
 */
final class AcquiaTelemetryTest extends AcquiaConnectorTestBase {

  /**
   * The AcquiaTelemetry service object.
   *
   * @var \Drupal\acquia_connector\Services\AcquiaTelemetryService
   */
  protected $acquiaTelemetry;

  /**
   * The event name.
   *
   * @var string
   */
  protected $eventTypeMachine;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->config('system.site')
      ->set('uuid', (new PhpUuid())->generate())
      ->save();
    $this->acquiaTelemetry = new AcquiaTelemetryService(
      $this->container->get('extension.list.module'),
      $this->container->get('config.factory'),
      $this->container->get("state"),
      $this->container->get("logger.factory"),
    );
    $this->eventTypeMachine = preg_replace('@[^a-z0-9-]+@', '_', strtolower('Drupal Module Statistics'));
  }

  /**
   * Tests the telemetry data sent.
   */
  public function testTelemetry(): void {
    $get_telemetry_method = $this->getAcquiaTelemetryMethod("getTelemetryData");
    $actual_telemetry_data = $get_telemetry_method->invokeArgs($this->acquiaTelemetry, ['Drupal Module Statistics', []]);

    $this->assertArrayHasKey('event_type', $actual_telemetry_data, "Telemetry data does not contain event_type key.");
    $this->assertArrayHasKey('user_id', $actual_telemetry_data, "Telemetry data does not contain user_id key.");
    $this->assertNotEmpty($actual_telemetry_data['user_id'], "Site UUID is empty.");

    $this->assertArrayHasKey('event_properties', $actual_telemetry_data, "Telemetry data does not contain event_properties key.");

    $this->assertArrayHasKey('extensions', $actual_telemetry_data['event_properties'], "Telemetry data event_properties does not contain extensions key.");
    $this->assertArrayHasKey('php', $actual_telemetry_data['event_properties'], "Telemetry data event_properties does not contain php key.");
    $this->assertArrayHasKey('drupal', $actual_telemetry_data['event_properties'], "Telemetry data event_properties does not contain drupal key.");

    $this->assertContains('acquia_connector', array_keys($actual_telemetry_data['event_properties']['extensions']));
    $this->assertEquals(['version'], array_keys($actual_telemetry_data['event_properties']['php']));
    $this->assertEquals(PHP_VERSION, $actual_telemetry_data['event_properties']['php']['version']);
    $this->assertEquals(['version', 'core_enabled'], array_keys($actual_telemetry_data['event_properties']['drupal']));
    $this->assertContains('user', array_keys($actual_telemetry_data['event_properties']['drupal']['core_enabled']));

  }

  /**
   * Tests the shouldSendTelemetryData() method of AcquiaCmsTelemetry class.
   *
   * @throws \ReflectionException
   *
   * @dataProvider telemetryTestData
   */
  public function testIfTelemetryDataShouldSend(string|null $env, bool $should_send): void {
    $get_telemetry_method = $this->getAcquiaTelemetryMethod("getTelemetryData");
    $actual_telemetry_data = $get_telemetry_method->invokeArgs($this->acquiaTelemetry, [$this->eventTypeMachine, []]);
    $should_send_method = $this->getAcquiaTelemetryMethod("shouldSendTelemetryData");
    $env_check_method = $this->getAcquiaTelemetryMethod("isAcquiaProdEnv");
    $state_service = $this->container->get("state");
    $parameters = [
      $this->eventTypeMachine,
      [
        'extensions' => $actual_telemetry_data['event_properties']['extensions'],
        'php' => $actual_telemetry_data['event_properties']['php'],
        'drupal' => $actual_telemetry_data['event_properties']['drupal'],
      ],
    ];

    if ($env) {
      putenv("AH_SITE_ENVIRONMENT=$env");
    }
    $should_send_data = $env_check_method->invoke($this->acquiaTelemetry);
    $should_send_msg = !$should_send ? 'not' : '';
    $this->assertEquals($should_send, $should_send_data, "Should $should_send_msg send telemetry data.");

    $method_hash = $this->getAcquiaTelemetryMethod("getHash");
    $state_service->set(
      "acquia_connector.telemetry.$this->eventTypeMachine.hash",
      $method_hash->invoke($this->acquiaTelemetry, [
        'extensions' => $actual_telemetry_data['event_properties']['extensions'],
        'php' => $actual_telemetry_data['event_properties']['php'],
        'drupal' => $actual_telemetry_data['event_properties']['drupal'],
      ]),
    );

    if ($should_send) {
      $should_send_data = $should_send_method->invokeArgs($this->acquiaTelemetry, $parameters);
      $this->assertFalse($should_send_data, "Should not send telemetry data, if current telemetry data is same as data already sent.");

      $state_service->set("acquia_connector.telemetry.$this->eventTypeMachine.hash", 'O2X4mf9Csg8KLOIqNlUqc9dqXdsL_JE5hjKh4dRPemQ');
      $should_send_data = $should_send_method->invokeArgs($this->acquiaTelemetry, $parameters);
      $this->assertTrue($should_send_data, "Should send telemetry data, if current telemetry data has changed from data already sent.");
    }
  }

  /**
   * Data for testing embed codes.
   *
   * @return \Generator
   *   The test data.
   */
  public function telemetryTestData(): \Generator {
    yield 'Non Acquia Environment' => [
      NULL,
      FALSE,
    ];
    yield 'Dev Acquia Environment' => [
      'dev',
      FALSE,
    ];
    yield 'Stage Acquia Environment' => [
      'stage',
      FALSE,
    ];
    yield 'Prod Acquia Environment' => [
      'prod',
      TRUE,
    ];
    yield 'IDE Acquia Environment' => [
      'IDE',
      FALSE,
    ];
    yield 'ODE Acquia Environment' => [
      'ODE',
      FALSE,
    ];
  }

  /**
   * Returns the AcquiaTelemetry ReflectionMethod object.
   *
   * @throws \ReflectionException
   */
  protected function getAcquiaTelemetryMethod(string $method_name): \ReflectionMethod {
    $class = new \ReflectionClass($this->acquiaTelemetry);
    $method = $class->getMethod($method_name);
    $method->setAccessible(TRUE);
    return $method;
  }

}
