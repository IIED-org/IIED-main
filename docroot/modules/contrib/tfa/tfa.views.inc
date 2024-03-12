<?php

/**
 * @file
 * TFA views configuration.
 */

/**
 * Implements hook_views_data_alter().
 */
function tfa_views_data_alter(array &$data) {
  $data['users']['tfa_enabled_field'] = [
    'title' => t('TFA enabled'),
    'real field' => 'uid',
    'field' => [
      'title' => t('TFA enabled'),
      'help' => t('Whether the user has enabled two-factor authentication.'),
      'id' => 'tfa_enabled_field',
    ],
  ];
}
