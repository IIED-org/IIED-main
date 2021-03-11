<?php

namespace Drupal\textfield_counter\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Textfield counter trait. Adds textfield counting functionality.
 */
trait TextFieldCounterWidgetTrait {

  /**
   * Adds a form element to set the maximum number of characters allowed.
   *
   * @param array $form
   *   The form render array to which the element should be added.
   * @param bool $includeDefaultSettings
   *   A boolean indicating whether or not to allow an override of the max
   *   length based on the default setting for the field. This should be set to
   *   true for textfields (textareas will not have a default setting for the
   *   field).
   */
  public function addMaxlengthSettingsFormElement(array &$form, $includeDefaultSettings = FALSE) {
    if ($includeDefaultSettings) {
      $form['use_field_maxlength'] = [
        '#title' => t(
          'Set maximum number of characters to field default (%character_count characters)',
          [
            '%character_count' => $this->formatPlural(
              $this->getFieldSetting('max_length'),
              '1 character',
              '@count characters'
            ),
          ]
        ),
        '#type' => 'checkbox',
        '#default_value' => $this->getSetting('use_field_maxlength'),
      ];
    }

    $form['maxlength'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum number of characters'),
      '#min' => 0,
      '#default_value' => $this->getSetting('maxlength'),
      '#description' => $this->t('Setting this value to zero will disable the counter on textareas.'),
    ];

    if ($includeDefaultSettings) {
      $form['maxlength']['#states']['visible'][':input[name="fields[' . $this->fieldDefinition->getName() . '][settings_edit_form][settings][use_field_maxlength]"]'] = ['checked' => FALSE];
    }
  }

  /**
   * Adds a form element to set the position of the text counter.
   *
   * @param array $form
   *   The form render array to which the element should be added.
   * @param bool $storageSettingMaxlengthField
   *   Whether or not the field has storage settings that include a maximum
   *   length. Such fields allow for using the storage settings rather than the
   *   wiget setting.
   */
  public function addCounterPositionSettingsFormElement(array &$form, $storageSettingMaxlengthField = FALSE) {
    $form['counter_position'] = [
      '#type' => 'select',
      '#title' => $this->t('Counter position'),
      '#options' => [
        'before' => $this->translateValue('before'),
        'after' => $this->translateValue('after'),
      ],
      '#default_value' => $this->getSetting('counter_position'),
    ];

    if ($storageSettingMaxlengthField) {
      $form['counter_position']['#states'] = [
        'invisible' => [
          ':input[name="fields[' . $this->fieldDefinition->getName() . '][settings_edit_form][settings][use_field_maxlength]"]' => ['checked' => FALSE],
          ':input[name="fields[' . $this->fieldDefinition->getName() . '][settings_edit_form][settings][maxlength]"]' => ['value' => 0],
        ],
      ];
    }
    else {
      $form['counter_position']['#states'] = [
        'invisible' => [
          ':input[name="fields[' . $this->fieldDefinition->getName() . '][settings_edit_form][settings][maxlength]"]' => ['value' => 0],
        ],
      ];
    }
  }

  /**
   * Adds a form element to toggle JS prevention of form submission on error.
   *
   * @param array $form
   *   The form render array to which the element should be added.
   * @param bool $storageSettingMaxlengthField
   *   Whether or not the field has storage settings that include a maximum
   *   length. Such fields allow for using the storage settings rather than the
   *   wiget setting.
   */
  public function addJsPreventSubmitSettingsFormElement(array &$form, $storageSettingMaxlengthField = FALSE) {
    $form['js_prevent_submit'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Prevent form submission when character limit exceeded'),
      '#description' => $this->t('Prevent form submission using JavaScript if the user has gone over the allowed character count.'),
      '#default_value' => $this->getSetting('js_prevent_submit'),
    ];

    if ($storageSettingMaxlengthField) {
      $form['js_prevent_submit']['#states'] = [
        'invisible' => [
          [':input[name="fields[' . $this->fieldDefinition->getName() . '][settings_edit_form][settings][use_field_maxlength]"]' => ['checked' => TRUE]],
          'or',
          [':input[name="fields[' . $this->fieldDefinition->getName() . '][settings_edit_form][settings][maxlength]"]' => ['value' => 0]],
        ],
      ];
    }
    else {
      $form['js_prevent_submit']['#states'] = [
        'invisible' => [
          ':input[name="fields[' . $this->fieldDefinition->getName() . '][settings_edit_form][settings][maxlength]"]' => ['value' => 0],
        ],
      ];
    }
  }

