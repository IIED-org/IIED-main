<?php

namespace Drupal\better_exposed_filters\Plugin\better_exposed_filters\filter;

use Drupal\better_exposed_filters\Attribute\FiltersWidget;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Date picker widget implementation.
 */
#[FiltersWidget(
  id: 'bef_datepicker',
  title: new TranslatableMarkup('Date Picker'),
)]
class DatePickers extends FilterWidgetBase {

  use LoggerChannelTrait;

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(mixed $filter = NULL, array $filter_options = []): bool {
    /** @var \Drupal\views\Plugin\views\filter\FilterPluginBase $filter */
    $is_applicable = FALSE;

    if ((is_a($filter, 'Drupal\views\Plugin\views\filter\Date') || !empty($filter->date_handler)) && !$filter->isAGroup()) {
      $is_applicable = TRUE;
    }

    return $is_applicable;
  }

  /**
   * In case of date offsets as a default value, convert to dates.
   *
   * @param array $element
   *   The form element to process.
   * @param bool $is_double_date
   *   Indicates this is a double input date.
   * @param string $field_id
   *   The element field id.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  protected function convertOffsets(array &$element, bool $is_double_date, string $field_id, FormStateInterface $form_state): void {
    $options = $this->handler->options;

    if ($options['value']['type'] !== 'offset') {
      return;
    }

    $userInput = $form_state->getUserInput();

    // Check if a Y-m-d date was submitted (not an offset string).
    // If so, we need to change the filter type from 'offset' to 'date'
    // so Views processes the value correctly.
    $hasDateInput = FALSE;
    if ($is_double_date) {
      if ((isset($userInput[$field_id]['min']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $userInput[$field_id]['min']))
        || (isset($userInput[$field_id]['max']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $userInput[$field_id]['max']))) {
        $hasDateInput = TRUE;
      }
    }
    else {
      if (isset($userInput[$field_id]) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $userInput[$field_id])) {
        $hasDateInput = TRUE;
      }
    }

    // Change the filter type to 'date' when a Y-m-d date is submitted.
    // This ensures Views processes the value as an absolute date, not as an
    // offset from current time.
    if ($hasDateInput) {
      $this->handler->options['value']['type'] = 'date';
      $this->handler->value['type'] = 'date';
    }

    try {
      if ($is_double_date) {
        foreach (['min', 'max'] as $key) {
          // Convert offset initial values to dates for display.
          if (isset($userInput[$field_id][$key]) && $userInput[$field_id][$key] !== '') {
            $date = new \DateTime($userInput[$field_id][$key]);
          }
          else {
            $date = new \DateTime($element[$key]['#default_value']);
          }

          // Set the default_value attribute for JavaScript to use for display.
          $element[$key]['#attributes']['default_value'] = $date->format('Y-m-d');
        }
      }
      else {
        // Convert offset initial values to dates for display.
        if (isset($userInput[$field_id]) && $userInput[$field_id] !== '') {
          $date = new \DateTime($userInput[$field_id]);
        }
        else {
          $date = new \DateTime($element['#default_value']);
        }

        // Set the default_value attribute for JavaScript to use for display.
        $element['#attributes']['default_value'] = $date->format('Y-m-d');
      }
    }
    catch (\Exception $e) {
      $this->getLogger('better_exposed_filters')->log(RfcLogLevel::ERROR, $e->getMessage());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function exposedFormAlter(array &$form, FormStateInterface $form_state): void {
    $field_id = $this->getExposedFilterFieldId();

    // Handle wrapper element added to exposed filters
    // in https://www.drupal.org/project/drupal/issues/2625136.
    $wrapper_id = $field_id . '_wrapper';
    if (!isset($form[$field_id]) && isset($form[$wrapper_id])) {
      $element = &$form[$wrapper_id][$field_id];
    }
    else {
      $element = &$form[$field_id];
    }

    parent::exposedFormAlter($form, $form_state);

    // Single Date API-based input element.
    $is_single_date = isset($element['#type']);

    // Double Date-API-based input elements such as "in-between".
    $is_double_date = isset($element['min']['#type']) && isset($element['max']['#type']);

    if ($is_single_date) {
      $element['#type'] = 'date';
      $element['#attributes']['class'][] = 'bef-datepicker';
      $element['#attributes']['autocomplete'] = 'off';
    }
    elseif ($is_double_date) {
      // Both min and max share the same format.
      $element['min']['#type'] = 'date';
      $element['max']['#type'] = 'date';
      $element['min']['#attributes']['class'][] = 'bef-datepicker';
      $element['max']['#attributes']['class'][] = 'bef-datepicker';
      $element['min']['#attributes']['autocomplete'] = 'off';
      $element['max']['#attributes']['autocomplete'] = 'off';
    }

    $this->convertOffsets($element, $is_double_date, $field_id, $form_state);
    $form['#attached']['library'][] = 'better_exposed_filters/datepickers';
  }

}
