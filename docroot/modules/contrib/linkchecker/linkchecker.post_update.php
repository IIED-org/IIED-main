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
