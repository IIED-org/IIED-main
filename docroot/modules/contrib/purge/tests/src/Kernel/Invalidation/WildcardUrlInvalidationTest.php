<?php

namespace Drupal\Tests\purge\Kernel\Invalidation;

use PHPUnit\Framework\Attributes\Group;

/**
 * Tests \Drupal\purge\Plugin\Purge\Invalidation\WildcardUrlInvalidation.
 */
#[Group('purge')]
class WildcardUrlInvalidationTest extends PluginTestBase {

  /**
   * The plugin ID of the invalidation type being tested.
   *
   * @var string
   */
  protected $pluginId = 'wildcardurl';

  /**
   * String expressions valid to the invalidation type being tested.
   *
   * @var null|mixed[]
   */
  protected $expressions = ['http://www.test.com/*', 'https://domain/path/*'];

  /**
   * String expressions invalid to the invalidation type being tested.
   *
   * @var null|mixed[]
   */
  protected $expressionsInvalid = [
    NULL,
    '',
    'http:// /aa',
    'http://www.test.com',
    'https://domain/path',
  ];

}
