<?php

namespace Drupal\ds_test\Plugin\DsFieldTemplate;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ds\Attribute\DsFieldTemplate;
use Drupal\ds\Plugin\DsFieldTemplate\DsFieldTemplateBase;

/**
 * Plugin for the expert field template.
 */
#[DsFieldTemplate(
  id: 'ds_test_template',
  title: new TranslatableMarkup('Field test function'),
  theme: 'ds_test_template'
)]
class TestLayout extends DsFieldTemplateBase {

}
