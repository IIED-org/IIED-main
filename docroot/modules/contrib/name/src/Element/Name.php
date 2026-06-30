<?php

namespace Drupal\name\Element;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Attribute\RenderElement;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Element\FormElementBase;
use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\Core\Template\Attribute;
use Drupal\name\Service\WidgetLayoutInterface;
use Drupal\name\Utility\NameComponents;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a name render element.
 */
#[RenderElement('name')]
class Name extends FormElementBase implements ContainerFactoryPluginInterface, TrustedCallbackInterface {

  /**
   * Field type plugin manager.
   */
  protected FieldTypePluginManagerInterface $fieldTypeManager;

  /**
   * Constructs a name form element plugin.
   *
   * @param array $configuration
   *   A configuration array containing plugin instance information.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldTypePluginManagerInterface $field_type_manager
   *   The field type plugin manager service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, FieldTypePluginManagerInterface $field_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->fieldTypeManager = $field_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.field.field_type')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return ['preRender'];
  }

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $parts = static::getComponentTranslations();
    $field_settings = $this->fieldTypeManager->getDefaultFieldSettings('name');

    return [
      '#input' => TRUE,
      '#process' => [[__CLASS__, 'process']],
      '#pre_render' => [[__CLASS__, 'preRender']],
      '#element_validate' => [[__CLASS__, 'validateElement']],
      '#theme_wrappers' => ['fieldset'],
      '#wrapper_type' => 'fieldset',
      '#open' => FALSE,
      '#summary_attributes' => [],
      '#show_component_required_marker' => 0,
      '#flag_required_input' => TRUE,
      '#default_value' => [
        'title' => '',
        'given' => '',
        'middle' => '',
        'family' => '',
        'generational' => '',
        'credentials' => '',
      ],
      '#minimum_components' => $field_settings['minimum_components'],
      '#allow_family_or_given' => $field_settings['allow_family_or_given'],
      '#components' => [
        'title' => [
          'type' => $field_settings['field_type']['title'],
          'title' => $parts['title'],
          'title_display' => 'description',
          'size' => $field_settings['size']['title'],
          'maxlength' => $field_settings['max_length']['title'],
          'options' => $field_settings['title_options'],
          'autocomplete' => FALSE,
        ],
        'given' => [
          'type' => 'textfield',
          'title' => $parts['given'],
          'title_display' => 'description',
          'size' => $field_settings['size']['given'],
          'maxlength' => $field_settings['max_length']['given'],
          'autocomplete' => FALSE,
        ],
        'middle' => [
          'type' => 'textfield',
          'title' => $parts['middle'],
          'title_display' => 'description',
          'size' => $field_settings['size']['middle'],
          'maxlength' => $field_settings['max_length']['middle'],
          'autocomplete' => FALSE,
        ],
        'family' => [
          'type' => 'textfield',
          'title' => $parts['family'],
          'title_display' => 'description',
          'size' => $field_settings['size']['family'],
          'maxlength' => $field_settings['max_length']['family'],
          'autocomplete' => FALSE,
        ],
        'generational' => [
          'type' => $field_settings['field_type']['generational'],
          'title' => $parts['generational'],
          'title_display' => 'description',
          'size' => $field_settings['size']['generational'],
          'maxlength' => $field_settings['max_length']['generational'],
          'options' => $field_settings['generational_options'],
          'autocomplete' => FALSE,
        ],
        'credentials' => [
          'type' => 'textfield',
          'title' => $parts['credentials'],
          'title_display' => 'description',
          'size' => $field_settings['size']['credentials'],
          'maxlength' => $field_settings['max_length']['credentials'],
          'autocomplete' => FALSE,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    $value = [
      'title' => '',
      'given' => '',
      'middle' => '',
      'family' => '',
      'generational' => '',
      'credentials' => '',
    ];
    if ($input === FALSE) {
      $element += ['#default_value' => []];
      return $element['#default_value'] + $value;
    }
    foreach ($value as $allowed_key => $default) {
      if (isset($input[$allowed_key]) && is_scalar($input[$allowed_key])) {
        $value[$allowed_key] = (string) $input[$allowed_key];
      }
    }
    return $value;
  }

  /**
   * Process callback: expands child component elements.
   */
  public static function process($element, FormStateInterface &$form_state, $complete_form) {
    $element['#tree'] = TRUE;
    if (empty($element['#value'])) {
      $element['#value'] = [];
    }
    $parts = static::getComponentTranslations();
    $components = $element['#components'];
    $min_components = (array) $element['#minimum_components'];
    foreach ($parts as $component => $title) {
      if (!isset($components[$component]['exclude'])) {
        $element[$component] = static::renderComponent(
          $components,
          $component,
          $element,
          isset($min_components[$component])
        );
        $attributes = [
          'class' => [
            'name-component-wrapper',
            'name-' . $component . '-wrapper',
          ],
        ];
        if ($component == 'credentials' && empty($element['#credentials_inline'])) {
          $attributes['class'][] = 'name-component-break';
        }
        $attributes = new Attribute($attributes);
        $element[$component]['#prefix'] = '<div' . $attributes . '>';
        $element[$component]['#suffix'] = '</div>';
      }
    }

    return $element;
  }

