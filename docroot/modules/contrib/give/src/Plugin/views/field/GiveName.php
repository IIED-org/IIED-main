<?php

namespace Drupal\give\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Field handler to provide the name of the donor, based on the donation owner
 * for authenticated users, or the stored name otherwise
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("give_name")
 */
class GiveName extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $donation = $values->_entity;
    if ($donation->getOwnerId()) {
      return $donation->getOwner()->toLink()->toRenderable();
    }
    else {
      // The 'name' property of the donation.
      return $this->getValue($values);
    }
  }
}
