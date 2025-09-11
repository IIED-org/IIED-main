<?php

namespace Drupal\ds_test\Plugin\DsField;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ds\Attribute\DsField;
use Drupal\ds\Plugin\DsField\DsFieldBase;

/**
 * Test field plugin that returns zero as a floating point number.
 */
#[DsField(
  id: 'test_field_zero_float',
  title: new TranslatableMarkup('Test field plugin that returns zero as a floating point number'),
  entity_type: 'node'
)]
class TestFieldZeroFloat extends DsFieldBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return ['#markup' => 0.0];
  }

}
