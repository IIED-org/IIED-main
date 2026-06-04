<?php

declare(strict_types=1);

namespace Drupal\name\Service;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Cached widget layouts from hook_name_widget_layouts().
 *
 * @internal
 */
class WidgetLayoutService implements WidgetLayoutInterface {

  /**
   * Request-level cache; mirrors prior drupal_static() semantics.
   *
   * Unset and empty array are falsy so empty hook results re-resolve each call.
   *
   * @var array<string, mixed>|null
   */
  private ?array $layouts = NULL;

  public function __construct(
    private readonly CacheBackendInterface $cache,
    private readonly ModuleHandlerInterface $moduleHandler,
  ) {}

  /**
   * Returns keyed widget layout definitions.
   */
  public function getLayouts(): array {
    if (!$this->layouts) {
      $cid = 'name:widget_layouts';
      if ($cache = $this->cache->get($cid)) {
        $this->layouts = is_array($cache->data) ? $cache->data : [];
      }
      else {
        $layouts = $this->moduleHandler->invokeAll('name_widget_layouts');
        foreach ($layouts as &$layout) {
          $layout += [
            'library' => [],
            'wrapper_attributes' => [],
          ];
          $layout['wrapper_attributes']['class'][] = 'name-widget-wrapper';
        }
        unset($layout);
        $this->cache->set($cid, $layouts);
        $this->layouts = $layouts;
      }
    }
    return $this->layouts ?? [];
  }

}
