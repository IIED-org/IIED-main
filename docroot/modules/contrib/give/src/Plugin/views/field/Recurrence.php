<?php

namespace Drupal\give\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Field handler to provide the label of a give form.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("give_recurrence")
 */
class Recurrence extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    if ($give_form = $values->_entity->give_form->entity) {
      if ($val = $this->getValue($values)) {
        $frequencies = $give_form->getFrequencies();
        if (isset($frequencies[$val])) {
          return $frequencies[$val]['description'];
        }
      }
    }
  }
}
