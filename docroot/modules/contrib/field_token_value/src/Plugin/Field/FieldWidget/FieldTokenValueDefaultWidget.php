<?php

namespace Drupal\field_token_value\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the default field widget for the field_token_value field type.
 *
 * @FieldWidget(
 *   id = "field_token_value_default",
 *   module = "field_token_value",
 *   label = @Translation("Field Token Value"),
 *   field_types = {
 *     "field_token_value"
 *   }
 * )
 */
class FieldTokenValueDefaultWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    // Essentially just saves the field setting as a hidden form element to
    // ensure the presave is run. Presave will only run if a field value is set.
    $element['value'] = [
      '#type' => 'hidden',
      '#value' => $this->getFieldSetting('field_value'),
    ];

    return $element;
  }

}
