<?php

namespace Drupal\Tests\acquia_connector\Functional;

use Drupal\acquia_connector\EventSubscriber\KernelTerminate\AcquiaTelemetry;
use Drupal\Component\Uuid\Php as PhpUuid;
use Drupal\Core\Database\Database;
use Drupal\Tests\BrowserTestBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\KernelEvent;

/**
 * @coversDefaultClass \Drupal\acquia_connector\EventSubscriber\KernelTerminate\AcquiaTelemetry
 * @group acquia_connector
 */
final class AcquiaTelemetryTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'dblog',
    'system',
    'user',
    'acquia_connector',
    'acquia_connector_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

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
   * Tests the sendTelemetry() method of AcquiaCmsTelemetry class for prod env.
   */
  public function testTelemetryLogs(): void {
    $count = 0;
    $telemetry_service = new AcquiaTelemetry(
      $this->container->get('acquia_connector.telemetry_service'),
    );
    $send_request = function () use ($telemetry_service) {
      $telemetry_service->onTerminateEvent(new KernelEvent(
        $this->container->get('http_kernel'),
        Request::create('/'),
        1
      ));
    };

    // Fake Acquia dev environment and then send data.
    putenv("AH_SITE_ENVIRONMENT=dev");
    // Invoke Acquia Temletry event.
    $send_request();
    $query_count = $this->getLogEntry();
    // Assert log entry does not exist.
    $this->assertSame($count, $query_count, 'Telemetry data should not be send as on Acquia dev environment.');

    // Fake Acquia stage environment and then send data.
    putenv("AH_SITE_ENVIRONMENT=stage");
    // Invoke Acquia Temletry event.
    $send_request();
    $query_count = $this->getLogEntry();
    // Assert log entry does not exist.
    $this->assertSame($count, $query_count, 'Telemetry data should not be send as on Acquia stage environment.');

    // Fake Acquia IDE environment and then send data.
    putenv("AH_SITE_ENVIRONMENT=IDE");
    // Invoke Acquia Temletry event.
    $send_request();
    $query_count = $this->getLogEntry();
    // Assert log entry does not exist.
    $this->assertSame($count, $query_count, 'Telemetry data should not be send as on Acquia IDE environment.');

    // Fake Acquia ODE environment and then send data.
    putenv("AH_SITE_ENVIRONMENT=ODE");
    // Invoke Acquia Temletry event.
    $send_request();
    $query_count = $this->getLogEntry();
    // Assert log entry does not exist.
    $this->assertSame($count, $query_count, 'Telemetry data should not be send as on Acquia ODE environment.');

    // Fake Acquia prod environment and then send data.
    putenv("AH_SITE_ENVIRONMENT=prod");
    // Invoke Acquia Temletry event.
    $send_request();
    $query_count = $this->getLogEntry();
    // Assert log entry does not exist.
    $this->assertEquals(1, $query_count, 'Telemetry data should send as on Acquia prod environment.');

  }

  /**
   * Tests the Telemetry sending log when module installed/uninstalled.
   */
  public function testTelemetryModuleInstallUninstallLogEntry(): void {
    // Fake Acquia prod environment and then send data.
    putenv("AH_SITE_ENVIRONMENT=prod");

    $this->container->get('module_installer')->install(['acquia_connector_subdata_test']);
    $query_count = $this->getLogEntry('acquia_extensions_installed');
    // Assert log entry exist.
    $this->assertEquals(1, $query_count, 'Telemetry data should send as on Acquia prod environment when module installed.');

    $this->container->get('module_installer')->uninstall(['acquia_connector_subdata_test']);
    $query_count = $this->getLogEntry('acquia_extensions_uninstalled');
    // Assert log entry exist.
    $this->assertEquals(1, $query_count, 'Telemetry data should send as on Acquia prod environment when module uninstalled.');

  }

  /**
   * Get database log for Drupal Module Statistics entry.
   *
   * @param string $event_type
   *   The event type.
   *
   * @return int
   *   The log count.
   */
  public function getLogEntry(string $event_type = 'drupal_module_statistics'): int {

    return(Database::getConnection()
      ->select('watchdog')
      ->condition('type', $event_type)
      ->countQuery()
      ->execute()
      ->fetchField());

  }

}