  /**
   * Builds a single component sub-element for process().
   *
   * @param array $components
   *   Core properties for all components.
   * @param string $component_key
   *   The component key of the component that is being rendered.
   * @param array $base_element
   *   Base FAPI element that makes up a name element.
   * @param bool $core
   *   Whether the component is required as part of a valid name.
   *
   * @return array
   *   The constructed component FAPI structure for a name element.
   */
  public static function renderComponent(array $components, $component_key, array $base_element, $core) {
    $component = $components[$component_key];
    $element = static::buildElementAttributes($component);
    $element['#attributes']['class'][] = 'name-' . $component_key;

    if ($core) {
      $element['#attributes']['class'][] = 'name-core-component';
    }

    $base_attributes = ['type', 'title', 'size', 'maxlength'];
    foreach ($base_attributes as $key) {
      $element['#' . $key] = $component[$key];
    }

    if (isset($base_element['#value'][$component_key])) {
      $element['#default_value'] = $base_element['#value'][$component_key];
    }
    if ($component['type'] == 'select') {
      $element['#options'] = $component['options'];
      $element['#size'] = 1;
      if (!empty($element['#options']) && is_array($element['#options'])) {
        [$normalized, $empty_label] = static::normalizeSelectOptions($element['#options']);

        if ($empty_label !== NULL) {
          $element['#empty_value'] = '_none';
          $element['#empty_option'] = $empty_label !== '' ? $empty_label : '--';
        }

        $element['#options'] = $normalized;
      }
    }
    elseif (!empty($component['autocomplete'])) {
      $element += $component['autocomplete'];
    }

    ['show_marker' => $show_component_required_marker, 'flag_required' => $flag_required_input]
      = static::resolveRequiredFlags((bool) $core, $base_element);

    return static::applyTitleDisplay(
      $element,
      $component['title_display'] ?? 'description',
      $show_component_required_marker,
      $flag_required_input,
    );
  }

  /**
   * Builds the base render array and merged attributes for a component.
   *
   * @param array $component
   *   Component definition.
   *
   * @return array
   *   Render array skeleton for the component.
   */
  private static function buildElementAttributes(array $component): array {
    $element = [];
    foreach (Element::properties($component) as $key) {
      $element[$key] = $component[$key];
    }
    $element['#attributes']['class'][] = 'name-element';

    if (isset($component['attributes'])) {
      foreach ($component['attributes'] as $key => $attribute) {
        if (isset($element['#attributes'][$key])) {
          if (is_array($attribute)) {
            $element['#attributes'][$key] = array_merge($element['#attributes'][$key], $attribute);
          }
          else {
            $element['#attributes'][$key] .= ' ' . $attribute;
          }
        }
        else {
          $element['#attributes'][$key] = $attribute;
        }
      }
    }

    return $element;
  }

