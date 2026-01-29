<?php

namespace Drupal\ds_test\Plugin\DsField;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ds\Attribute\DsField;
use Drupal\ds\Plugin\DsField\DsFieldBase;

/**
 * Test field plugin.
 */
#[DsField(
  id: 'test_field',
  title: new TranslatableMarkup('Test field plugin'),
  entity_type: 'node'
)]
class TestField extends DsFieldBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return ['#markup' => 'Test field plugin on node ' . $this->entity()->id()];
  }

}
