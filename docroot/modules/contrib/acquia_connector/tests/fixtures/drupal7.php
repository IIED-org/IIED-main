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
    'name' => 'acquia_subscription_name',
    'value' => 's:4:"Test";',
  ])
  ->values([
    'name' => 'acquia_agent_debug',
    'value' => 'b:0;',
  ])
  ->values([
    'name' => 'acquia_spi_cron_interval',
    'value' => 'i:30;',
  ])
  ->values([
    'name' => 'acquia_spi_cron_interval_override',
    'value' => 'i:0;',
  ])
  ->values([
    'name' => 'acquia_agent_hide_signup_messages',
    'value' => 'i:0;',
  ])
  ->values([
    'name' => 'acquia_spi_server',
    'value' => 's:23:"https://nspi.acquia.com";',
  ])
  ->values([
    'name' => 'acquia_spi_ssl_override',
    'value' => 'b:0;',
  ])
  ->values([
    'name' => 'acquia_agent_verify_peer',
    'value' => 'b:1;',
  ])
  ->values([
    'name' => 'acquia_spi_admin_priv',
    'value' => 'i:1;',
  ])
  ->values([
    'name' => 'acquia_spi_send_node_user',
    'value' => 'i:1;',
  ])
  ->values([
    'name' => 'acquia_spi_send_watchdog',
    'value' => 'i:1;',
  ])
  ->values([
    'name' => 'acquia_spi_use_cron',
    'value' => 'i:1;',
  ])
  ->values([
    'name' => 'acquia_dynamic_banner',
    'value' => 'i:0;',
  ])
  ->values([
    'name' => 'acquia_spi_set_variables_override',
    'value' => 'i:0;',
  ])
  ->values([
    'name' => 'acquia_spi_set_variables_automatic',
    'value' => 'a:13:{i:0;s:35:"acquia_spi_set_variables_automatic ";i:1;s:11:"error_level";i:2;s:13:"preprocess_js";i:3;s:22:"page_cache_maximum_age";i:4;s:11:"block_cache";i:5;s:14:"preprocess_css";i:6;s:16:"page_compression";i:7;s:32:"image_allow_insecure_derivatives";i:8;s:21:"googleanalytics_cache";i:9;s:25:"acquia_spi_send_node_user";i:10;s:21:"acquia_spi_admin_priv";i:11;s:24:"acquia_spi_send_watchdog";i:12;s:19:"acquia_spi_use_cron";}',
  ])
  ->values([
    'name' => 'acquia_spi_ignored_set_variables',
    'value' => 'a:0:{};',
  ])
  ->values([
    'name' => 'acquia_spi_saved_variables',
    'value' => 'a:2:{s:9:"variables";a:0:{}s:4:"time";i:0;};',
  ])
  ->values([
    'name' => 'acquia_subscription_data',
    'value' => 'a:12:{s:9:"timestamp";i:1234567890;s:6:"active";s:1:"1";s:4:"href";s:83:"https://insight.acquia.com/node/uuid/1b2c3456-a123-456d-a789-e1234567895d/dashboard";s:4:"uuid";s:36:"1b2c3456-a123-456d-a789-e1234567895d";s:17:"subscription_name";s:4:"Test";s:15:"expiration_date";a:1:{s:5:"value";s:19:"2042-12-30T00:00:00";}s:7:"product";a:1:{s:4:"view";s:14:"Acquia Network";}s:16:"derived_key_salt";s:32:"1234e56789979a1d8ae123cd321a12c7";s:14:"update_service";s:1:"1";s:22:"search_service_enabled";i:1;s:6:"update";a:0:{}s:14:"heartbeat_data";a:4:{s:11:"acquia_lift";a:3:{s:6:"status";b:0;s:8:"decision";a:2:{s:10:"public_key";s:0:"";s:11:"private_key";s:0:"";}s:7:"profile";a:5:{s:12:"account_name";s:0:"";s:8:"hostname";s:0:"";s:10:"public_key";s:0:"";s:10:"secret_key";s:0:"";s:7:"js_path";s:0:"";}}s:22:"search_service_enabled";i:1;s:12:"search_cores";a:7:{i:0;a:2:{s:8:"balancer";s:28:"useast1-c1.acquia-search.com";s:7:"core_id";s:11:"TEST-123456";}i:1;a:2:{s:8:"balancer";s:28:"useast1-c1.acquia-search.com";s:7:"core_id";s:19:"TEST-123456.prod.v2";}i:2;a:2:{s:8:"balancer";s:28:"useast1-c1.acquia-search.com";s:7:"core_id";s:19:"TEST-123456.test.v2";}i:3;a:2:{s:8:"balancer";s:28:"useast1-c1.acquia-search.com";s:7:"core_id";s:18:"TEST-123456.dev.v2";}i:4;a:2:{s:8:"balancer";s:29:"useast1-c26.acquia-search.com";s:7:"core_id";s:24:"TEST-123456.prod.default";}i:5;a:2:{s:8:"balancer";s:29:"useast1-c26.acquia-search.com";s:7:"core_id";s:24:"TEST-123456.test.default";}i:6;a:2:{s:8:"balancer";s:29:"useast1-c26.acquia-search.com";s:7:"core_id";s:23:"TEST-123456.dev.default";}}s:21:"search_service_colony";s:28:"useast1-c1.acquia-search.com";}}',
  ])
  ->execute();
