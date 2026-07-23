<?php

namespace Drupal\Tests\purge\Kernel\Queue;

use PHPUnit\Framework\Attributes\Group;

/**
 * Tests \Drupal\purge\Plugin\Purge\Queue\DatabaseQueue.
 */
#[Group('purge')]
class DatabaseQueueTest extends PluginTestBase {

  /**
   * {@inheritdoc}
   */
  protected $pluginId = 'database';

}
