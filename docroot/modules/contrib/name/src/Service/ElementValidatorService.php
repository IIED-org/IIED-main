<?php

declare(strict_types=1);

namespace Drupal\name\Service;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\name\Element\Name;

/**
 * Validates name element minimum components and required state.
 *
 * @internal
 */
class ElementValidatorService implements ElementValidatorInterface {

  use StringTranslationTrait;

  public function __construct(
    private readonly ModuleHandlerInterface $moduleHandler,
    TranslationInterface $string_translation,
    private readonly NameComponentMetadataInterface $componentMetadata,
  ) {
    $this->setStringTranslation($string_translation);
  }

  /**
   * Validates a name form element.
   */
  public function validate(array $element, FormStateInterface $form_state): array {
    if (empty($element['#needs_validation'])) {
      return $element;
    }

    $minimum_components = array_filter($element['#minimum_components']);
    $labels             = $this->resolveLabels($element);
    $item               = $element['#value'];
    $empty              = Name::validateIsEmpty($item);
    $item_components    = $this->resolveFilledComponents($element, $labels);
    $item_components    = $this->applyFamilyOrGivenLogic($element, $labels, $item_components);
    $missing_labels     = $this->resolveMissingLabels($minimum_components, $item_components, $labels);
    $is_inline          = (bool) $this->moduleHandler->moduleExists('inline_form_errors');

    if (!$empty && !empty($missing_labels)) {
      $this->setPartialInputErrors($element, $form_state, $missing_labels, $is_inline);
    }

    if ($empty && $element['#required']) {
      $this->setRequiredErrors($element, $form_state, $missing_labels, $is_inline);
    }
    return $element;
  }

  /**
   * Resolves validation labels for enabled components.
   */
  private function resolveLabels(array $element): array {
    $labels = [];
    foreach ($element['#components'] as $key => $component) {
      if (!isset($component['exclude'])) {
        $labels[$key] = $component['title'];
      }
    }

    return $labels;
  }

  /**
   * Resolves components containing user-entered values.
   */
  private function resolveFilledComponents(array $element, array $labels): array {
    $item = $element['#value'];
    $item_components = [];

    foreach (array_keys($this->componentMetadata->getTranslations()) as $key) {
      if (!isset($labels[$key]) && !isset($item[$key])) {
        continue;
      }

      $value = $item[$key] ?? NULL;

      $is_select = (($element['#components'][$key]['type'] ?? NULL) === 'select');
      if ($is_select && $value === '_none') {
        $value = '';
      }

      if (!empty($value)) {
        $item_components[$key] = 1;
      }
    }

    return $item_components;
  }

  /**
   * Applies the family-or-given shortcut when it is configured.
   */
  private function applyFamilyOrGivenLogic(
    array $element,
    array $labels,
    array $item_components,
  ): array {
    $item = $element['#value'];
    if (!empty($element['#allow_family_or_given'])) {
      if (isset($labels['given']) && isset($labels['family'])) {
        if (!empty($item['given']) || !empty($item['family'])) {
          $item_components['given'] = 1;
          $item_components['family'] = 1;
        }
      }
    }

    return $item_components;
  }

  /**
   * Resolves enabled labels for missing minimum components.
   */
  private function resolveMissingLabels(
    array $minimum_components,
    array $item_components,
    array $labels,
  ): array {
    $missing_components = array_diff(array_keys($minimum_components), array_keys($item_components));
    $missing_components = array_combine($missing_components, $missing_components);

    return array_intersect_key($labels, $missing_components);
  }

  /**
   * Sets validation errors for partial name input.
   */
  private function setPartialInputErrors(
    array $element,
    FormStateInterface $form_state,
    array $missing_labels,
    bool $is_inline,
  ): void {
    if ($is_inline) {
      foreach ($missing_labels as $key => $label) {
        $form_state->setError($element[$key], $this->t('@name requires <em>@components</em>.', [
          '@name' => $element['#title'],
          '@components' => $label,
        ]));
      }
    }
    else {
      $form_state->setError($element[key($missing_labels)], $this->t('@name requires <em>@components</em>.', [
        '@name' => $element['#title'],
        '@components' => implode(', ', $missing_labels),
      ]));

      foreach ($missing_labels as $key => $label) {
        $form_state->setError($element[$key]);
      }
    }
  }

  /**
   * Sets validation errors for required name input.
   */
  private function setRequiredErrors(
    array $element,
    FormStateInterface $form_state,
    array $missing_labels,
    bool $is_inline,
  ): void {
    if ($is_inline) {
      foreach ($missing_labels as $key => $label) {
        $form_state->setError($element[$key], $this->t('@name requires <em>@components</em>.', [
          '@name' => $element['#title'],
          '@components' => $label,
        ]));
      }
    }
    else {
      $form_state->setError($element, $this->t('@name field is required.', ['@name' => $element['#title']]));
    }
  }

}
