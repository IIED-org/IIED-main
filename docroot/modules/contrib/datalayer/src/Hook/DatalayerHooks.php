<?php

namespace Drupal\datalayer\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for datalayer.
 */
class DatalayerHooks {

  /**
   * Implements hook_datalayer_meta().
   *
   * Defines default meta data.
   */
  #[Hook('datalayer_meta')]
  public function datalayerMeta() {
    return [
      'created',
      'langcode',
      'name',
      'status',
      'uid',
      'uuid',
      'vid',
    ];
  }

  /**
   * Implements hook_datalayer_current_user_meta().
   *
   * Defines current user meta data.
   */
  #[Hook('datalayer_current_user_meta')]
  public function datalayerCurrentUserMeta() {
    return [
      'name',
      'mail',
      'roles',
      'created',
      'access',
    ];
  }

}
