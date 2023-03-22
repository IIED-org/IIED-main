<?php

namespace Drupal\tablefield\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\MapDataDefinition;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Routing\RouteObjectInterface;

/**
 * Plugin implementation of the 'tablefield' field type.
 *
 * @FieldType (
 *   id = "tablefield",
 *   label = @Translation("Table Field"),
 *   description = @Translation("Stores a table of text fields"),
 *   default_widget = "tablefield",
 *   default_formatter = "tablefield"
 * )
 */
class TablefieldItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'value' => [
          'type' => 'blob',
          'size' => 'big',
          'serialize' => TRUE,
        ],
        'format' => [
          'type' => 'varchar',
          'length' => 255,
          'default value' => '',
        ],
        'caption' => [
          'type' => 'varchar',
          'length' => 255,
          'default value' => '',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return [
      'export' => 0,
      'restrict_rebuild' => 1,
      'restrict_import' => 1,
      'lock_values' => 0,
      'cell_processing' => 0,
      'empty_rules' => [
        'ignore_table_structure' => 0,
        'ignore_table_header' => 0,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $form = [];
    $settings = $this->getSettings();

    $form['default_message'] = [
      '#type' => 'markup',
      '#markup' => $this->t('To specify a default table, use the &quot;Default Value&quot; above. There you can specify a default number of rows/columns and values.'),
    ];
    $form['export'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow users to export table data as CSV'),
      '#default_value' => $settings['export'],
    ];
    $form['restrict_rebuild'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Restrict rebuilding to users with the permission "rebuild tablefield"'),
      '#default_value' => $settings['restrict_rebuild'],
    ];
    $form['restrict_import'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Restrict importing to users with the permission "import tablefield"'),
      '#default_value' => $settings['restrict_import'],
    ];
    $form['lock_values'] = [
      '#type' => 'checkbox',
      '#title' => 'Lock cell default values from further edits during node add/edit. Most commonly used to have fixed values for the header.',
      '#default_value' => $settings['lock_values'],
    ];
    $form['cell_processing'] = [
      '#type' => 'radios',
      '#title' => $this->t('Table cell processing'),
      '#default_value' => $settings['cell_processing'],
      '#options' => [
        $this->t('Plain text'),
        $this->t('Filtered text (user selects input format)'),
      ],
    ];
    $form['empty_rules'] = [
      '#type' => 'details',
      '#title' => $this->t('Rules for evaluating whether tablefield item should be considered empty'),
      '#open' => FALSE,
    ];
    $form['empty_rules']['ignore_table_structure'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Ignore table structure changes'),
      '#description' => $this->t('If checked, table structure, i.e. number of rows and cols will not be considered when evaluating whether tablefield item is empty or not. If unchecked, a table structure which is different from the one set in defaults will result in the tablefield item being considered not empty.'),
      '#default_value' => $settings['empty_rules']['ignore_table_structure'],
    ];
    $form['empty_rules']['ignore_table_header'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Ignore table header'),
      '#description' => $this->t('If checked, tablefield item will be considered empty even if it does have a table header, i.e. even if first row of the table contains non-empty cells.'),
      '#default_value' => $settings['empty_rules']['ignore_table_header'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['table_value'] = DataDefinition::create('string')
      ->setLabel(t('Stringified table value'))
      ->setDescription(t('The stringified value of the table.'))
      ->setComputed(TRUE)
      ->setClass('\Drupal\tablefield\TableValue');

    $properties['value'] = MapDataDefinition::create()
      ->setLabel(t('Table data'))
      ->setDescription(t('Stores tabular data.'));

    $properties['format'] = DataDefinition::create('filter_format')
      ->setLabel(t('Text format'));

    $properties['caption'] = DataDefinition::create('string')
      ->setLabel(t('Table Caption'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($values, $notify = TRUE) {
    if (!isset($values)) {
      return;
    }
    // We want to keep the table right under the 'value' key.
    elseif (!empty($values['tablefield'])) {
      $values['rebuild'] = $values['tablefield']['rebuild'];
      $values['value'] = $values['tablefield']['table'];
      unset($values['tablefield']);
      unset($values['rebuild']['rebuild']);
    }
    // In case cell_processing is enabled
    // text_format puts values under an extra 'value' key.
    elseif (!empty($values['value']['tablefield'])) {
      $values['rebuild'] = $values['value']['tablefield']['rebuild'];
      $values['value'] = $values['value']['tablefield']['table'];
      unset($values['rebuild']['rebuild']);
    }
    // In case this is being loaded from storage recalculate rows/cols.
    elseif (empty($values['rebuild'])) {
      if (array_key_exists('value', $values) && array_key_exists('caption', $values['value'])) {
        unset($values['value']['caption']);
      }
      $values['rebuild']['rows'] = isset($values['value']) ? count($values['value']) : 0;
      $values['rebuild']['cols'] = isset($values['value'][0]) ? count($values['value'][0]) : 0;
      // If the weight column was saved, don't include it in the count.
      if (isset($values['value'][0]['weight'])) {
        --$values['rebuild']['cols'];
      }
    }

    if (isset($values['caption'])) {
      $values['value']['caption'] = $values['caption'];
    }

    // If "Lock defaults" is enabled the table needs sorting.
    $lock = $this->getFieldDefinition()->getSetting('lock_values');
    if ($lock) {
      if (!empty($values['value']) && is_array($values['value'])) {
        // Sort columns on key.
        foreach ($values['value'] as $key => $value) {
          if (is_array($value)) {
            ksort($value);
            $values['value'][$key] = $value;
          }
        }
        // Sort rows on key.
        ksort($values['value']);
      }
    }

    parent::setValue($values, $notify);
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    // @todo Should field definition be counted?
    return [
      'value' => [['Header 1', 'Header 2'], ['Data 1', 'Data 2']],
      'rebuild' => ['rows' => 2, 'cols' => 2],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->getValue();

    $empty_rules = $this->getFieldDefinition()->getSetting('empty_rules');
    $in_settings = \Drupal::request()->get(RouteObjectInterface::ROUTE_NAME) == 'entity.field_config.node_field_edit_form';

    // Check table data first.
    if (!empty($value) && isset($value['value']) && is_array($value['value'])) {

      // Check table caption first.
      if (!empty($value['caption'])) {
        return FALSE;
      }

      // Ignore table header?
      if (!$in_settings && $empty_rules['ignore_table_header']) {
        array_shift($value['value']);
      }

      foreach ($value['value'] as $row) {
        if (is_array($row)) {
          foreach ($row as $cell) {
            if (!empty($cell)) {
              return FALSE;
            }
          }
        }
      }
    }

    // If table structure is not ignored see if it differs from defaults
    // check the route to see if you are in the field settings form
    // if yes, defaults are the tablefield config defaults
    // otherwise first consider field settings defaults.
    if (empty($empty_rules['ignore_table_structure'])) {
      $default_value = $this->getFieldDefinition()->getDefaultValueLiteral();

      if (!$in_settings && !empty($default_value[$this->name])) {
        $default_structure = $default_value[$this->name]['rebuild'];
      }
      else {
        $default_structure = [
          'rows' => \Drupal::config('tablefield.settings')->get('rows'),
          'cols' => \Drupal::config('tablefield.settings')->get('cols'),
        ];
      }

      if (!empty($value['rebuild']) && $value['rebuild'] != $default_structure) {
        return FALSE;
      }
    }

    return TRUE;
  }

}
