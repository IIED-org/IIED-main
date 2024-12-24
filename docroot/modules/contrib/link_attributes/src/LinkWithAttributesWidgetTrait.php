<?php

namespace Drupal\link_attributes;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\link_attributes\Plugin\Field\FieldWidget\LinkWithAttributesWidget;

/**
 * Provides a trait for link widgets with attributes.
 */
trait LinkWithAttributesWidgetTrait {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'placeholder_url' => '',
      'placeholder_title' => '',
      'enabled_attributes' => [
        'id' => FALSE,
        'name' => FALSE,
        'target' => TRUE,
        'rel' => TRUE,
        'class' => TRUE,
        'accesskey' => FALSE,
      ],
      'widget_default_open' => LinkWithAttributesWidget::WIDGET_OPEN_EXPAND_IF_VALUES_SET,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    $item = $items[$delta];

    $options = $item->get('options')->getValue();
    $attributes = $options['attributes'] ?? [];

    // Condition to check if there are any enabled attributes, if not, an
    // empty element is returned:
    if (empty(array_filter($this->getSetting('enabled_attributes')))) {
      return $element;
    }

    $widgetDefaultOpenSetting = $this->getSetting('widget_default_open');
    $open = NULL;

    match ($widgetDefaultOpenSetting) {
      LinkWithAttributesWidget::WIDGET_OPEN_EXPAND_IF_VALUES_SET => $open = count($attributes),
      LinkWithAttributesWidget::WIDGET_OPEN_EXPANDED => $open = TRUE,
      LinkWithAttributesWidget::WIDGET_OPEN_COLLAPSED => $open = FALSE,
      default => $open = count($attributes),
    };

    $element['options']['attributes'] = [
      '#type' => 'details',
      '#title' => $this->t('Attributes'),
      '#tree' => TRUE,
      '#open' => $open,
    ];
    $required = FALSE;
    $plugin_definitions = $this->linkAttributesManager->getDefinitions();
    foreach (array_keys(array_filter($this->getSetting('enabled_attributes'))) as $attribute) {
      if (isset($plugin_definitions[$attribute])) {
        foreach ($plugin_definitions[$attribute] as $property => $value) {
          if ($property === 'id') {
            // Don't set ID.
            continue;
          }
          $element['options']['attributes'][$attribute]['#' . $property] = $value;
        }

        // Set the default value, in case of a class that is stored as array,
        // convert it back to a string.
        $default_value = $attributes[$attribute] ?? NULL;
        if ($attribute === 'class' && is_array($default_value)) {
          $default_value = implode(' ', $default_value);
        }
        if (isset($default_value)) {
          $element['options']['attributes'][$attribute]['#default_value'] = $default_value;
        }
        $required = $required || !empty($element['options']['attributes'][$attribute]['#required']);
      }
    }
    // Open the widget by default if there is a required attribute.
    $element['options']['attributes']['#open'] = $element['options']['attributes']['#open'] || $required;

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);
    $options = array_map(function ($plugin_definition) {
      return $plugin_definition['title'];
    }, $this->linkAttributesManager->getDefinitions());
    $selected = array_keys(array_filter($this->getSetting('enabled_attributes')));
    $element['enabled_attributes'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Enabled attributes'),
      '#options' => $options,
      '#default_value' => array_combine($selected, $selected),
      '#description' => $this->t('Select the attributes to allow the user to edit.'),
    ];
    $element['widget_default_open'] = [
      '#type' => 'select',
      '#title' => $this->t('Widget default open behavior'),
      '#options' => [
        LinkWithAttributesWidget::WIDGET_OPEN_EXPAND_IF_VALUES_SET => $this->t('Expand if values set (Default)'),
        LinkWithAttributesWidget::WIDGET_OPEN_EXPANDED => $this->t('Expand'),
        LinkWithAttributesWidget::WIDGET_OPEN_COLLAPSED => $this->t('Collapse'),
      ],
      '#default_value' => $this->getSetting('default_open') ?? LinkWithAttributesWidget::WIDGET_OPEN_EXPAND_IF_VALUES_SET,
      '#description' => $this->t('Set the widget default open behavior.'),
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    // Convert a class string to an array so that it can be merged reliable.
    foreach ($values as $delta => $value) {
      if (isset($value['options']['attributes']['class']) && is_string($value['options']['attributes']['class'])) {
        $values[$delta]['options']['attributes']['class'] = explode(' ', $value['options']['attributes']['class']);
      }
    }

    return array_map(function (array $value) {
      if (isset($value['options']['attributes'])) {
        $value['options']['attributes'] = array_filter($value['options']['attributes'], function ($attribute) {
          return $attribute !== "";
        });
      }
      return $value;
    }, $values);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    $enabled_attributes = array_filter($this->getSetting('enabled_attributes'));
    if ($enabled_attributes) {
      $summary[] = $this->t('With attributes: @attributes', ['@attributes' => implode(', ', array_keys($enabled_attributes))]);
    }
    $widgetDefaultOpenSetting = $this->getSetting('widget_default_open');

    match ($widgetDefaultOpenSetting) {
      LinkWithAttributesWidget::WIDGET_OPEN_EXPAND_IF_VALUES_SET => $summary[] = $this->t('Widget open if values set'),
      LinkWithAttributesWidget::WIDGET_OPEN_EXPANDED => $summary[] = $this->t('Widget open by default.'),
      LinkWithAttributesWidget::WIDGET_OPEN_COLLAPSED => $summary[] = $this->t('Widget closed by default.'),
    };
    return $summary;
  }

}
