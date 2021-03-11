<?php

namespace Drupal\textfield_counter\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\StringTextareaWidget;

/**
 * Plugin implementation of the 'string_textarea_with_counter' widget.
 *
 * @FieldWidget(
 *   id = "string_textarea_with_counter",
 *   label = @Translation("Textarea (multiple rows) with counter"),
 *   field_types = {
 *     "string_long"
 *   }
 * )
 */
class StringTextareaWithCounterWidget extends StringTextareaWidget {

  use TextFieldCounterWidgetTrait;

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'maxlength' => 0,
      'counter_position' => 'after',
      'js_prevent_submit' => TRUE,
      'count_html_characters' => TRUE,
      'textcount_status_message' => self::getDefaultTextCountStatusMessage(),
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    $this->addMaxlengthSettingsFormElement($form);
    $this->addCounterPositionSettingsFormElement($form);
    $this->addJsPreventSubmitSettingsFormElement($form);
    $this->addCountHtmlSettingsFormElement($form);
    $this->addTextCountStatusMessageSettingsFormElement($form);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    $this->addMaxlengthSummary($summary);
    $this->addPositionSummary($summary);
    $this->addJsSubmitPreventSummary($summary);
    $this->addCountHtmlSummary($summary);
    $this->addTextCountStatusMessageSummary($summary);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    if ($maxlength = $this->getSetting('maxlength')) {
      $entity = $items->getEntity();
      $field_defintion = $items->getFieldDefinition();
      $this->fieldFormElement($element['value'], $entity, $field_defintion, $delta);
      $count_html_characters = $this->getSetting('count_html_characters');
      if (isset($element['value'])) {
        $element['value']['#textfield-maxlength'] = $maxlength;
        $element['value']['#textfield-count-html'] = $count_html_characters;
      }
      $element['#textfield-maxlength'] = $maxlength;
      $element['#textfield-count-html'] = $count_html_characters;
      $classes = class_uses($this);
      if (count($classes)) {
        $element['#element_validate'][] = [array_pop($classes), 'validateFieldFormElement'];
      }
    }

    return $element;
  }

}
