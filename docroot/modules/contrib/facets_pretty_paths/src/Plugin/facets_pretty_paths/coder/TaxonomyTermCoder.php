<?php

namespace Drupal\facets_pretty_paths\Plugin\facets_pretty_paths\coder;

use Drupal\facets_pretty_paths\Coder\CoderPluginBase;
use Drupal\taxonomy\Entity\Term;

/**
 * Banana facets pretty paths coder.
 *
 * @FacetsPrettyPathsCoder(
 *   id = "taxonomy_term_coder",
 *   label = @Translation("Taxonomy term name + id"),
 *   description = @Translation("Use term name with term id, e.g. /color/<strong>blue-2</strong>")
 * )
 */
class TaxonomyTermCoder extends CoderPluginBase {

  /**
   * Encode an id into an alias.
   *
   * @param string $id
   *   An entity id.
   *
   * @return string
   *   An alias.
   */
  public function encode($id) {
    if ($term = Term::load($id)) {
      $term_trans = \Drupal::service('entity.repository')->getTranslationFromContext($term);
      $term_name = $term_trans->get('name')->value;
      $term_name = \Drupal::service('pathauto.alias_cleaner')
        ->cleanString($term_name);
      return $term_name . '-' . $id;
    }
    return $id;
  }

  /**
   * Decodes an alias back to an id.
   *
   * @param string $alias
   *   An alias.
   *
   * @return string
   *   An id.
   */
  public function decode($alias) {
    $exploded = explode('-', $alias);
    $id = array_pop($exploded);

    return $id;
  }

}
