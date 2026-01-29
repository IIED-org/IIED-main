<?php

namespace Drupal\ds\Plugin\DsFieldTemplate;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ds\Attribute\DsFieldTemplate;

/**
 * Plugin for the default field template.
 */
#[DsFieldTemplate(
  id: 'default',
  title: new TranslatableMarkup('Default'),
  theme: 'field'
)]
class DefaultField extends DsFieldTemplateBase {

}
