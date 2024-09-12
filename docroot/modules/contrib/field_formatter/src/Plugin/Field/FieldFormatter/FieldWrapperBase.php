<?php

namespace Drupal\field_formatter\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FormatterInterface;
use Drupal\Core\Field\FormatterPluginManager;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Wraps an existing field.
 */
abstract class FieldWrapperBase extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * Entity view display.
   *
   * @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface
   */
  protected $viewDisplay;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The formatter plugin manager.
   *
   * @var \Drupal\Core\Field\FormatterPluginManager
   */
  protected $formatterPluginManager;

  /**
   * The field_type plugin manager.
   *
   * @var \Drupal\Core\Field\FieldTypePluginManagerInterface
   */
  protected $fieldTypePluginManager;

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * Constructs a FieldFormatterWithInlineSettings object.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\Core\Field\FormatterPluginManager $formatter_plugin_manager
   *   The formatter plugin manager.
   * @param \Drupal\Core\Field\FieldTypePluginManagerInterface $field_type_plugin_manager
   *   The field_type plugin manager.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, FormatterPluginManager $formatter_plugin_manager, FieldTypePluginManagerInterface $field_type_plugin_manager, EntityRepositoryInterface $entity_repository) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->formatterPluginManager = $formatter_plugin_manager;
    $this->fieldTypePluginManager = $field_type_plugin_manager;
    $this->entityRepository = $entity_repository;
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
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('plugin.manager.field.formatter'),
      $container->get('plugin.manager.field.field_type'),
      $container->get('entity.repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'type' => '',
      'settings' => [],
    ];
  }

  /**
   * Get field definition for given field storage definition.
   *
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface $field_storage_definition
   *   The field storage definition.
   *
   * @return \Drupal\Core\Field\BaseFieldDefinition
   *   The field definition.
   */
  protected function getFieldDefinition(FieldStorageDefinitionInterface $field_storage_definition) {
    return BaseFieldDefinition::createFromFieldStorageDefinition($field_storage_definition);
  }

  /**
   * Get all available formatters by loading available ones and filtering out.
   *
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface $field_storage_definition
   *   The field storage definition.
   *
   * @return string[]
   *   The field formatter labels keys by plugin ID.
   */
  protected function getAvailableFormatterOptions(FieldStorageDefinitionInterface $field_storage_definition) {
    $formatters = $this->formatterPluginManager->getOptions($field_storage_definition->getType());
    $formatter_instances = array_map(
      function ($formatter_id) {
        // @todo Ensure it is right to empty all values here, see:
        // https://api.drupal.org/api/drupal/core%21lib%21Drupal%21Core%21Field%21FormatterPluginManager.php/class/FormatterPluginManager/8.2.x
        $configuration = [
          'field_definition' => $this->fieldDefinition,
          'settings' => [],
          'label' => '',
          'view_mode' => '',
          'third_party_settings' => [],
        ];
        return $this->formatterPluginManager->createInstance($formatter_id, $configuration);
      },
      array_combine(array_keys($formatters), array_keys($formatters))
    );

    $filtered_formatter_instances = array_filter($formatter_instances,
      function (FormatterInterface $formatter) {
        return $formatter->isApplicable($this->fieldDefinition);
      }
    );

    $options = array_map(
      function (FormatterInterface $formatter) {
        return $formatter->getPluginDefinition()['label'];
      }, $filtered_formatter_instances);

    // Remove field_link itself.
    unset($options['field_link']);

    return $options;
  }

  /**
   * Ajax callback for fields with AJAX callback to update form substructure.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array|null
   *   The replaced form substructure.
   */
  public static function onFormatterTypeChange(array $form, FormStateInterface $form_state) {
    $triggeringElement = $form_state->getTriggeringElement();
    $formSubstructure = NULL;
    // Dynamically return the dependent ajax for elements based on the
    // triggering element. This shouldn't be done statically because
    // settings forms may be different, e.g. for layout builder, core, ...
    if (!empty($triggeringElement['#array_parents'])) {
      $subformKeys = $triggeringElement['#array_parents'];
      // Remove the triggering element itself and add the 'settings' below key.
      array_pop($subformKeys);
      $subformKeys[] = 'settings';
      // Return the subform:
      $formSubstructure = NestedArray::getValue($form, $subformKeys);
    }
    return $formSubstructure;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);
    $field_storage = $this->fieldDefinition->getFieldStorageDefinition();
    $formatter_options = $this->getAvailableFormatterOptions($field_storage);

    if (!empty($formatter_options)) {
      $formatter_type = $this->getSettingFromFormState($form_state, 'type');
      $settings = $this->getSettingFromFormState($form_state, 'settings');
      if (!isset($formatter_options[$formatter_type])) {
        $formatter_type = $this->fieldTypePluginManager->getDefinition($field_storage->getType())['default_formatter'] ?? key($formatter_options);
        $settings = $this->formatterPluginManager->getDefaultSettings($field_storage->getType());
      }

      $form['type'] = [
        '#type' => 'select',
        '#title' => $this->t('Formatter'),
        '#options' => $formatter_options,
        '#default_value' => $formatter_type,
        // Note: We cannot use ::foo syntax, because the form is the entity form
        // display.
        '#ajax' => [
          'callback' => [get_class($this), 'onFormatterTypeChange'],
          'wrapper' => 'field-formatter-settings-ajax',
          'method' => 'replaceWith',
        ],
      ];

      $options = [
        'field_definition' => $this->getFieldDefinition($field_storage),
        'configuration' => [
          'type' => $formatter_type,
          'settings' => $settings,
          'label' => '',
          'weight' => 0,
        ],
        'view_mode' => '_custom',
      ];

      // Get the formatter settings form.
      $settings_form = ['#value' => []];
      if ($formatter = $this->formatterPluginManager->getInstance($options)) {
        $settings_form = $formatter->settingsForm([], $form_state);
      }
      $form['settings'] = $settings_form;
      $form['settings']['#prefix'] = '<div id="field-formatter-settings-ajax">' . ($form['settings']['#prefix'] ?? '');
      $form['settings']['#suffix'] = ($form['settings']['#suffix'] ?? '') . '</div>';
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    if ($type = $this->getSetting('type')) {
      $summary[] = $this->t('Formatter %type used.', ['%type' => $type]);
    }
    else {
      $summary[] = $this->t('Formatter not configured.');
    }

    return $summary;
  }

  /**
   * Returns a view display object used to render the content of the field.
   *
   * @param string $bundle_id
   *   The bundle ID.
   *
   * @return \Drupal\Core\Entity\Display\EntityViewDisplayInterface
   *   Entity view display.
   */
  protected function getViewDisplay($bundle_id) {
    if (!isset($this->viewDisplay[$bundle_id])) {

      $display = EntityViewDisplay::create([
        'targetEntityType' => $this->fieldDefinition->getTargetEntityTypeId(),
        'bundle' => $bundle_id,
        'status' => TRUE,
      ]);
      $display->setComponent($this->fieldDefinition->getName(), [
        'type' => $this->getSetting('type'),
        'settings' => $this->getSetting('settings'),
      ]);
      $this->viewDisplay[$bundle_id] = $display;
    }
    return $this->viewDisplay[$bundle_id];
  }

  /**
   * Returns the wrapped field output.
   */
  protected function getFieldOutput(FieldItemListInterface $items, $langcode) {
    /** @var \Drupal\Core\Entity\FieldableEntityInterface $entity */
    $entity = $this->entityRepository->getTranslationFromContext($items->getEntity(), $langcode);

    $build = $this->getViewDisplay($entity->bundle())->build($entity);
    return $build[$this->fieldDefinition->getName()] ?? [];
  }

  /**
   * Helper function to retrieve the $setting from the $form_state.
   *
   * @param Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   * @param string $setting
   *   The setting key to retrieve.
   */
  protected function getSettingFromFormState(FormStateInterface $form_state, $setting) {
    $field_name = $this->fieldDefinition->getName();
    if ($form_state->hasValue([
      'fields',
      $field_name,
      'settings_edit_form',
      'settings',
      $setting,
    ])) {
      return $form_state->getValue([
        'fields',
        $field_name,
        'settings_edit_form',
        'settings',
        $setting,
      ]);
    }

    return $this->getSetting($setting);
  }

}
