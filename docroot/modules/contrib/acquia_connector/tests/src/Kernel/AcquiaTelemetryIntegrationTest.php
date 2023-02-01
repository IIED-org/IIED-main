<?php

declare(strict_types=1);

namespace Drupal\Tests\acquia_connector\Kernel;

use Drupal\acquia_telemetry\Telemetry;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceModifierInterface;

/**
 * @group acquia_connector
 * @requires module acquia_telemetry
 */
final class AcquiaTelemetryIntegrationTest extends AcquiaConnectorTestBase implements ServiceModifierInterface {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'acquia_telemetry',
  ];

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    if ($container->hasDefinition('acquia.telemetry')) {
      $mocked_telemetry = $this->createMock(Telemetry::class);
      $mocked_telemetry->expects($this->never())->method('sendTelemetry');
      $mocked_telemetry->expects($this->never())->method('getAcquiaExtensionNames');
      $container->set('acquia.telemetry', $mocked_telemetry);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    $this->checkRequirements();
    parent::setUp();
    $this->installSchema('user', ['users_data']);
  }

  /**
   * Verifies acquia_telemetry's hooks are disabled.
   *
   * The telemetry service is mocked to not expect to be called, invoking the
   * hooks provide the assertion.
   */
  public function testHooksDisabled(): void {
    $module_handler = $this->container->get('module_handler');

    $module_handler->invokeAll('cron');
    $modules = ['acquia_foo', 'lightning_bar'];
    $module_handler->invokeAll('modules_installed', [$modules]);
    $module_handler->invokeAll('modules_uninstalled', [$modules]);
  }

}
