<?php

namespace Drupal\ds\Plugin\DsFieldTemplate;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ds\Attribute\DsFieldTemplate;

/**
 * Plugin for the minimal field template.
 */
#[DsFieldTemplate(
  id: 'minimal',
  title: new TranslatableMarkup('Minimal'),
  theme: 'ds_field_minimal'
)]
class Minimal extends DsFieldTemplateBase {

}
