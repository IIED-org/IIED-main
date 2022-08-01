<?php
// @codingStandardsIgnoreFile

/**
 * @file
 * Contains database additions to search-api-db-base.php.
 *
 * Can be used for setting up a base Search API sorts installation.
 */

use Drupal\Core\Database\Database;

$connection = Database::getConnection();

$connection->insert('config')
  ->fields([
    'collection',
    'name',
    'data',
  ])
  ->values([
    'collection' => 'system.schema',
    'name' => 'search_api_sorts',
    'data' => 'i:8101;',
  ])
  ->execute();

// Update core.extension.
$extensions = $connection->select('config')
  ->fields('config', ['data'])
  ->condition('collection', '')
  ->condition('name', 'core.extension')
  ->execute()
  ->fetchField();
$extensions = unserialize($extensions);
$extensions['module']['search_api_sorts'] = 0;
$connection->update('config')
  ->fields([
    'data' => serialize($extensions),
  ])
  ->condition('collection', '')
  ->condition('name', 'core.extension')
  ->execute();
