<?php

namespace Drupal\ds_test\Plugin\DsField;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ds\Attribute\DsField;
use Drupal\ds\Plugin\DsField\DsFieldBase;

/**
 * Test field plugin that returns zero as an integer.
 */
#[DsField(
  id: 'test_field_zero_int',
  title: new TranslatableMarkup('Test field plugin that returns zero as an integer'),
  entity_type: 'node'
)]
class TestFieldZeroInt extends DsFieldBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return ['#markup' => 0];
  }

}
