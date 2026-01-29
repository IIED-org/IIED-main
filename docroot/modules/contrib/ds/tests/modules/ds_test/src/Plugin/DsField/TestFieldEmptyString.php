<?php

namespace Drupal\ds_test\Plugin\DsField;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ds\Attribute\DsField;
use Drupal\ds\Plugin\DsField\DsFieldBase;

/**
 * Test field plugin that returns an empty string.
 */
#[DsField(
  id: 'test_field_empty_string',
  title: new TranslatableMarkup('Test field plugin that returns an empty string'),
  entity_type: 'node'
)]
class TestFieldEmptyString extends DsFieldBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return ['#markup' => ''];
  }

}
