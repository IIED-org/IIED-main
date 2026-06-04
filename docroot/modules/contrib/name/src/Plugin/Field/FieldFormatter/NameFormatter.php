<?php

namespace Drupal\name\Plugin\Field\FieldFormatter;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Exception\UndefinedLinkTemplateException;
use Drupal\Core\Field\Attribute\FieldFormatter;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\name\Service\AdditionalComponentInterface;
use Drupal\name\Service\FormatOptionInterface;
use Drupal\name\Service\GeneratorInterface;
use Drupal\name\Service\NameFormatParserInterface;
use Drupal\name\Service\NameFormatterInterface;
use Drupal\name\Traits\NameAdditionalPreferredTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'name' formatter.
 *
 * The 'Default' formatter is different for integer fields on the one hand, and
 * for decimal and float fields on the other hand, in order to be able to use
 * different settings.
 */
#[FieldFormatter(
  id: "name_default",
  label: new TranslatableMarkup("Name formatter"),
  field_types: ["name"]
)]
class NameFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  use NameAdditionalPreferredTrait;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManager
   */
  protected $entityFieldManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The name formatter.
   *
   * @var \Drupal\name\Service\NameFormatterInterface
   */
  protected $formatter;

  /**
   * The name format parser.
   *
   * Directly called to format the examples without the fallback.
   *
   * @var \Drupal\name\Service\NameFormatParserInterface
   */
  protected $parser;

  /**
   * The name generator.
   *
   * @var \Drupal\name\Service\GeneratorInterface
   */
  protected $generator;

  /**
   * Format entity label/pattern options.
   *
   * @var \Drupal\name\Service\FormatOptionInterface|null
   */
  protected $formatOptions;

  /**
   * Additional component values for preferred/alternative references.
   *
   * @var \Drupal\name\Service\AdditionalComponentInterface|null
   */
  protected $additionalComponent;

  /**
   * Constructs a NameFormatter instance.
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
   *   Any third party settings settings.
   * @param \Drupal\Core\Entity\EntityFieldManager $entityFieldManager
   *   The entity field manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\name\Service\NameFormatterInterface $formatter
   *   The name formatter.
   * @param \Drupal\name\Service\NameFormatParserInterface $parser
   *   The name format parser.
   * @param \Drupal\name\Service\GeneratorInterface $generator
   *   The name generator.
   * @param \Drupal\name\Service\FormatOptionInterface|null $format_options
   *   The name format options service.
   * @param \Drupal\name\Service\AdditionalComponentInterface|null $additional_component
   *   The additional component resolver.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, EntityFieldManager $entityFieldManager, EntityTypeManagerInterface $entityTypeManager, NameFormatterInterface $formatter, NameFormatParserInterface $parser, GeneratorInterface $generator, ?FormatOptionInterface $format_options, ?AdditionalComponentInterface $additional_component) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);

    $this->entityFieldManager = $entityFieldManager;
    $this->entityTypeManager = $entityTypeManager;
    $this->formatter = $formatter;
    $this->parser = $parser;
    $this->generator = $generator;
    $this->formatOptions = $format_options;
    $this->additionalComponent = $additional_component;
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
      $container->get('entity_field.manager'),
      $container->get('entity_type.manager'),
      $container->get('name.formatter'),
      $container->get('name.format_parser'),
      $container->get('name.generator'),
      $container->get('name.format_options', ContainerInterface::NULL_ON_INVALID_REFERENCE),
      $container->get('name.additional_component', ContainerInterface::NULL_ON_INVALID_REFERENCE)
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $settings = parent::defaultSettings();
    $settings += [
      "format" => "default",
      "markup" => "none",
      "list_format" => "",
      "link_target" => "",
    ];
    $settings += self::getDefaultAdditionalPreferredSettings();
    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);
    $elements['format'] = [
      '#type' => 'select',
      '#title' => $this->t('Name format'),
      '#default_value' => $this->getSetting('format'),
      '#options' => $this->formatOptions?->getCustomFormatOptions() ?? [],
      '#required' => TRUE,
    ];

    $elements['list_format'] = [
      '#type' => 'select',
      '#title' => $this->t('List format'),
      '#default_value' => $this->getSetting('list_format'),
      '#empty_option' => $this->t('-- individually --'),
      '#options' => $this->formatOptions?->getCustomListFormatOptions() ?? [],
    ];

    $elements['markup'] = [
      '#type' => 'select',
      '#title' => $this->t('Markup'),
      '#default_value' => $this->getSetting('markup'),
      '#options' => $this->parser->getMarkupOptions(),
      '#description' => $this->t('This option wraps the individual components of the name in SPAN elements with corresponding classes to the component.'),
      '#required' => TRUE,
    ];
    if (!empty($this->fieldDefinition->getTargetBundle())) {
      $elements['link_target'] = [
        '#type' => 'select',
        '#title' => $this->t('Link Target'),
        '#default_value' => $this->getSetting('link_target'),
        '#empty_option' => $this->t('-- no link --'),
        '#options' => $this->getLinkableTargets(),
      ];

      $elements += $this->getNameAdditionalPreferredSettingsForm($form, $form_state);
    }
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $settings = $this->getSettings();
    $summary = [];

    // Name format.
    $machine_name = $settings['format'] ?? 'default';
    $name_format = $this->entityTypeManager->getStorage('name_format')->load($machine_name);
    if ($name_format) {
      $summary[] = $this->t('Format: @format (@machine_name)', [
        '@format' => $name_format->label(),
        '@machine_name' => $name_format->id(),
      ]);
    }
    else {
      $summary[] = $this->t(
        'Format: @missing@line_break@fallback',
        [
          '@missing' => new FormattableMarkup('<strong>@text</strong>', [
            '@text' => $this->t('Missing format.'),
          ]),
          '@line_break' => new FormattableMarkup('<br/>', []),
          '@fallback' => $this->t('This field will be displayed using the Default format.'),
        ]
      );
    }

    // List format.
    if (!isset($settings['list_format']) || $settings['list_format'] == '') {
      $summary[] = $this->t('List format: Individually');
    }
    else {
      $machine_name = $settings['list_format'] ?? 'default';
      $name_list_format = $this->entityTypeManager->getStorage('name_list_format')->load($machine_name);
      if ($name_list_format) {
        $summary[] = $this->t('List format: @format (@machine_name)', [
          '@format' => $name_list_format->label(),
          '@machine_name' => $name_list_format->id(),
        ]);
      }
      else {
        $summary[] = $this->t(
          'List format: @missing@line_break@fallback',
          [
            '@missing' => new FormattableMarkup('<strong>@text</strong>', [
              '@text' => $this->t('Missing list format.'),
            ]),
            '@line_break' => new FormattableMarkup('<br/>', []),
            '@fallback' => $this->t('This field will be displayed using the Default list format.'),
          ]
        );
      }
    }

    // Additional options.
    $markup_options = $this->parser->getMarkupOptions();
    $summary[] = $this->t('Markup: @type', [
      '@type' => $markup_options[$this->getSetting('markup')],
    ]);

    if (!empty($settings['link_target'])) {
      $targets = $this->getLinkableTargets();
      $summary[] = $this->t('Link: @target', [
        '@target' => empty($targets[$settings['link_target']]) ? $this->t('-- invalid --') : $targets[$settings['link_target']],
      ]);
    }

    $this->settingsNameAdditionalPreferredSummary($summary);

    // Provide an example of the selected format.
    if ($name_format) {
      $names = $this->generator->loadSampleValues(1, $this->fieldDefinition);
      if (is_array($names) && $names !== [] && ($name = reset($names))) {
        $previous_markup = $this->formatter->getSetting('markup');
        $this->formatter->setSetting('markup', $this->getSetting('markup'));
        $formatted = $this->formatter->format($name, $name_format->id());
        $this->formatter->setSetting('markup', $previous_markup);
        if (empty($formatted)) {
          $summary[] = $this->t('Example: @empty', [
            '@empty' => new FormattableMarkup('<em>@text</em>', [
              '@text' => '<<empty>>',
            ]),
          ]);
        }
        else {
          $summary[] = $this->t('Example: @example', [
            '@example' => $formatted,
          ]);
        }
      }
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $items_count = $items->count();
    if (!$items_count) {
      return $elements;
    }

    $settings = $this->settings;

    $format = $settings['format'] ?? 'default';
    $is_multiple = $this->fieldDefinition->getFieldStorageDefinition()->isMultiple() && $items_count > 1;
    $list_format = $is_multiple && !empty($settings['list_format']) ? $settings['list_format'] : '';

    $extra = $this->parseAdditionalComponents($items);
    $extra['url'] = empty($settings['link_target']) ? NULL : $this->getLinkableTargetUrl($items);

    $item_array = [];
    foreach ($items as $item) {
      $components = $item->toArray() + $extra;
      $item_array[] = $components;
    }

    $this->formatter->setSetting('markup', $this->getSetting('markup'));

    if ($list_format) {
      $elements[0]['#markup'] = $this->formatter->formatList($item_array, $format, $list_format, $langcode);
    }
    else {
      foreach ($item_array as $delta => $item) {
        $elements[$delta]['#markup'] = $this->formatter->format($item, $format, $langcode);
      }
    }

    return $elements;
  }

  /**
   * Determines with markup should be added to the results.
   *
   * @return bool
   *   Returns TRUE if markup should be applied.
   */
  protected function useMarkup() {
    return $this->settings['markup'];
  }

  /**
   * Find any linkable targets.
   *
   * @return array
   *   An array of possible targets.
   */
  protected function getLinkableTargets() {
    $targets = ['_self' => $this->t('Entity URL')];
    $bundle = $this->fieldDefinition->getTargetBundle();
    $entity_type_id = $this->fieldDefinition->getTargetEntityTypeId();
    $fields = $this->entityFieldManager->getFieldDefinitions($entity_type_id, $bundle);
    foreach ($fields as $field) {
      if (!$field->getFieldStorageDefinition()->isBaseField()) {
        switch ($field->getType()) {
          case 'entity_reference':
          case 'link':
            $targets[$field->getName()] = $field->getLabel();
            break;
        }
      }
    }
    return $targets;
  }

  /**
   * Gets the URL object.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   The name formatters FieldItemList.
   *
   * @return \Drupal\Core\Url
   *   Returns a Url object.
   */
  protected function getLinkableTargetUrl(FieldItemListInterface $items) {
    try {
      $parent = $items->getEntity();
      $link_target = $this->settings['link_target'];

      if ($link_target === '_self') {
        return $this->resolveEntitySelfUrl($parent) ?? Url::fromRoute('<none>');
      }

      if ($parent->hasField($link_target)) {
        $target_items = $parent->get($link_target);
        if (!$target_items->isEmpty()) {
          return $this->resolveFieldTargetUrl($target_items) ?? Url::fromRoute('<none>');
        }
      }
    }
    catch (UndefinedLinkTemplateException) {
    }

    return Url::fromRoute('<none>');
  }

  /**
   * Gets the current entity URL when the entity can be viewed.
   *
   * @param \Drupal\Core\Entity\EntityInterface $parent
   *   The entity that owns the name field.
   *
   * @return \Drupal\Core\Url|null
   *   The entity URL, or NULL when it cannot be linked.
   */
  private function resolveEntitySelfUrl(EntityInterface $parent): ?Url {
    if (!$parent->isNew() && $parent->access('view')) {
      return $parent->toUrl();
    }

    return NULL;
  }

  /**
   * Gets a URL from a supported link target field item list.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $target_items
   *   The field item list configured as the link target.
   *
   * @return \Drupal\Core\Url|null
   *   The resolved URL, or NULL when the field type is unsupported.
   */
  private function resolveFieldTargetUrl(FieldItemListInterface $target_items): ?Url {
    return match ($target_items->getFieldDefinition()->getType()) {
      'entity_reference' => $this->resolveEntityReferenceUrl($target_items),
      'link' => $this->resolveLinkFieldUrl($target_items),
      default => NULL,
    };
  }

  /**
   * Gets the first accessible referenced entity URL.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $target_items
   *   The entity reference field item list.
   *
   * @return \Drupal\Core\Url|null
   *   The referenced entity URL, or NULL when no target can be linked.
   */
  private function resolveEntityReferenceUrl(FieldItemListInterface $target_items): ?Url {
    foreach ($target_items as $item) {
      if (!empty($item->entity) && !$item->entity->isNew() && $item->entity->access('view')) {
        return $item->entity->toUrl();
      }
    }

    return NULL;
  }

  /**
   * Gets the first URL from a link field item list.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $target_items
   *   The link field item list.
   *
   * @return \Drupal\Core\Url|null
   *   The link field URL, or NULL when no URL is available.
   */
  private function resolveLinkFieldUrl(FieldItemListInterface $target_items): ?Url {
    foreach ($target_items as $item) {
      if ($url = $item->getUrl()) {
        return $url;
      }
    }

    return NULL;
  }

  /**
   * Gets any additional linked components.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   The name formatters FieldItemList.
   *
   * @return array
   *   An array of any additional components if set.
   */
  protected function parseAdditionalComponents(FieldItemListInterface $items) {
    $extra = [];
    foreach (['preferred', 'alternative'] as $key) {
      $key_value = $this->getSetting($key . '_field_reference');
      $sep_value = $this->getSetting($key . '_field_reference_separator');
      if (!$key_value) {
        $key_value = $this->fieldDefinition->getSetting($key . '_field_reference');
        $sep_value = $this->fieldDefinition->getSetting($key . '_field_reference_separator');
      }
      if ($this->additionalComponent && ($value = $this->additionalComponent->getAdditionalComponent($items, $key_value, $sep_value))) {
        $extra[$key] = $value;
      }
    }

    return $extra;
  }

  /**
   * Gets the field definition.
   *
   * This is used by NameAdditionalPreferredTrait.
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface
   *   The field definition.
   */
  protected function getFieldDefinition() {
    return $this->fieldDefinition;
  }

}
