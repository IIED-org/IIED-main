<?php

/**
 * @file
 * Post update functions for Link checker.
 */

use Drupal\views\Views;

/**
 * Set an empty value for the last_check field in the broken_links_report view.
 */
function linkchecker_post_update_last_check_empty_value() {
  if (!\Drupal::moduleHandler()->moduleExists('views')) {
    return t('The views module is not enabled, so there is nothing to update.');
  }
  $view = Views::getView('broken_links_report');

  if (!$view) {
    return t("The broken_links_report view could not be updated because it doesn't exist");
  }

  $display = &$view->storage->getDisplay('default');
  $fields = $display['display_options']['fields'];

  if (!isset($fields['last_check']) || !empty($fields['last_check']['empty'])) {
    return t("The last_check field doesn't exist or the empty field already contains a value.");
  }

  $fields['last_check']['empty'] = 'Never';
  $display['display_options']['fields'] = $fields;
  $view->storage->save(TRUE);

  return t('The view "broken_links_report" has been updated successfully.');
}

/**
 * Implements hook_post_update_NAME().
 */
function linkchecker_post_update_remove_orphaned_queue_data(&$sandbox): void {
  $database_connection = \Drupal::database();
  // Find linkchecker entities ids that exist in queue table but were removed as
  // entities.
  $queued_items_data = $database_connection
    ->select('queue', 'q')
    ->fields('q', ['data'])
    ->condition('name', ['linkchecker_status_handle', 'linkchecker_check'], 'IN')
    ->execute()
    ->fetchCol();

  $entities_ids = $database_connection
    ->select('linkchecker_link', 'll')
    ->fields('ll', ['lid'])
    ->execute()
    ->fetchCol();
  $orphaned_links = [];
  foreach ($queued_items_data as $item_data) {
    // $item_data is a serialized string so un-serialize it.
    $item_data = unserialize($item_data);
    // Check if we're processing status handle queue.
    if (isset($item_data['links'])) {
      $item_id = reset($item_data['links']);
      if (!in_array($item_id, $entities_ids, TRUE)) {
        $orphaned_links[] = $item_id;
      }
    }
    else {
      // Otherwise - un-serialized data is an array of ids.
      $diff = array_diff($item_data, $entities_ids);
      if (!empty($diff)) {
        $orphaned_links = array_merge($orphaned_links, $diff);
      }
    }
  }

  /** @var \Drupal\linkchecker\LinkCleanUp $clean_up */
  $clean_up = \Drupal::service('linkchecker.clean_up');
  foreach ($orphaned_links as $orphaned_link) {
    $clean_up->cleanUpQueues($orphaned_link);
  }
}
