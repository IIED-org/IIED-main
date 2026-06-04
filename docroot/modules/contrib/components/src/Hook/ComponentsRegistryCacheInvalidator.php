<?php

namespace Drupal\components\Hook;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Invalidates the cache for the Components registry.
 *
 * The components module needs to rebuild its registry when 2 things happen:
 * 1. New or updated Twig namespaces in .info.yml need to be parsed.
 * 2. New Twig components are added.
 *
 * We can use Drupal calling hook_theme while rebuilding the theme registry
 * as a proxy for these events.
 */
class ComponentsRegistryCacheInvalidator {

  /**
   * Implements hook_theme().
   */
  #[Hook('theme') /* @phpstan-ignore attribute.notFound */]
  public function invalidate(): array {
    Cache::invalidateTags(['components_registry']);

    return [];
  }

}
