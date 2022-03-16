<?php

/**
 * @file
 * API documentation for Layout Paragraphs module.
 */

use Drupal\Core\Form\FormStateInterface;

/**
 * Alter the Layout Paragraph Element Form.
 *
 * Allow others modules to adjust the Layout Paragraph Element Dialog Form..
 *
 * @param array $element_form
 *   The Layout Paragraph Element Form.
 * @param \Drupal\Core\Form\FormStateInterface $element_form_state
 *   The Layout Paragraph Element Form State.
 * @param array $parent_form
 *   The Parent Form.
 * */
function hook_layout_paragraph_element_form_alter(array &$element_form, FormStateInterface &$element_form_state, array $parent_form) {
  // Make custom alterations to adjust the Layout Paragraph Element Form..
}
