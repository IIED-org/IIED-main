<?php

namespace Drupal\name\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\Attribute\FieldWidget;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\name\Service\NameComponentMetadataInterface;
use Drupal\name\Service\NameOptionInterface;
use Drupal\name\Traits\NameFormDisplaySettingsTrait;
use Drupal\name\Traits\NameFormSettingsHelperTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'name' widget.
 */
#[FieldWidget(
  id: "name_default",
  label: new TranslatableMarkup("Name components"),
  field_types: ["name"]
)]
class NameWidget extends WidgetBase implements ContainerFactoryPluginInterface, TrustedCallbackInterface {

  use NameFormDisplaySettingsTrait;
  use NameFormSettingsHelperTrait;

  /**
   * Name options provider service.
   *
   * @var \Drupal\name\Service\NameOptionInterface
   */
  protected $optionsProvider;

  /**
   * Translated component labels and related metadata.
   *
   * @var \Drupal\name\Service\NameComponentMetadataInterface
   */
  protected NameComponentMetadataInterface $componentMetadata;

  /**
   * Constructs a NameWidget object.
   *
   * @param string $plugin_id
   *   The plugin_id for the widget.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the widget is associated.
   * @param array $settings
   *   The widget settings.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\name\Service\NameOptionInterface|null $options_provider
   *   Name options provider service.
   * @param \Drupal\name\Service\NameComponentMetadataInterface|null $component_metadata
   *   Component label metadata.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, ?NameOptionInterface $options_provider, ?NameComponentMetadataInterface $component_metadata) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    // @phpstan-ignore-next-line
    $this->optionsProvider = $options_provider ?? \Drupal::service('name.options_provider');
    // @phpstan-ignore-next-line
    $this->componentMetadata = $component_metadata ?? \Drupal::service('name.component_metadata');
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
      $container->get('name.options_provider', ContainerInterface::NULL_ON_INVALID_REFERENCE),
      $container->get('name.component_metadata', ContainerInterface::NULL_ON_INVALID_REFERENCE)
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $settings = $this->resolveSettings($form_state);
    $widget_settings = $this->getSettings();

    $element += [
      '#type' => 'name',
      '#title' => $this->fieldDefinition->getLabel(),
      '#components' => [],
      '#minimum_components' => array_filter($settings['minimum_components']),
      '#allow_family_or_given' => !empty($settings['allow_family_or_given']),
      '#default_value' => isset($items[$delta]) ? $items[$delta]->getValue() : NULL,
      '#field' => $this,
      '#credentials_inline' => empty($settings['credentials_inline']) ? 0 : 1,
      '#widget_layout' => empty($settings['widget_layout']) ? 'stacked' : $settings['widget_layout'],
      '#wrapper_type' => empty($widget_settings['wrapper_type']) ? 'fieldset' : $widget_settings['wrapper_type'],
      '#component_layout' => empty($settings['component_layout']) ? 'default' : $settings['component_layout'],
      '#show_component_required_marker' => !empty($settings['show_component_required_marker']),
      '#flag_required_input' => !empty($settings['flag_required_input']),
    ];

    // WidgetBase may have already overridden the display title
    // if the field is multi-cardinality.
    if (!empty($settings['field_title_display']) && $element['#title_display'] === 'before') {
      $element['#title_display'] = $settings['field_title_display'];
    }

    $components = array_filter($settings['components']);
    foreach ($this->componentMetadata->getTranslations() as $key => $title) {
      if (isset($components[$key])) {
        $element['#components'][$key] = $this->buildComponentProperties($key, $settings);
      }
      else {
        $element['#components'][$key]['exclude'] = TRUE;
      }
    }

    return $element;
  }

  /**
   * Resolves the active field widget settings.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   *
   * @return array
   *   The active widget settings.
   */
  protected function resolveSettings(FormStateInterface $form_state): array {
    $widget_settings = $this->getSettings();
    $field_settings = $this->getFieldSettings();
    if (!empty($widget_settings['override_field_settings'])
        && !$this->isDefaultValueWidget($form_state)) {
      return $widget_settings + $field_settings;
    }

    return $field_settings;
  }

