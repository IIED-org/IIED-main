<?php

namespace Drupal\Tests\purge\Kernel\Invalidation;

use PHPUnit\Framework\Attributes\Group;

/**
 * Tests \Drupal\purge\Plugin\Purge\Invalidation\TagInvalidation.
 */
#[Group('purge')]
class TagInvalidationTest extends PluginTestBase {

  /**
   * The plugin ID of the invalidation type being tested.
   *
   * @var string
   */
  protected $pluginId = 'tag';

  /**
   * String expressions valid to the invalidation type being tested.
   *
   * @var null|mixed[]
   */
  protected $expressions = [
    'tag',
    'user:1',
    'menu:footer',
  ];

  /**
   * String expressions invalid to the invalidation type being tested.
   *
   * @var null|mixed[]
   */
  protected $expressionsInvalid = [
    NULL,
    '',
    ['node', '1'],
    'wildtag:*',
  ];

}