  /**
   * Adds a form element to determine whether HTML characters should be counted.
   *
   * @param array $form
   *   The form render array to which the element should be added.
   * @param bool $storageSettingMaxlengthField
   *   Whether or not the field has storage settings that include a maximum
   *   length. Such fields allow for using the storage settings rather than the
   *   wiget setting.
   */
  public function addCountHtmlSettingsFormElement(array &$form, $storageSettingMaxlengthField = FALSE) {
    $form['count_html_characters'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Include HTML characters in the character count'),
      '#description' => $this->t('When this box is checked, HTML characters are included in the character count. For example, when this box is checked, the string <em>&lt;p&gt;Hi&lt;/p&gt;</em> would be nine characters long. When this box is not checked, the character count would be two characters long (for hi). Note that if this textarea uses an editor like CKEditor, it is very likely that this box should be unchecked.'),
      '#default_value' => $this->getSetting('count_html_characters'),
    ];

    if ($storageSettingMaxlengthField) {
      $form['count_html_characters']['#states'] = [
        'invisible' => [
          ':input[name="fields[' . $this->fieldDefinition->getName() . '][settings_edit_form][settings][use_field_maxlength]"]' => ['checked' => FALSE],
          ':input[name="fields[' . $this->fieldDefinition->getName() . '][settings_edit_form][settings][maxlength]"]' => ['value' => 0],
        ],
      ];
    }
    else {
      $form['count_html_characters']['#states'] = [
        'invisible' => [
          ':input[name="fields[' . $this->fieldDefinition->getName() . '][settings_edit_form][settings][maxlength]"]' => ['value' => 0],
        ],
      ];
    }
  }

  /**
   * Get the default value for the textcount_status_message widget setting.
   *
   * @return string
   *   The default message for widget settings.
   */
  public static function getDefaultTextCountStatusMessage() {
    return 'Maxlength: <span class="maxlength_count">@maxlength</span><br />Used: <span class="current_count">@current_length</span><br />Remaining: <span class="remaining_count">@remaining_count</span>';
  }

  /**
   * Adds a form element to set the status message to be shown to users.
   *
   * @param array $form
   *   The form render array to which the element should be added.
   * @param bool $storageSettingMaxlengthField
   *   Whether or not the field has storage settings that include a maximum
   *   length. Such fields allow for using the storage settings rather than the
   *   wiget setting.
   */
  public function addTextCountStatusMessageSettingsFormElement(array &$form, $storageSettingMaxlengthField = FALSE) {
    $form['textcount_status_message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Message shown to users'),
      '#default_value' => $this->getSetting('textcount_status_message'),
      '#description' => $this->t('Enter the message to show to users indicating the current status of the character count. The variables <strong>@maxlength</strong>, <strong>@current_length</strong> and <strong>@remaining_count</strong> can be used in this field. For the real-time counter to work, said variables must be wrapped in HTML span tags with their classes respectively set to <strong>maxlength_count</strong>, <strong>current_count</strong> and <strong>remaining_count</strong>.'),
    ];

