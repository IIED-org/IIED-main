<?php

/**
 * @file
 * Post update functions for Login And Logout Redirect Per Role.
 */

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\user\RoleInterface;

/**
 * Convert configuration to a new structure.
 */
function login_redirect_per_role_post_update_convert_configuration() {
  $config_factory = \Drupal::service('config.factory');

  if ($config_factory instanceof ConfigFactoryInterface) {
    $old_config = $config_factory->getEditable('login_redirect_per_role.redirecturlsettings');

    $old_data = $old_config->get();

    if ($old_data) {
      $new_config = $config_factory->getEditable('login_redirect_per_role.settings');

      $allow_destination = isset($old_data['allow_destination']) ? (bool) $old_data['allow_destination'] : FALSE;
      $default_site_url = isset($old_data['default_site_url']) ? trim($old_data['default_site_url']) : '';

      $old_role_url_pairs = [];
      foreach ($old_data as $key => $value) {
        $chunk = explode('login_redirect_per_role_', $key);

        if (!empty($chunk[1]) && $value) {
          $old_role_url_pairs[$chunk[1]] = $value;
        }
      }

      $roles = \Drupal::entityTypeManager()->getStorage('user_role')->loadMultiple();
      $role_by_weight = [];

      foreach ($roles as $role) {
        if ($role instanceof RoleInterface) {
          $role_by_weight[$role->getWeight()] = $role->id();
        }
      }

      krsort($role_by_weight, SORT_NUMERIC);

      $new_data = [];
      $weight = 0;

      foreach ($role_by_weight as $role_id) {
        switch ($role_id) {

          case RoleInterface::ANONYMOUS_ID:
            // Exclude anonymous from a list.
            break;

          case RoleInterface::AUTHENTICATED_ID:
            $new_data['login'][$role_id] = [
              'redirect_url' => $default_site_url,
              'allow_destination' => $allow_destination,
              'weight' => $weight,
            ];
            $new_data['logout'][$role_id] = [
              'redirect_url' => '',
              'allow_destination' => FALSE,
              'weight' => $weight,
            ];
            $weight++;
            break;

          default:
            $new_data['login'][$role_id] = [
              'redirect_url' => isset($old_role_url_pairs[$role_id]) ? $old_role_url_pairs[$role_id] : '',
              'allow_destination' => $allow_destination,
              'weight' => $weight,
            ];
            $new_data['logout'][$role_id] = [
              'redirect_url' => '',
              'allow_destination' => FALSE,
              'weight' => $weight,
            ];
            $weight++;
            break;
        }
      }

      $old_config->delete();
      $new_config->setData($new_data)->save();
    }
  }
}
