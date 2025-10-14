<?php

namespace Drupal\ds_test\Plugin\DsField;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ds\Attribute\DsField;
use Drupal\ds\Plugin\DsField\DsFieldBase;

/**
 * Test field plugin that returns NULL.
 */
#[DsField(
  id: 'test_field_null',
  title: new TranslatableMarkup('Test field plugin that returns NULL'),
  entity_type: 'node'
)]
class TestFieldNull extends DsFieldBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return NULL;
  }

}
