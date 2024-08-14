<?php

declare(strict_types=1);

namespace Drupal\gin_lb\HookHandler;

/**
 * Hook implementation.
 */
class ModuleImplementsAlter {

  /**
   * Hook implementation.
   *
   * @param array $implementations
   *   An array keyed by the module's name. The value of each item corresponds
   *   to a $group, which is usually FALSE, unless the implementation is in a
   *   file named $module.$group.inc.
   * @param string $hook
   *   The name of the module hook being implemented.
   */
  public function alter(array &$implementations, string $hook): void {
    switch ($hook) {
      case 'suggestions_alter':
      case 'form_alter':
      case 'preprocess':
        $group = $implementations['gin_lb'];
        unset($implementations['gin_lb']);
        $implementations['gin_lb'] = $group;
        break;
    }
  }

}
