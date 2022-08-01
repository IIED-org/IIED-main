<?php
// @codingStandardsIgnoreFile

/**
 * @file
 * Contains database additions to search-api-sorts-db-base.php.
 *
 * Used for testing the search_api_sorts_update_8102() update.
 *
 * @see \Drupal\Tests\search_api_sorts\Functional\Update\SearchApiSortsUpdate8102Test
 */

use Drupal\Core\Database\Database;

$connection = Database::getConnection();

$connection->insert('key_value')
  ->fields([
    'collection',
    'name',
    'value',
  ])
  ->values([
    'collection' => 'system.schema',
    'name' => 'search_api_sorts',
    'value' => 'i:8101;',
  ])
  ->execute();
