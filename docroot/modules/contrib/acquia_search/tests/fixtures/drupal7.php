<?php

/**
 * @file
 * A database agnostic dump for testing purposes.
 */

use Drupal\Core\Database\Database;

$connection = Database::getConnection();

$connection->insert('variable')
  ->fields([
    'name',
    'value',
  ])
  ->values([
    'name' => 'acquia_search_api_host',
    'value' => 's:32:"https://api.sr-prod02.acquia.com";',
  ])
  ->values([
    'name' => 'acquia_search_solr_forced_read_only',
    'value' => 'b:1;',
  ])
  ->values([
    'name' => 'apachesolr_default_environment',
    'value' => 's:22:"acquia_search_server_3";',
  ])
  ->execute();

$connection->schema()->createTable('apachesolr_index_bundles', [
  'fields' => [
    'env_id' => [
      'type' => 'varchar',
      'not null' => FALSE,
      'length' => '255',
    ],
    'entity_type' => [
      'type' => 'varchar',
      'not null' => FALSE,
      'length' => '255',
    ],
    'bundle' => [
      'type' => 'varchar',
      'not null' => FALSE,
      'length' => '255',
    ],
  ],
  'mysql_character_set' => 'utf8',
]);

$connection->insert('apachesolr_index_bundles')
  ->fields([
    'env_id',
    'entity_type',
    'bundle',
  ])
  ->values([
    'env_id' => 'acquia_search_server_3',
    'entity_type' => 'node',
    'bundle' => 'article',
  ])
  ->values([
    'env_id' => 'acquia_search_server',
    'entity_type' => 'node',
    'bundle' => 'page',
  ])
  ->execute();

$connection->insert('system')
  ->fields([
    'filename',
    'name',
    'type',
    'owner',
    'status',
    'bootstrap',
    'schema_version',
    'weight',
    'info',
  ])
  ->values([
    'filename' => 'sites/all/modules/contrib/acquia_connector/acquia_search/acquia_search.module',
    'name' => 'acquia_search',
    'type' => 'module',
    'owner' => '',
    'status' => '1',
    'bootstrap' => '0',
    'schema_version' => '7002',
    'weight' => '0',
    'info' => 'a:6:{s:5:"label";s:4:"Body";s:6:"widget";a:4:{s:4:"type";s:26:"text_textarea_with_summary";s:8:"settings";a:2:{s:4:"rows";i:20;s:12:"summary_rows";i:5;}s:6:"weight";i:-4;s:6:"module";s:4:"text";}s:8:"settings";a:3:{s:15:"display_summary";b:1;s:15:"text_processing";i:1;s:18:"user_register_form";b:0;}s:7:"display";a:2:{s:7:"default";a:5:{s:5:"label";s:6:"hidden";s:4:"type";s:12:"text_default";s:6:"weight";s:1:"0";s:8:"settings";a:1:{s:21:"field_formatter_class";s:16:"heyyy destitaion";}s:6:"module";s:4:"text";}s:6:"teaser";a:5:{s:5:"label";s:6:"hidden";s:4:"type";s:23:"text_summary_or_trimmed";s:8:"settings";a:2:{s:11:"trim_length";i:600;s:21:"field_formatter_class";s:0:"";}s:6:"module";s:4:"text";s:6:"weight";i:0;}}s:8:"required";b:0;s:11:"description";s:0:"";}',
  ])
  ->execute();
