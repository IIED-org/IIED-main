<?php

declare(strict_types=1);

namespace Drupal\Tests\acquia_connector\Kernel;

use Drupal\Core\DependencyInjection\ContainerBuilder;

/**
 * @group acquia_connector
 */
final class RequirementsTest extends AcquiaConnectorTestBase {

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
    parent::register($container);
    // Fake the installation profile for system_requirements().
    $container->setParameter('install_profile', 'standard');
  }

  /**
   * Test that we never get REQUIREMENT_ERROR for runtime PHP requirement.
   */
  public function testPhpEol(): void {
    // Preload install files for system and acquia_connector to ensure their
    // hooks are discovered. They are not loaded in `drupal_load_updates` due to
    // the fact module schema is not registered in Kernel tests.
    $module_handler = $this->container->get('module_handler');
    $module_handler->loadInclude('system', 'install');
    $module_handler->loadInclude('acquia_connector', 'install');

    $requirements = $this->container->get('system.manager')->listRequirements();

    self::assertArrayHasKey('php', $requirements);
    // Severity is only set if there is an issue with PHP. If it is set, ensure
    // it is not set to REQUIREMENT_ERROR.
    if (isset($requirements['php']['severity'])) {
      self::assertNotEquals(REQUIREMENT_ERROR, $requirements['php']['severity']);
    }
  }

}
