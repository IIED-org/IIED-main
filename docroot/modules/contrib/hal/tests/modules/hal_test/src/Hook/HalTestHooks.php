<?php

namespace Drupal\hal_test\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for hal_test.
 */
class HalTestHooks {
  /**
   * @file
   * Contains hook implementations for testing HAL module.
   */

  /**
   * Implements hook_hal_type_uri_alter().
   */
  #[Hook('hal_type_uri_alter')]
  public static function halTypeUriAlter(
    &$uri,
    $context = [],
  ) {
    if (!empty($context['hal_test'])) {
      $uri = 'hal_test_type';
    }
  }

  /**
   * Implements hook_hal_relation_uri_alter().
   */
  #[Hook('hal_relation_uri_alter')]
  public static function halRelationUriAlter(
    &$uri,
    $context = [],
  ) {
    if (!empty($context['hal_test'])) {
      $uri = 'hal_test_relation';
    }
  }

}
