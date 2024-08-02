<?php

/**
 * @file
 * Hook_post_update_NAME functions for tfa module.
 */

/**
 * Redirect users to TFA setup page default to disabled.
 */
function tfa_post_update_add_redirect_to_users_without_tfa(array &$sandbox): void {
  $config = \Drupal::configFactory()->getEditable('tfa.settings');
  $config->set('users_without_tfa_redirect', FALSE);
  $config->save();
}
