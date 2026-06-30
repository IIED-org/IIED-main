<?php

namespace Drupal\Tests\memcache\Functional;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceModifierInterface;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Tests\system\Functional\Lock\LockFunctionalTest;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Confirm locking works between two separate requests.
 *
 * @group memcache
 */
class MemcacheLockFunctionalTest extends LockFunctionalTest implements ServiceModifierInterface {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['memcache'];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    $host = getenv('MEMCACHED_HOST') ?: '127.0.0.1:11211';
    $settings['settings']['memcache'] = (object) [
      'value' => [
        'servers' => [$host => 'default'],
        'bin' => ['default' => 'default'],
      ],
      'required' => TRUE,
    ];

    $settings['settings']['hash_salt'] = (object) [
      'value' => $this->randomMachineName(),
      'required' => TRUE,
    ];

    $this->writeSettings($settings);
  }

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $definition = new Definition(LockBackendInterface::class);
    $definition->setFactory([new Reference('memcache.lock.factory'), 'get']);

    $container->setDefinition('lock', $definition);
  }

}
