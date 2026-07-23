<?php

namespace Drupal\Tests\purge\Kernel\Invalidation;

use PHPUnit\Framework\Attributes\Group;

/**
 * Tests \Drupal\purge\Plugin\Purge\Invalidation\EverythingInvalidation.
 */
#[Group('purge')]
class EverythingInvalidationTest extends PluginTestBase {

  /**
   * The plugin ID of the invalidation type being tested.
   *
   * @var string
   */
  protected $pluginId = 'everything';

  /**
   * String expressions valid to the invalidation type being tested.
   *
   * @var null|mixed[]
   */
  protected $expressions = [NULL];

  /**
   * String expressions invalid to the invalidation type being tested.
   *
   * @var null|mixed[]
   */
  protected $expressionsInvalid = ['', 'foobar'];

}
