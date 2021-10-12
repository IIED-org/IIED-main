<?php

namespace Drupal\computed_field\Plugin\Field\FieldType;

use Drupal\Core\Form\FormStateInterface;

/**
 * Common methods for Computed Field FieldType plugins.
 *
 * The FieldType plugins in this module descend from either FieldItemBase
 * (numbers via ComputedFieldItemBase) or StringItemBase (strings via
 * ComputedStringItemBase). As they have no common ancestry outside of Core,
 * it's necessary to introduce this trait to prevent code duplication across
 * hierarchies.
 */
trait ComputedFieldItemTrait {

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->getRawResult();
    if (($value === NULL) || $value === '') {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Fetches the raw result of the computation.
   *
   * @return mixed
   */
  protected function getRawResult() {
    return $this->executeCode();
  }

  /**
   * {@inheritdoc}
   */
  public function preSave() {
    $value = $this->executeCode();
    $this->setValue($value);
  }

  /**
   * Performs the field value computation.
   *
   * If this method is being overridden to return a typed result, the class must
   * use ComputedFieldStronglyTypedItemTrait to ensure access to raw results.
   *
   * @see ComputedFieldStronglyTypedItemTrait
   */
  public function executeCode() {
    $entity = $this->getEntity();
    $delta = $this->name;
    $field_name = $this->definition->getFieldDefinition()->getName();
    $value = \Drupal::service('computed_field.helpers')->executeCode($field_name, $entity, $delta);

    return $value;
  }

  /**
   * Default field settings form.
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $field_name = $this->definition->getFieldDefinition()->getName();
    $service = \Drupal::service('computed_field.helpers');
    $element = [];

    $element['hook_info'] = [
      '#markup' => t('
<p>The hook implementation function signature should be
  <strong>%function($entity_type_manager, $entity, $fields, $delta)</strong>,
  and the desired value should be returned.</em>
</p>
<p>The variables available to your code include:</p>
<ul>
  <li><code>$entity_type_manager</code>: The entity type manager.</li>
  <li><code>$entity</code>: The entity the field belongs to.</li>
  <li><code>$fields</code>: The list of fields available in this entity.</li>
  <li><code>$delta</code>: Current index of the field in case of multi-value computed fields (counting from 0).</li>
  <li><code>$value</code>: The resulting value to be set above, or returned in your hook implementation).</li>
</ul>
      ', ['%function' => $service->getComputeFunctionName($field_name)]),
    ];
    return $element;
  }

}
