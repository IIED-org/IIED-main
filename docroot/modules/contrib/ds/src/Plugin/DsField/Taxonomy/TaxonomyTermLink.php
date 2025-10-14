<?php

namespace Drupal\ds\Plugin\DsField\Taxonomy;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ds\Attribute\DsField;
use Drupal\ds\Plugin\DsField\Link;

/**
 * Plugin that renders the read more link on taxonomy.
 */
#[DsField(
  id: 'taxonomy_term_link',
  title: new TranslatableMarkup('Read more'),
  entity_type: 'taxonomy_term',
  provider: 'taxonomy'
)]
class TaxonomyTermLink extends Link {

}
