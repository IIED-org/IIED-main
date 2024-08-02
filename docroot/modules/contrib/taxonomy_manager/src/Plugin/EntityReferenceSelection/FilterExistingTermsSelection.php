<?php

declare(strict_types=1);

namespace Drupal\taxonomy_manager\Plugin\EntityReferenceSelection;

use Drupal\taxonomy\Plugin\EntityReferenceSelection\TermSelection;

/**
 * Provides an additional filtering to exclude source terms.
 *
 * @EntityReferenceSelection(
 *   id = "default:filter_existing_terms",
 *   label = @Translation("Taxonomy term limit selection"),
 *   entity_types = {"taxonomy_term"},
 *   group = "default",
 *   weight = 1
 * )
 */
class FilterExistingTermsSelection extends TermSelection {

  /**
   * {@inheritdoc}
   */
  protected function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS') {
    $query = parent::buildEntityQuery($match, $match_operator);
    $handler_settings = $this->configuration;
    if (!isset($handler_settings['filter'])) {
      return $query;
    }
    $filter_settings = $handler_settings['filter'];
    foreach ($filter_settings as $field_name => $value) {
      $query->condition($field_name, $value, is_array($value) ? 'NOT IN' : '<>');
    }
    return $query;
  }

}
