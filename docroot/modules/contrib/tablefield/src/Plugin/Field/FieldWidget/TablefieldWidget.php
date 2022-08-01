<?php

namespace Drupal\tablefield\Plugin\Field\FieldWidget;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldItemList;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountProxy;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;

/**
 * Plugin implementation of the 'tablefield' widget.
 *
 * @FieldWidget (
 *   id = "tablefield",
 *   label = @Translation("Table Field"),
 *   field_types = {
 *     "tablefield"
 *   },
 * )
 */
class TablefieldWidget extends WidgetBase implements ContainerFactoryPluginInterface {

  /**
   * Drupal\Core\Session\AccountProxy definition.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * Drupal\Core\Config\ConfigFactoryInterface definition.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id,
                              $plugin_definition,
                              FieldDefinitionInterface $field_definition,
                              array $settings,
                              array $third_party_settings,
                              ConfigFactoryInterface $configFactory,
                              AccountProxy $current_user) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->configFactory = $configFactory;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('config.factory'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'input_type' => 'textfield',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element['input_type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Input type'),
      '#default_value' => $this->getSetting('input_type'),
      '#required' => TRUE,
      '#options' => [
        'textfield' => 'textfield',
        'textarea' => 'textarea',
      ],
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = $this->t('Using input type: @input_type', ['@input_type' => $this->getSetting('input_type')]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $is_field_settings_default_widget_form = $form_state->getBuildInfo()['form_id'] == 'field_config_edit_form' ? 1 : 0;

    $field = $items[0]->getFieldDefinition();
    $field_settings = $field->getSettings();
    $field_widget_default = $field->getDefaultValueLiteral();

    if (!empty($field_widget_default[$delta])) {
      $field_default = (object) $field_widget_default[$delta];
    }

    if (isset($items[$delta]->value)) {
      $default_value = $items[$delta];
    }
    elseif (!$is_field_settings_default_widget_form && !empty($field_default)) {
      // Load field settings defaults in case current item is empty.
      $default_value = $field_default;
    }
    else {
      $default_value = (object) ['value' => [], 'rebuild' => []];
    }

    // Make sure rows and cols are set.
    $rows = isset($default_value->rebuild['rows']) ?
      $default_value->rebuild['rows'] : $this->configFactory->get('tablefield.settings')->get('rows');

    $cols = isset($default_value->rebuild['cols']) ?
      $default_value->rebuild['cols'] : $this->configFactory->get('tablefield.settings')->get('cols');

    $element['caption'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Table Caption'),
      '#default_value' => (!empty($default_value->caption) ? $default_value->caption : NULL),
      '#size' => 60,
      '#description' => $this->t('This brief caption will be associated with the table and will help screen reader better describe the content within.'),
    ];

    $element = [
      '#type' => 'tablefield',
      '#input_type' => $this->getSetting('input_type'),
      '#description_display' => 'before',
      '#description' => $element['#description'] ?: $this->t('The first row will appear as the table header. Leave the first row blank if you do not need a header.'),
      '#cols' => $cols,
      '#rows' => $rows,
      '#default_value' => $default_value->value,
      '#lock' => !$is_field_settings_default_widget_form && $field_settings['lock_values'],
      '#locked_cells' => !empty($field_default->value) ? $field_default->value : [],
      '#rebuild' => $this->currentUser->hasPermission('rebuild tablefield'),
      '#import' => $this->currentUser->hasPermission('import tablefield'),
    // Add permission.
      '#addrow' => $this->currentUser->hasPermission('addrow tablefield'),
    ] + $element;

    if ($is_field_settings_default_widget_form) {
      $element['#description'] = $this->t('This form defines the table field defaults, but the number of rows/columns and content can be overridden on a per-node basis. The first row will appear as the table header. Leave the first row blank if you do not need a header.');
    }

    if ($form_state->getTriggeringElement()) {
      $element['#element_validate'][] = [$this, 'validateTablefield'];
    }

    // Allow the user to select input filters.
    if (!empty($field_settings['cell_processing'])) {
      $element['#base_type'] = $element['#type'];
      $element['#type'] = 'text_format';
      $element['#format'] = isset($default_value->format) ? $default_value->format : NULL;
      $element['#editor'] = FALSE;
    }

    return $element;
  }

  /**
   * Validation handler.
   */
  public function validateTablefield(array &$element, FormStateInterface &$form_state, array $form) {
    if ($element['#required'] && $form_state->getTriggeringElement()['#type'] == 'submit') {
      $items = new FieldItemList($this->fieldDefinition);
      $this->extractFormValues($items, $form, $form_state);
      $values = FALSE;
      if (isset($element['#value'])) {
        foreach ($element['#value']['tablefield']['table'] as $row) {
          foreach ($row as $cell) {
            if (empty($cell)) {
              $values = TRUE;
              break;
            }
          }
        };
      }
      if (!$items->count() && $values == TRUE) {
        $form_state->setError($element, $this->t('@name field is required.', ['@name' => $this->fieldDefinition->getLabel()]));
      }
    }
  }

  /**
   * {@inheritdoc}
   *
   * Set error only on the first item in a multi-valued field.
   */
  public function errorElement(array $element, ConstraintViolationInterface $violation, array $form, FormStateInterface $form_state) {
    return $element[0];
  }

}