  /**
   * Builds the render properties for a single name component.
   *
   * @param string $key
   *   The component key.
   * @param array $settings
   *   The active widget settings.
   *
   * @return array
   *   The component render properties.
   */
  private function buildComponentProperties(string $key, array $settings): array {
    $component = [
      'type' => 'textfield',
      'title' => Html::escape($settings['labels'][$key]),
      'title_display' => $settings['title_display'][$key] ?? 'description',
      'size' => !empty($settings['size'][$key]) ? $settings['size'][$key] : 60,
      'maxlength' => !empty($settings['max_length'][$key]) ? $settings['max_length'][$key] : 255,
    ];

    // Provides backwards compatibility with Drupal 6 modules.
    $field_type = ($key === 'title' || $key === 'generational') ? 'select' : 'text';
    $field_type = $settings['field_type'][$key] ?? ($settings[$key . '_field'] ?? $field_type);

    if ($field_type === 'select') {
      $component['type'] = 'select';
      $component['size'] = 1;
      $component['options'] = $this->optionsProvider->getOptions($this->fieldDefinition, $key);
    }
    elseif ($field_type === 'autocomplete') {
      $sources = array_filter($settings['autocomplete_source'][$key] ?? []);
      if (!empty($sources)) {
        $component['autocomplete'] = [
          '#autocomplete_route_name' => 'name.autocomplete',
          '#autocomplete_route_parameters' => [
            'field_name' => $this->fieldDefinition->getName(),
            'entity_type' => $this->fieldDefinition->getTargetEntityTypeId(),
            'bundle' => $this->fieldDefinition->getTargetBundle(),
            'component' => $key,
          ],
        ];
      }
    }

    return $component;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    $values = parent::massageFormValues($values, $form, $form_state);

    // Get fields that use selection.
    $selection_fields = array_keys($this->fieldDefinition->getSettings()['field_type'], 'select', TRUE);

    $new_values = [];
    foreach ($values as $item) {
      // For all selection fields, replace '_none' with an empty string.
      foreach ($selection_fields as $field_name) {
        if (isset($item[$field_name]) && $item[$field_name] == '_none') {
          $item[$field_name] = '';
        }
      }

      $value = implode('', array_intersect_key($item, $this->componentMetadata->getTranslations()));
      if (strlen($value)) {
        $new_values[] = $item;
      }
    }
    return $new_values;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $settings = self::getDefaultNameFormDisplaySettings();
    $settings['override_field_settings'] = FALSE;
    $settings['wrapper_type'] = 'fieldset';
    return $settings + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);
    $settings = $this->getSettings();

    $element['override_field_settings'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Override shared field settings'),
      '#default_value' => $this->getSetting('override_field_settings'),
      '#table_group' => 'above',
      '#weight' => -100,
    ];
    $element['wrapper_type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Wrapper type'),
      '#default_value' => $settings['wrapper_type'] ?? 'fieldset',
      '#options' => [
        'container' => $this->t('Container (invisible)'),
        'details' => $this->t('Details (collapsible)'),
        'fieldset' => $this->t('Fieldset (non-collapsible)'),
      ],
      '#table_group' => 'above',
      '#weight' => -99,
    ];

    $element += $this->getDefaultNameFormDisplaySettingsForm($settings, $form, $form_state);

    // Remove inaccessible name components as defined in the field settings.
    $field_settings = $this->getFieldSettings();
    $components = array_keys(array_filter($field_settings['components']));
    $components = array_combine($components, $components);
    $element['#excluded_components'] = array_diff_key($this->componentMetadata->getTranslations(), $components);
    $element['#pre_render'][] = [$this, 'fieldSettingsFormPreRender'];
    $element['widget_layout']['#states'] = [
      'visible' => [
        ':input[name$="[override_field_settings]"]' => [
          'checked' => TRUE,
        ],
      ],
    ];
    $element['field_title_display']['#states'] = [
      'visible' => [
        ':input[name$="[override_field_settings]"]' => [
          'checked' => TRUE,
        ],
      ],
    ];
    $element['name_settings']['#states'] = $element['widget_layout']['#states'];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    $widget_settings = $this->getSettings();
    $wrapper_options = [
      'container' => $this->t('Container (invisible)'),
      'details' => $this->t('Details (collapsible)'),
      'fieldset' => $this->t('Fieldset (non-collapsible)'),
    ];
    if (empty($widget_settings['override_field_settings'])) {
      array_unshift($summary, $this->t('Using shared settings'));
    }
    else {
      array_unshift($summary, $this->t('Overridden settings'));
    }
    $wrapper_type = $widget_settings['wrapper_type'] ?? 'fieldset';
    $wrapper_label = $wrapper_options[$wrapper_type] ?? $wrapper_options['fieldset'];
    $summary[] = $this->t('Wrapper type: @wrapper_type', ['@wrapper_type' => $wrapper_label]);

    return $summary;
  }

}
