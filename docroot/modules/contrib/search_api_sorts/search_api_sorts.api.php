<?php

/**
 * @file
 * Hooks provided by the Search API sorts module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alter the active sort.
 *
 * Modules may implement this hook to alter the active sort if an active sort
 * is specified. This hook allows altering both the field and the order. If
 * your logic also applies to the default sort, remember to implement
 * hook_search_api_sorts_default_sort_alter too.
 *
 * @param \Drupal\search_api_sorts\SortsField $sort
 *   The Search API sorts object, containing the field name and order.
 * @param \Drupal\search_api\Display\DisplayInterface $display
 *   The search api display for which the active sort is executed.
 *
 * @see \Drupal\search_api_sorts\SearchApiSortsManager
 */
function hook_search_api_sorts_active_sort_alter(\Drupal\search_api_sorts\SortsField &$sort, \Drupal\search_api\Display\DisplayInterface $display) {

  // Example: use different price for anonymous users when sorting on price.
  if ($sort->getFieldName() === "price" && \Drupal::currentUser()->isAnonymous()) {
    $sort->setFieldName("price_anonymous");
    $sort->setOrder("desc");
  }
}

/**
 * Alter the default sort.
 *
 * Modules may implement this hook to alter the default sort that will be used
 * when no specific sort is chosen. This hook allows altering both the field
 * and the order.
 *
 * @param \Drupal\search_api_sorts\SortsField $sort
 *   The Search API sorts object, containing the field name and order.
 * @param \Drupal\search_api\Display\DisplayInterface $display
 *   The search api display for which the default sort is executed.
 *
 * @see \Drupal\search_api_sorts\SearchApiSortsManager
 */
function hook_search_api_sorts_default_sort_alter(\Drupal\search_api_sorts\SortsField &$sort, \Drupal\search_api\Display\DisplayInterface $display) {
  $sort->setFieldName("title");
  $sort->setOrder("desc");
}

/**
 * @} End of "addtogroup hooks".
 */
