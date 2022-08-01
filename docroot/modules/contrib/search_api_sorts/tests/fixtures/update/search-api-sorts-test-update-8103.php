<?php
// @codingStandardsIgnoreFile

/**
 * @file
 * Contains database additions to search-api-sorts-db-base.php.
 *
 * Used for testing the search_api_sorts_update_8103() update.
 *
 * @see \Drupal\Tests\search_api_sorts\Functional\Update\SearchApiSortsUpdate8103Test
 */

use Drupal\Core\Database\Database;

$connection = Database::getConnection();

// Update core.extension.
$extensions = $connection->select('config')
  ->fields('config', ['data'])
  ->condition('collection', '')
  ->condition('name', 'core.extension')
  ->execute()
  ->fetchField();
$extensions = unserialize($extensions);
$extensions['module']['language'] = 0;
$connection->update('config')
  ->fields([
    'data' => serialize($extensions),
  ])
  ->condition('collection', '')
  ->condition('name', 'core.extension')
  ->execute();

$connection->insert('key_value')
  ->fields([
    'collection',
    'name',
    'value',
  ])
  ->values([
    'collection' => 'system.schema',
    'name' => 'search_api_sorts',
    'value' => 'i:8102;',
  ])
  ->execute();
