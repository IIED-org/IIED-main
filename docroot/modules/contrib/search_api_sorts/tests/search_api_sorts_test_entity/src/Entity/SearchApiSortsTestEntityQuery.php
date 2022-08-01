<?php

namespace Drupal\search_api_sorts_test_entity\Entity;

use Drupal\Core\Config\Entity\Query\Query;

/**
 * Class SearchApiSortsTestEntityQuery.
 */
class SearchApiSortsTestEntityQuery extends Query {

  /**
   * Loads all the matching entities for validation.
   *
   * @return array
   *   All the records.
   */
  public function getRecords() {
    $this->condition('display_id', 'views_page---search_api_sorts_test_view__page_1', 'IN');
    $this->condition('status', TRUE, 'IN');

    return $this->loadRecords();
  }

}
