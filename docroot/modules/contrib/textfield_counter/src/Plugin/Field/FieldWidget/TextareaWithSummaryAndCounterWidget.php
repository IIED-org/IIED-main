<?php

namespace Drupal\textfield_counter\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\text\Plugin\Field\FieldWidget\TextareaWithSummaryWidget;

/**
 * Plugin implementation of the 'text_textarea_with_summary_and_counter' widget.
 *
 * @FieldWidget(
 *   id = "text_textarea_with_summary_and_counter",
 *   label = @Translation("Textarea with a summary and counter"),
 *   field_types = {
 *     "text_with_summary"
 *   }
 * )
 */
class TextareaWithSummaryAndCounterWidget extends TextareaWithSummaryWidget {

  use TextFieldCounterWidgetTrait;

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'maxlength' => 0,
      'enable_summary' => TRUE,
      'summary_maxlength' => 0,
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

    $form['general_header'] = [
      '#prefix' => '<h3>',
      '#suffix' => '</h3>',
      '#markup' => $this->t('General Settings'),
      '#weight' => -149,
    ];

    $this->addCounterPositionSettingsFormElement($form);
    $form['counter_position']['#weight'] = -139;
    $this->addTextCountStatusMessageSettingsFormElement($form);
    $form['textcount_status_message']['#weight'] = -129;

    $form['summary_header'] = [
      '#prefix' => '<h3>',
      '#suffix' => '</h3>',
      '#markup' => $this->t('Summary'),
      '#weight' => -99,
    ];

    $form['enable_summary'] = [
      '#title' => $this->t('Enable summary field'),
      '#type' => 'checkbox',
      '#weight' => -89,
      '#default_value' => $this->getSetting('enable_summary'),
      '#attributes' => [
        'data-textfield-counter-selector' => 'enable-summary-checkbox',
      ],
    ];

    $form['show_summary']['#weight'] = -79;
    $form['show_summary']['#states']['visible']['[data-textfield-counter-selector="enable-summary-checkbox"]'] = ['checked' => TRUE];

    $form['summary_rows']['#weight'] = -69;
    $form['summary_rows']['#states']['visible']['[data-textfield-counter-selector="enable-summary-checkbox"]'] = ['checked' => TRUE];
    $this->addSummaryMaxLengthSettingsFormElement($form);

    $form['textarea_header'] = [
      '#prefix' => '<h3>',
      '#suffix' => '</h3>',
      '#markup' => $this->t('Textarea'),
      '#weight' => -49,
    ];

    $this->addMaxlengthSettingsFormElement($form);
    $this->addJsPreventSubmitSettingsFormElement($form);
    $this->addCountHtmlSettingsFormElement($form);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {

    $summary = [];

    $this->addPositionSummary($summary);
    $this->addTextCountStatusMessageSummary($summary);

    $summary = array_merge($summary, parent::settingsSummary());

    $textarea_rows = $summary[0];
    unset($summary[0]);
    $summary_rows = $summary[1];
    unset($summary[1]);
    unset($summary[2]);

    if ($this->getSetting('enable_summary')) {
      $summary['enable_summary'] = $this->t('Enable summary field: %enabled', ['%enabled' => $this->t('Yes')]);
      $summary['summary_open'] = $this->t('Summary open: %open', ['%open' => ($this->getSetting('show_summary') ? $this->t('Yes') : $this->t('No'))]);
      $summary['summary_rows'] = $summary_rows;
      $this->addSummaryMaxlengthSummary($summary);
    }
    else {
      $summary['enable_summary'] = $this->t('Enable summary field: %enabled', ['%enabled' => $this->t('No')]);
    }

    $summary['num_rows'] = $textarea_rows;
    $this->addMaxlengthSummary($summary);
    $this->addJsSubmitPreventSummary($summary);
    $this->addCountHtmlSummary($summary);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {

    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    $element['summary']['#access'] = $this->getSetting('enable_summary');

    $entity = $items->getEntity();
    $field_defintion = $items->getFieldDefinition();

    if ($maxlength = $this->getSetting('maxlength')) {
      $this->fieldFormElement($element, $entity, $field_defintion, $delta);
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

    if ($summary_maxlength = $this->getSetting('summary_maxlength')) {
      $this->fieldFormElement($element['summary'], $entity, $field_defintion, $delta, TRUE);
      $element['summary']['#textfield-maxlength'] = $summary_maxlength;
      $element['summary']['#textfield-count-html'] = $this->getSetting('count_html_characters');

      $classes = class_uses($this);
      if (count($classes)) {
        $element['summary']['#element_validate'][] = [array_pop($classes), 'validateFieldFormElement'];
      }
    }

    return $element;
  }

  /**
   * Adds a form element to set maximum number of summary characters allowed.
   *
   * @param array $form
   *   The form render array to which the element should be added.
   */
  public function addSummaryMaxlengthSettingsFormElement(array &$form) {
    $form['summary_maxlength'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum number of characters in the summary'),
      '#min' => 0,
      '#default_value' => $this->getSetting('summary_maxlength'),
      '#description' => $this->t('Setting this value to zero will disable the counter on the summary.'),
      '#weight' => -69,
      '#states' => [
        'visible' => [
          '[data-textfield-counter-selector="enable-summary-checkbox"]' => ['checked' => TRUE],
        ],
      ],
    ];
  }

  /**
   * Adds summary of the maximum number of allowed of characters in the summary.
   *
   * @param array $summary
   *   The array of summaries to which the summary should be added.
   */
  public function addSummaryMaxlengthSummary(array &$summary) {
    if ($this->getSetting('show_summary')) {
      $maxlength = $this->getSetting('summary_maxlength');
      $text = $this->t('Maximum number of characters in the summary: %count', ['%count' => ($maxlength ? $maxlength : $this->t('Disabled'))]);

      $summary['summary_maxlength'] = $text;
    }
  }

}