  /**
   * Normalizes select options and extracts the placeholder label.
   *
   * @param array $options
   *   Select options.
   *
   * @return array
   *   Two-item array with normalized options and empty label.
   */
  private static function normalizeSelectOptions(array $options): array {
    $empty_label = NULL;
    // If the options array contains a '_none' key, set the empty label to the
    // value of the '_none' key and remove the '_none' key from the options
    // array. The normalized options array should only contain the options with
    // real values.
    if (array_key_exists('_none', $options)) {
      $empty_label = (string) $options['_none'];
      unset($options['_none']);
    }

    $clean_options = [];
    foreach ($options as $label) {
      $label = (string) $label;
      if ($empty_label === NULL && str_starts_with($label, '--')) {
        $empty_label = trim(substr($label, 2));
        continue;
      }
      $clean_options[] = $label;
    }

    $normalized = [];
    foreach ($clean_options as $label) {
      $normalized[$label] = $label;
    }

    return [$normalized, $empty_label];
  }

  /**
   * Resolves required marker and required input flags.
   *
   * @param bool $core
   *   Whether the component is a required core part.
   * @param array $base_element
   *   Base form element.
   *
   * @return array
   *   Marker and required flags.
   */
  private static function resolveRequiredFlags(bool $core, array $base_element): array {
    $has_field_parents = isset($base_element['#field_parents'])
      && is_array($base_element['#field_parents'])
      && !in_array('default_value_input', $base_element['#field_parents'], TRUE);

    return [
      'show_marker' => $core
      && !empty($base_element['#required'])
      && !empty($base_element['#show_component_required_marker'])
      && $has_field_parents,
      'flag_required' => $core
      && !empty($base_element['#required'])
      && !empty($base_element['#flag_required_input'])
      && $has_field_parents,
    ];
  }

  /**
   * Applies title display configuration and required metadata.
   *
   * @param array $element
   *   Component render array.
   * @param string $title_display
   *   Title display mode.
   * @param bool $show_component_required_marker
   *   Whether to show required marker styling.
   * @param bool $flag_required_input
   *   Whether to mark the field as required.
   *
   * @return array
   *   Updated render array.
   */
  private static function applyTitleDisplay(array $element, string $title_display, bool $show_component_required_marker, bool $flag_required_input): array {
    switch ($title_display) {
      case 'title':
        $element['#title_display'] = 'before';
        if ($flag_required_input) {
          $element['#required'] = TRUE;
        }

        if ($show_component_required_marker) {
          $element['#label_attributes']['class'][] = 'js-form-required';
          $element['#label_attributes']['class'][] = 'form-required';
        }
        break;

      case 'placeholder':
        $element['#attributes']['placeholder'] = $element['#title'];
        if ($show_component_required_marker) {
          $element['#attributes']['placeholder'] .= ' (' . t('Required') . ')';
        }
        if ($flag_required_input) {
          $element['#required'] = TRUE;
        }
        $element['#title_display'] = 'invisible';
        break;

      case 'none':
        $element['#title_display'] = 'invisible';
        if ($flag_required_input) {
          $element['#required'] = TRUE;
        }
        break;

      case 'attribute':
        $element['#title_display'] = 'attribute';
        $element['#attributes']['title'] = $element['#title'];
        if ($show_component_required_marker) {
          $element['#attributes']['title'] .= ' (' . t('Required') . ')';
        }
        break;

      case 'description':
      default:
        $label = [
          '#theme' => 'form_element_label',
          '#title' => $element['#title'],
          '#required' => $show_component_required_marker,
          '#title_display' => 'before',
        ];
        $element['#title_display'] = 'invisible';
        $element['#required'] = $flag_required_input;
        $element['#description'] = $label;
        $element['#after_build'][] = [static::class, 'componentDescriptionAfterBuildLabelAlter'];
        break;
    }

    return $element;
  }

  /**
   * After-build callback: sets #for on description label render arrays.
   */
  public static function componentDescriptionAfterBuildLabelAlter(array $element, FormStateInterface $form_state) {
    if (!empty($element['#description']) && !empty($element['#id']) && is_array($element['#description'])) {
      $element['#description']['#for'] = $element['#id'];
    }
    return $element;
  }

  /**
   * Element validate entrypoint; delegates to the element validator service.
   */
  public static function validateElement($element, FormStateInterface &$form_state) {
    $validator = \Drupal::getContainer()
      ->get('name.element_validator', ContainerInterface::NULL_ON_INVALID_REFERENCE);
    return $validator ? $validator->validate($element, $form_state) : $element;
  }

