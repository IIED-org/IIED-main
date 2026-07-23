<?php

namespace Drupal\Tests\purge\Kernel\Queue;

use PHPUnit\Framework\Attributes\Group;

/**
 * Tests \Drupal\purge\Plugin\Purge\Queue\MemoryQueue.
 */
#[Group('purge')]
class MemoryQueueTest extends PluginTestBase {

  /**
   * {@inheritdoc}
   */
  protected $pluginId = 'memory';

}
