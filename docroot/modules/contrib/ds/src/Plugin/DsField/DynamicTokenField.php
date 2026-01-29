<?php

namespace Drupal\ds\Plugin\DsField;

use Drupal\ds\Attribute\DsField;
use Drupal\ds\Plugin\Derivative\DynamicTokenField as DynamicTokenFieldDerivative;

/**
 * Defines a generic dynamic code field.
 */
#[DsField(
  id: 'dynamic_token_field',
  deriver: DynamicTokenFieldDerivative::class
)]
class DynamicTokenField extends TokenBase {

  /**
   * {@inheritdoc}
   */
  public function content() {
    $definition = $this->getPluginDefinition();
    return $definition['properties']['content']['value'];
  }

  /**
   * {@inheritdoc}
   */
  public function format() {
    $definition = $this->getPluginDefinition();
    return $definition['properties']['content']['format'];
  }

}
