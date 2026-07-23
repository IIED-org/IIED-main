<?php

namespace Drupal\Tests\purge\Kernel\Invalidation;

use PHPUnit\Framework\Attributes\Group;

/**
 * Tests \Drupal\purge\Plugin\Purge\Invalidation\DomainInvalidation.
 */
#[Group('purge')]
class DomainInvalidationTest extends PluginTestBase {

  /**
   * The plugin ID of the invalidation type being tested.
   *
   * @var string
   */
  protected $pluginId = 'domain';

  /**
   * String expressions valid to the invalidation type being tested.
   *
   * @var null|mixed[]
   */
  protected $expressions = ['sitea.com', 'www.site.com'];

  /**
   * String expressions invalid to the invalidation type being tested.
   *
   * @var null|mixed[]
   */
  protected $expressionsInvalid = [NULL, ''];

}
