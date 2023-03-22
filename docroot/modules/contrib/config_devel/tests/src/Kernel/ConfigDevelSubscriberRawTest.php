<?php

namespace Drupal\Tests\config_devel\Kernel;

/**
 * Tests the automated importer for raw config objects.
 *
 * @group config_devel
 */
class ConfigDevelSubscriberRawTest extends ConfigDevelSubscriberTestBase {

  /**
   * {@inheritdoc}
   */
  const CONFIGNAME = 'config_devel.test';

  /**
   * {@inheritdoc}
   */
  protected function doAssert(array $data, array $exported_data) {
    $this->assertSame($data, $this->storage->read(static::CONFIGNAME));
    $this->assertSame($data, $exported_data);
  }

}
