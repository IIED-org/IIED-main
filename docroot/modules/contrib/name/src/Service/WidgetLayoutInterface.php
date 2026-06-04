<?php

declare(strict_types=1);

namespace Drupal\name\Service;

/**
 * Cached widget layouts from hook_name_widget_layouts().
 *
 * @internal
 */
interface WidgetLayoutInterface {

  /**
   * Returns keyed widget layout definitions.
   */
  public function getLayouts(): array;

}
