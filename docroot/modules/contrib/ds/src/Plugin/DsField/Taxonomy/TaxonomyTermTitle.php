<?php

namespace Drupal\ds\Plugin\DsField\Taxonomy;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ds\Attribute\DsField;
use Drupal\ds\Plugin\DsField\Title;

/**
 * Plugin that renders the title of a term.
 */
#[DsField(
  id: 'taxonomy_term_title',
  title: new TranslatableMarkup('Name'),
  entity_type: 'taxonomy_term',
  provider: 'taxonomy'
)]
class TaxonomyTermTitle extends Title {

  /**
   * {@inheritdoc}
   */
  public function entityRenderKey() {
    return 'name';
  }

}
