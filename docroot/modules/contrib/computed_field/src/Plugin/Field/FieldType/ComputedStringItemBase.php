<?php

namespace Drupal\computed_field\Plugin\Field\FieldType;

use Drupal\Core\Field\Plugin\Field\FieldType\StringItemBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin base of the string field type.
 */
abstract class ComputedStringItemBase extends StringItemBase {

  use ComputedFieldItemTrait {
    fieldSettingsForm as getFieldSettingsFormBase;
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    return $this->getFieldSettingsFormBase($form, $form_state);
  }

}
