<?php

namespace Drupal\give\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Field handler to provide the label of a give form.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("give_amount")
 */
class GiveAmount extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $value = $this->getValue($values);
    return format_stripe_currency($value);
  }

}
