<?php

namespace Drupal\name\Traits;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\name\Service\NameOptionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Name settings trait.
 *
 * Shared methods to assist handling the field element setting forms.
 */
trait NameFormSettingsHelperTrait {

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return ['fieldSettingsFormPreRender'];
  }

  /**
   * Themes up the field settings into a table.
   */
  public function fieldSettingsFormPreRender($form) {
    [$components, $excluded_components] = $this->resolveComponents($form);
    $form = $this->initFormScaffold($form, $components, $excluded_components);
    $grouped_elements = [];
    $help_footer_notes = [];
    $footer_notes_counter = 0;
    $visible_component_count = count($components) - count($excluded_components);

    foreach (Element::children($form) as $child) {
      if (in_array($child, ['name_settings', 'top', 'hidden'], TRUE)) {
        continue;
      }

      if (!empty($form[$child]['#table_group'])) {
        if ($this->routeTableGroupChild($child, $form, $grouped_elements)) {
          continue;
        }
      }
      elseif (!empty($form[$child]['#indent_row'])) {
        $this->buildIndentRow(
          $child,
          $form,
          $grouped_elements,
          $visible_component_count,
        );
      }
      else {
        $this->buildStandardRow(
          $child,
          $form,
          $components,
          $excluded_components,
          $grouped_elements,
          $help_footer_notes,
          $footer_notes_counter,
          $visible_component_count,
        );
      }
    }

    $this->flushOrphanedGroupedElements(
      $form,
      $grouped_elements,
      $visible_component_count,
    );
    $this->appendFootnotes($form, $help_footer_notes);
    $form['#sorted'] = FALSE;

    return $form;
  }

  /**
   * Resolves translatable components and excluded components.
   *
   * @param array $form
   *   The current form.
   *
   * @return array{0: array, 1: array}
   *   The components and excluded component list.
   */
  private function resolveComponents(array $form): array {
    $metadata = \Drupal::getContainer()
      ->get('name.component_metadata', ContainerInterface::NULL_ON_INVALID_REFERENCE);
    $components = $metadata ? $metadata->getTranslations() : [];

    $excluded_components = !empty($form['#excluded_components'])
      ? $form['#excluded_components']
      : [];

    return [$components, $excluded_components];
  }

  /**
   * Initializes the settings table wrapper and component headers.
   *
   * @param array $form
   *   The current form.
   * @param array $components
   *   Available components.
   * @param array $excluded_components
   *   Excluded components.
   *
   * @return array
   *   The initialized form.
   */
  private function initFormScaffold(
    array $form,
    array $components,
    array $excluded_components,
  ): array {
    $form = [
      'top' => [],
      'hidden' => ['#access' => FALSE],
      'name_settings' => [
        '#type' => 'container',
        'table' => [
          '#type' => 'table',
          '#header' => [
            [
              'data' => $this->t('Field'),
            ],
          ],
          '#weight' => -2,
        ],
      ] + ($form['name_settings'] ?? []),
    ] + $form;

    foreach ($components as $key => $title) {
      if (empty($excluded_components[$key])) {
        $form['name_settings']['table']['#header'][] = [
          'data' => $title,
        ];
      }
    }

    return $form;
  }

  /**
   * Routes children that declare #table_group.
   *
   * @param string $child
   *   The child key being processed.
   * @param array $form
   *   The current form.
   * @param array $grouped_elements
   *   Deferred grouped elements.
   *
   * @return bool
   *   TRUE if the child processing is complete.
   */
  private function routeTableGroupChild(
    string $child,
    array &$form,
    array &$grouped_elements,
  ): bool {
    $table_group = (string) $form[$child]['#table_group'];
    if ($table_group === 'none') {
      return TRUE;
    }

    if ($table_group === 'above') {
      $form['top'][$child] = $form[$child];
      unset($form[$child]);
      return TRUE;
    }

    if (isset($form['name_settings']['table'][$table_group]['elements'])) {
      $form['name_settings']['table'][$table_group]['elements'][$child] = $form[$child];
    }
    else {
      $grouped_elements[$table_group][$child] = $form[$child];
    }
    unset($form[$child]);

    return TRUE;
  }

  /**
   * Builds a single indented row.
   *
   * @param string $child
   *   The child key being processed.
   * @param array $form
   *   The current form.
   * @param array $grouped_elements
   *   Deferred grouped elements.
   * @param int $visible_component_count
   *   Number of visible components.
   */
  private function buildIndentRow(
    string $child,
    array &$form,
    array &$grouped_elements,
    int $visible_component_count,
  ): void {
    $elements_data = $this->buildColspanContainer($visible_component_count)
      + $form[$child];
    foreach ($grouped_elements[$child] ?? [] as $grouped_key => $grouped_element) {
      $elements_data[$grouped_key] = $grouped_element;
    }

    $form['name_settings']['table'][$child] = [
      'field' => [
        '#markup' => '&nbsp;',
      ],
      'elements' => $elements_data,
    ];
    unset($form[$child]);
  }

  /**
   * Builds a standard row with label and component columns.
   *
   * @param string $child
   *   The child key being processed.
   * @param array $form
   *   The current form.
   * @param array $components
   *   Available components.
   * @param array $excluded_components
   *   Excluded components.
   * @param array $grouped_elements
   *   Deferred grouped elements.
   * @param array $help_footer_notes
   *   Collected footer notes.
   * @param int $footer_notes_counter
   *   Footnote counter.
   * @param int $visible_component_count
   *   Number of visible components.
   */
  private function buildStandardRow(
    string $child,
    array &$form,
    array $components,
    array $excluded_components,
    array &$grouped_elements,
    array &$help_footer_notes,
    int &$footer_notes_counter,
    int $visible_component_count,
  ): void {
    $child_element = $form[$child];
    $row = [];

    if (isset($child_element['#title'])) {
      $row['field'] = $this->buildLabelCell(
        $child_element,
        $help_footer_notes,
        $footer_notes_counter,
      );
    }

    $row += $this->buildComponentColumns(
      $child,
      $child_element,
      $components,
      $excluded_components,
      $form['hidden'],
    );

    if (!empty($grouped_elements[$child])) {
      if (!isset($row['elements'])) {
        $row['elements'] = $this->buildColspanContainer($visible_component_count);
      }
      foreach ($grouped_elements[$child] as $grouped_key => $grouped_element) {
        $row['elements'][$grouped_key] = $grouped_element;
      }
    }

    $form['name_settings']['table'][$child] = $row;
    unset($form[$child]);
  }

  /**
   * Builds the label cell, including a footnote marker if needed.
   *
   * @param array $child_element
   *   The child form element.
   * @param array $help_footer_notes
   *   Collected footer notes.
   * @param int $footer_notes_counter
   *   Footnote counter.
   *
   * @return array
   *   The label cell.
   */
  private function buildLabelCell(
    array &$child_element,
    array &$help_footer_notes,
    int &$footer_notes_counter,
  ): array {
    $label_cell = [
      '#type' => 'container',
      'title' => [
        '#plain_text' => (string) $child_element['#title'],
      ],
    ];

    if (!empty($child_element['#description'])) {
      $footnote_sup = $this->t(
        '<sup>@number</sup>',
        ['@number' => ++$footer_notes_counter],
      );
      $label_cell['footnote'] = [
        '#markup' => $footnote_sup,
      ];
      $help_footer_notes[] = $child_element['#description'];
      unset($child_element['#description']);
    }

    return $label_cell;
  }

  /**
   * Builds all component columns for a standard row.
   *
   * @param string $child
   *   The child key being processed.
   * @param array $child_element
   *   The child form element.
   * @param array $components
   *   Available components.
   * @param array $excluded_components
   *   Excluded components.
   * @param array $hidden_elements
   *   Hidden elements keyed by child.
   *
   * @return array
   *   Component columns keyed by component name.
   */
  private function buildComponentColumns(
    string $child,
    array &$child_element,
    array $components,
    array $excluded_components,
    array &$hidden_elements,
  ): array {
    $row = [];
    foreach (array_keys($components) as $weight => $key) {
      if (!empty($excluded_components[$key]) && isset($child_element[$key])) {
        $child_element[$key]['#access'] = FALSE;
        $hidden_elements[$child][$key] = $child_element[$key];
        continue;
      }

      if (!isset($child_element[$key])) {
        $row[$key] = $this->buildEmptyCell($weight);
        continue;
      }

      $child_element[$key]['#attributes']['title'] = $child_element[$key]['#title'];
      if (($child_element[$key]['#type'] ?? NULL) === 'checkbox') {
        $child_element[$key]['#title_display'] = 'invisible';
      }
      $row[$key] = [
        '#weight' => $weight,
      ] + $child_element[$key];

      // Show columns when component is checked or label is blank.
      if ($child !== 'components') {
        $row[$key]['#states'] = $this->buildComponentVisibilityStates($key);
      }
    }

    return $row;
  }

  /**
   * Builds visibility states for non-component rows.
   *
   * @param string $key
   *   Component key.
   *
   * @return array
   *   Render API states definition.
   */
  private function buildComponentVisibilityStates(string $key): array {
    return [
      'visible' => [
        [
          ':input[name$="[components][' . $key . ']"]' => [
            'checked' => TRUE,
          ],
        ],
        'or',
        [
          ':input[name$="[labels][' . $key . ']"]' => [
            'empty' => TRUE,
          ],
        ],
      ],
    ];
  }

  /**
   * Adds grouped elements that were deferred until after row creation.
   *
   * @param array $form
   *   The current form.
   * @param array $grouped_elements
   *   Deferred grouped elements.
   * @param int $visible_component_count
   *   Number of visible components.
   */
  private function flushOrphanedGroupedElements(
    array &$form,
    array $grouped_elements,
    int $visible_component_count,
  ): void {
    foreach ($grouped_elements as $target_key => $elements) {
      if (isset($form['name_settings']['table'][$target_key]['elements'])) {
        foreach ($elements as $grouped_key => $grouped_element) {
          $form['name_settings']['table'][$target_key]['elements'][$grouped_key] = $grouped_element;
        }
      }
      elseif (!isset($form['name_settings']['table'][$target_key])) {
        $elements_data = $this->buildColspanContainer($visible_component_count);
        foreach ($elements as $grouped_key => $grouped_element) {
          $elements_data[$grouped_key] = $grouped_element;
        }
        $form['name_settings']['table'][$target_key] = [
          'field' => [
            '#markup' => '&nbsp;',
          ],
          'elements' => $elements_data,
        ];
      }
    }
  }

  /**
   * Appends footnotes if descriptions were collected.
   *
   * @param array $form
   *   The current form.
   * @param array $help_footer_notes
   *   Collected footer notes.
   */
  private function appendFootnotes(array &$form, array $help_footer_notes): void {
    if (empty($help_footer_notes)) {
      return;
    }

    $form['name_settings']['footnotes'] = [
      '#type' => 'details',
      '#title' => t('Footnotes'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
      '#parents' => [],
      '#weight' => -1,
      'help_items' => [
        '#theme' => 'item_list',
        '#list_type' => 'ol',
        '#items' => $help_footer_notes,
      ],
    ];
  }

  /**
   * Builds a colspan-aware container wrapper.
   *
   * @param int $visible_component_count
   *   Number of visible components.
   *
   * @return array
   *   The container render array.
   */
  private function buildColspanContainer(int $visible_component_count): array {
    return [
      '#type' => 'container',
      '#wrapper_attributes' => [
        'colspan' => $visible_component_count,
      ],
    ];
  }

  /**
   * Builds an empty component placeholder cell.
   *
   * @param int $weight
   *   Display weight.
   *
   * @return array
   *   The placeholder cell.
   */
  private function buildEmptyCell(int $weight): array {
    return [
      '#markup' => '&nbsp;',
      '#weight' => $weight,
    ];
  }

  /**
   * Helper function to validate minimum components.
   *
   * @param array $element
   *   Element being validated.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param mixed $values
   *   Values to check.
   * @param int $max_length
   *   The max length.
   */
  protected static function validateOptions($element, FormStateInterface $form_state, $values, $max_length) {
    $label = $element['#title'];

    $long_options = [];
    $valid_options = [];
    $default_options = [];
    foreach ($values as $value) {
      $value = trim($value);
      // Blank option - anything goes!
      if (strpos($value, '--') === 0) {
        $default_options[] = $value;
      }
      // Simple checks on the taxonomy includes.
      elseif (preg_match(NameOptionInterface::VOCABULARY_REGEX, $value, $matches)) {
        if (!\Drupal::moduleHandler()->moduleExists('taxonomy')) {
          $form_state->setError($element, t("The taxonomy module must be enabled before using the '%tag' tag in %label.", [
            '%tag' => $matches[0],
            '%label' => $label,
          ]));
        }
        elseif ($value !== $matches[0]) {
          $form_state->setError($element, t("The '%tag' tag in %label should be on a line by itself.", [
            '%tag' => $matches[0],
            '%label' => $label,
          ]));
        }
        else {
          $vocabulary = \Drupal::entityTypeManager()->getStorage('taxonomy_vocabulary')->load($matches[1]);
          if ($vocabulary) {
            $valid_options[] = $value;
          }
          else {
            $form_state->setError($element, t("The vocabulary '%tag' in %label could not be found.", [
              '%tag' => $matches[1],
              '%label' => $label,
            ]));
          }
        }
      }
      elseif (mb_strlen($value) > $max_length) {
        $long_options[] = $value;
      }
      elseif (!empty($value)) {
        $valid_options[] = $value;
      }
    }
    if (count($long_options)) {
      $form_state->setError($element, t('The following options exceed the maximum allowed %label length: %options', [
        '%options' => implode(', ', $long_options),
        '%label' => $label,
      ]));
    }
    elseif (empty($valid_options)) {
      $form_state->setError($element, t('%label are required.', [
        '%label' => $label,
      ]));
    }
    elseif (count($default_options) > 1) {
      $form_state->setError($element, t('%label can only have one blank value assigned to it.', [
        '%label' => $label,
      ]));
    }

    $form_state->setValueForElement($element, array_merge($default_options, $valid_options));
  }

  /**
   * Helper function to get the allowed values.
   *
   * @param string $string
   *   The string to parse.
   *
   * @return array
   *   The parsed values.
   */
  protected static function extractAllowedValues($string) {
    return array_filter(array_map('trim', explode("\n", $string)));
  }

}