  /**
   * Whether a name value array is empty for validation purposes.
   */
  public static function validateIsEmpty(array $item): bool {
    foreach (NameComponents::coreKeys() as $key => $_) {
      if ($key === 'title' || $key === 'generational') {
        continue;
      }
      if (!empty($item[$key])) {
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * Pre-render: builds wrapper layout and moves children under _name.
   */
  public static function preRender($element) {
    static::applyDetailsWrapper($element);

    $layouts_service = \Drupal::getContainer()
      ->get('name.widget_layouts', ContainerInterface::NULL_ON_INVALID_REFERENCE);
    $layout = static::resolveWidgetLayout(
      $layouts_service instanceof WidgetLayoutInterface ? $layouts_service : NULL,
      $element['#widget_layout'] ?? NULL,
    );

    if (!empty($layout['library'])) {
      $element['#attached']['library'] = array_merge(
        $element['#attached']['library'] ?? [],
        $layout['library'],
      );
    }
    $attributes = new Attribute($layout['wrapper_attributes']);
    $element['_name'] = [
      '#prefix' => '<div' . $attributes . '>',
      '#suffix' => '</div>',
    ];

    foreach (static::getComponentTranslations() as $key => $title) {
      if (isset($element[$key])) {
        $element['_name'][$key] = $element[$key];
        unset($element[$key]);
      }
    }

    if (!empty($element['#component_layout'])) {
      NameComponents::applyLayout($element['_name'], $element['#component_layout']);
    }

    return $element;
  }

  /**
   * Applies the configured wrapper type to the element.
   *
   * @param array $element
   *   The render element being prepared.
   */
  public static function applyDetailsWrapper(array &$element): void {
    $wrapper_type = $element['#wrapper_type'] ?? 'fieldset';
    if (!in_array($wrapper_type, ['container', 'details', 'fieldset'], TRUE)) {
      $wrapper_type = 'fieldset';
    }

    $element['#theme_wrappers'] = [$wrapper_type];
    if ($wrapper_type !== 'details') {
      return;
    }

    $title = isset($element['#title'])
      ? Html::escape((string) $element['#title'])
      : '';
    $required_classes = !empty($element['#required'])
      ? ' class="js-form-required form-required"'
      : '';
    $open = !empty($element['#open']) ? ' open' : '';
    $element['#theme_wrappers'] = ['container'];
    $element['#prefix'] = '<details' . $open . '><summary'
      . $required_classes . '>' . $title . '</summary>';
    $element['#suffix'] = '</details>';
  }

  /**
   * Resolves a widget layout definition with required defaults.
   *
   * @param \Drupal\name\Service\WidgetLayoutInterface|null $service
   *   The widget layout service, if available.
   * @param string|null $widget_layout
   *   The requested widget layout key.
   *
   * @return array
   *   A normalized layout definition.
   */
  public static function resolveWidgetLayout(
    ?WidgetLayoutInterface $service,
    ?string $widget_layout,
  ): array {
    $default_layout = [
      'library' => [],
      'wrapper_attributes' => [
        'class' => ['name-widget-wrapper'],
      ],
    ];
    $layouts = $service ? $service->getLayouts() : [];
    if ($layouts === []) {
      $layout = $default_layout;
    }
    else {
      $layout = $layouts['stacked'] ?? reset($layouts) ?: $default_layout;
      if ($widget_layout !== NULL && isset($layouts[$widget_layout])) {
        $layout = $layouts[$widget_layout];
      }
    }

    $layout += [
      'library' => [],
      'wrapper_attributes' => [],
    ];
    $layout['wrapper_attributes'] += ['class' => []];
    if (!in_array('name-widget-wrapper', $layout['wrapper_attributes']['class'], TRUE)) {
      $layout['wrapper_attributes']['class'][] = 'name-widget-wrapper';
    }

    return $layout;
  }

  /**
   * Loads translated component labels from the metadata service.
   *
   * @return array<string, \Drupal\Core\StringTranslation\TranslatableMarkup|string>
   *   Keyed labels.
   */
  protected static function getComponentTranslations(): array {
    $metadata = \Drupal::getContainer()
      ->get('name.component_metadata', ContainerInterface::NULL_ON_INVALID_REFERENCE);
    return $metadata ? $metadata->getTranslations() : [];
  }

}
