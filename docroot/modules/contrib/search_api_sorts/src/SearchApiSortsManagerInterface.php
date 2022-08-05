<?php

namespace Drupal\search_api_sorts;

use Drupal\search_api\Display\DisplayInterface;
use Drupal\search_api\IndexInterface;

/**
 * Defines the interface for the search api sort manager.
 */
interface SearchApiSortsManagerInterface {

  /**
   * Returns the active sort field and order for a given search api display.
   *
   * @param \Drupal\search_api\Display\DisplayInterface $display
   *   The display where the active sort should be returned for.
   *
   * @return \Drupal\search_api_sorts\SortsField
   *   An object containing the field and order.
   */
  public function getActiveSort(DisplayInterface $display);

  /**
   * Returns the default sort field and order.
   *
   * @param \Drupal\search_api\Display\DisplayInterface $display
   *   The display where the default sort should be returned for.
   *
   * @return \Drupal\search_api_sorts\SortsField
   *   An object containing the field and order.
   */
  public function getDefaultSort(DisplayInterface $display);

  /**
   * Returns all enabled sort fields for a given search api display.
   *
   * @param \Drupal\search_api\Display\DisplayInterface $display
   *   The display where the enabled sorts should be returned for.
   *
   * @return \Drupal\search_api_sorts\Entity\SearchApiSortsField[]
   *   An array of sort field entities.
   */
  public function getEnabledSorts(DisplayInterface $display);

  /**
   * Remove all deleted sorts for the given index.
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   The search api index.
   */
  public function cleanupSortFields(IndexInterface $index);

}
