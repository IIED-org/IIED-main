<?php

namespace Drupal\Tests\acquia_telemetry\Kernel;

use Drupal\acquia_connector\EventSubscriber\KernelTerminate\AcquiaTelemetry;
use Drupal\acquia_telemetry\Telemetry;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests that the event on terminate request actually fires.
 *
 * @group acquia_telemetry
 */
class EventsTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['acquia_telemetry', 'system'];

  /**
   * The telemetry service under test.
   *
   * @var \Drupal\acquia_telemetry\Telemetry
   */
  private $telemetry;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->telemetry = $this->prophesize(AcquiaTelemetry::class);
    $this->container->set('acquia.telemetry', $this->telemetry->reveal());
  }

  public function testCron() {
    $this->telemetry->sendTelemetry('Drupal cron ran')->shouldBeCalled();
    $this->container->get('cron')->run();
  }

  public function testAcquiaExtensionInstall() {
    $modules = ['lightning_search'];

    $this->telemetry->getAcquiaExtensionNames()
      ->willReturn($modules)
      ->shouldBeCalled();

    $this->telemetry->sendTelemetry('Acquia extensions installed', [
      'installed_extensions' => $modules,
    ])->shouldBeCalled();

    acquia_telemetry_modules_installed($modules);

    $this->telemetry->sendTelemetry('Acquia extensions uninstalled', [
      'uninstalled_extensions' => $modules,
    ])->shouldBeCalled();

    acquia_telemetry_modules_uninstalled($modules);
  }

  public function testAcquiaExtensionUninstall() {
    $modules = ['lightning_search'];

    $this->telemetry->getAcquiaExtensionNames()
      ->willReturn($modules)
      ->shouldBeCalled();

    $this->telemetry->sendTelemetry('Acquia extensions uninstalled', [
      'uninstalled_extensions' => $modules,
    ])->shouldBeCalled();

    acquia_telemetry_modules_uninstalled($modules);
  }

}