    if ($storageSettingMaxlengthField) {
      $form['textcount_status_message']['#states'] = [
        'invisible' => [
          ':input[name="fields[' . $this->fieldDefinition->getName() . '][settings_edit_form][settings][use_field_maxlength]"]' => ['checked' => FALSE],
          ':input[name="fields[' . $this->fieldDefinition->getName() . '][settings_edit_form][settings][maxlength]"]' => ['value' => 0],
        ],
      ];
    }
    else {
      $form['textcount_status_message']['#states'] = [
        'invisible' => [
          ':input[name="fields[' . $this->fieldDefinition->getName() . '][settings_edit_form][settings][maxlength]"]' => ['value' => 0],
        ],
      ];
    }
  }

  /**
   * Adds the summary of the maximum number of allowed characters.
   *
   * @param array $summary
   *   The array of summaries to which the summary should be added.
   */
  public function addMaxlengthSummary(array &$summary) {
    if ($this->getSetting('use_field_maxlength')) {
      $text = $this->t('Maximum number of characters: %count (field default)', ['%count' => $this->getFieldSetting('max_length')]);
    }
    else {
      $maxlength = $this->getSetting('maxlength');
      $text = $this->t('Maximum number of characters: %count', ['%count' => ($maxlength ? $maxlength : $this->t('Disabled'))]);
    }

    $summary['maxlength'] = $text;
  }

  /**
   * Adds the summary of the position of the textfield counter.
   *
   * @param array $summary
   *   The array of summaries to which the summary should be added.
   */
  public function addPositionSummary(array &$summary) {
    if ($this->getSetting('maxlength') || $this->getSetting('use_field_maxlength')) {
      $summary['counter_position'] = $this->t('Counter position: %position', ['%position' => $this->translateValue($this->getSetting('counter_position'))]);
    }
  }

  /**
   * Adds the summary of the js_prevent_submit setting.
   *
   * @param array $summary
   *   The array of summaries to which the summary should be added.
   */
  public function addJsSubmitPreventSummary(array &$summary) {
    if ($this->getSetting('maxlength') && !$this->getSetting('use_field_maxlength')) {
      $summary['js_prevent_submit'] = $this->t('Prevent form submission when user goes over character count: %prevent', ['%prevent' => ($this->getSetting('js_prevent_submit') ? $this->t('Yes') : $this->t('No'))]);
    }
  }

  /**
   * Adds the summary of the count_html_characters setting.
   *
   * @param array $summary
   *   The array of summaries to which the summary should be added.
   */
  public function addCountHtmlSummary(array &$summary) {
    if ($this->getSetting('maxlength') || $this->getSetting('use_field_maxlength')) {
      $summary['count_html_characters'] = $this->t('Include HTML characters in the character count: %count_html_characters', ['%count_html_characters' => ($this->getSetting('count_html_characters') ? $this->t('Yes') : $this->t('No'))]);
    }
  }

  /**
   * Adds the summary of the count_html_characters setting.
   *
   * @param array $summary
   *   The array of summaries to which the summary should be added.
   */
  public function addTextCountStatusMessageSummary(array &$summary) {
    if ($this->getSetting('maxlength') || $this->getSetting('use_field_maxlength')) {
      $summary['textcount_status_message'] = $this->t('Status Message: %status_message', ['%status_message' => $this->getSetting('textcount_status_message')]);
    }
  }

  /**
   * Sets up the form element with the textfield counter.
   *
   * @param array $element
   *   The render array for the element to which files are being attached.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to which the field is attached.
   * @param |Drupal\Core\Field\FieldDefinitionInterface $fieldDefinition
   *   The field definition.
   * @param int $delta
   *   The delta (index) of the current item.
   * @param bool $summary
   *   Set this to TRUE for the summary field on textfields with the summary
   *   enabled.
   */
  public function fieldFormElement(array &$element, EntityInterface $entity, FieldDefinitionInterface $fieldDefinition, $delta, $summary = FALSE) {
    if ($summary) {
      $maxlength = $this->getSetting('summary_maxlength');
    }
    else {
      $maxlength = $this->getSetting('use_field_maxlength') ? $this->getFieldSetting('max_length') : $this->getSetting('maxlength');
    }
    $position = $this->getSetting('counter_position');

    $keys = [$entity->getEntityTypeId()];
    $keys[] = $entity->id() ? $entity->id() : 0;
    if (method_exists($fieldDefinition, 'id')) {
      $field_definition_id = str_replace('.', '--', $fieldDefinition->id());
    }
    else {
      $field_definition_id = "{$entity->getEntityTypeId()}--{$entity->bundle()}--{$fieldDefinition->getName()}";
    }

    $keys[] = $field_definition_id;
    $keys[] = $delta;

    $key = implode('-', $keys);

    if ($summary) {
      $key .= '-summary';
    }

    $element['#attributes']['class'][] = $key;
    $element['#attributes']['class'][] = 'textfield-counter-element';
    $element['#attributes']['data-field-definition-id'] = $field_definition_id;

    $element['#attached']['library'][] = 'textfield_counter/counter';
    $element['#attached']['drupalSettings']['textfieldCounter'][$key]['key'][$delta] = $key;
    $element['#attached']['drupalSettings']['textfieldCounter'][$key]['maxlength'] = (int) $maxlength;
    $element['#attached']['drupalSettings']['textfieldCounter'][$key]['counterPosition'] = $position;
    $element['#attached']['drupalSettings']['textfieldCounter'][$key]['textCountStatusMessage'] = $this->getSetting('textcount_status_message');

    if ($this->getSetting('js_prevent_submit')) {
      $element['#attached']['drupalSettings']['textfieldCounter'][$key]['preventSubmit'] = TRUE;
    }

    $element['#attached']['drupalSettings']['textfieldCounter'][$key]['countHTMLCharacters'] = $this->getSetting('count_html_characters');
  }

  /**
   * Validates the field for the maximum number of characters.
   *
   * @param array $element
   *   The render array for the element to which fiels are being attached.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The Drupal form state.
   * @param int $maxlength
   *   The maximum allowed text length against which the field should be
   *   validated.
   */
  public static function validateFieldFormElement(array $element, FormStateInterface $form_state, $maxlength) {
    $input_exists = FALSE;
    $value = NestedArray::getValue($form_state->getValues(), $element['#parents'], $input_exists);
    $value = is_array($value) ? $value['value'] : $value;
    $value_length = self::getLengthOfSubmittedValue($element, $value);
    if ($value_length > $element['#textfield-maxlength']) {
      $form_state->setError($element, t(
        '@name cannot be longer than %max characters but is currently %length characters long.',
        [
          '@name' => $element['#title'],
          '%max' => $element['#textfield-maxlength'],
          '%length' => $value_length,
        ]
      ));
    }
  }

  /**
   * Get the length of the submitted text value.
   *
   * @param array $element
   *   The form element.
   * @param string $value
   *   The value whose length is to be calcluated.
   *
   * @return int
   *   The length of the value.
   */
  protected static function getLengthOfSubmittedValue(array $element, string $value): int {
    $parts = explode(PHP_EOL, $value);
    $newline_count = count($parts) - 1;
    $count_html_characters = $element['#textfield-count-html'];

    if ($count_html_characters) {
      $value_length = mb_strlen($value) - $newline_count;
    }
    else {
      $value_length = str_replace('&nbsp;', ' ', $value);
      $value_length = trim($value_length);
      $value_length = preg_replace("/(\r?\n|\r)+/", "\n", $value_length);
      $value_length = mb_strlen(strip_tags($value_length));
    }

    return $value_length;
  }

  /**
   * A unified translation function to translate values provided by this module.
   *
   * @param string $value
   *   The key of the item to be translated.
   *
   * @return string
   *   The translated value
   */
  private function translateValue($value) {
    $values = [
      'before' => $this->t('Before'),
      'after' => $this->t('After'),
    ];

    return $values[$value];
  }

}
