<?php

namespace Drupal\ds_test\Plugin\DsField;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ds\Attribute\DsField;
use Drupal\ds\Plugin\DsField\DsFieldBase;

/**
 * Test field plugin that returns zero as a string.
 */
#[DsField(
  id: 'test_field_zero_string',
  title: new TranslatableMarkup('Test field plugin that returns zero as a string'),
  entity_type: 'node'
)]
class TestFieldZeroString extends DsFieldBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return ['#markup' => '0'];
  }

}
