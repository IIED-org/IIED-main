<?php

namespace Drupal\term_reference_tree\Element;

use Drupal\Core\Render\Element\FormElementBase;

/**
 * Provides a form element for term reference tree.
 *
 * @FormElement("checkbox_tree_label")
 */
class CheckboxTreeLabel extends FormElementBase {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return [
      '#input' => FALSE,
      '#theme' => 'checkbox_tree_label',
    ];
  }

}
