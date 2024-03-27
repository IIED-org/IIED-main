<?php

/**
 * @file
 * Post update functions for View password module.
 */

/**
 * Moves configuration to own namespace.
 */
function view_password_post_update_move_configuration() {
  $factory = \Drupal::configFactory();
  $config = $factory->getEditable('pwd.settings');

  $factory->getEditable('view_password.settings')
    ->set('form_ids', $config->get('pwd.form_id_pwd'))
    ->save(TRUE);
  $config->delete();
}
