<?php

namespace Drupal\field_formatter\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceFormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for field formatters.
 */
abstract class FieldFormatterBase extends EntityReferenceFormatterBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Entity view display.
   *
   * @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface
   */
  protected $viewDisplay;

  /**
   * The entity bundle info.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * The language manager object for retrieving the correct language code.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The entity field manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Constructs a FieldFormatterBase object.
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
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager object for retrieving the correct language code.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, LanguageManagerInterface $language_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info, EntityFieldManagerInterface $entity_field_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->languageManager = $language_manager;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
    $this->entityFieldManager = $entity_field_manager;
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
      $container->get('language_manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('entity_field.manager')
    );
  }

  /**
   * Gets entity view display for a bundle.
   *
   * @param string $bundle_id
   *   The bundle ID.
   *
   * @return \Drupal\Core\Entity\Display\EntityViewDisplayInterface
   *   Entity view display.
   */
  abstract protected function getViewDisplay($bundle_id);

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return parent::defaultSettings() + [
      'link_to_entity' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    $entity_type = $this->entityTypeManager->getDefinition($this->fieldDefinition->getTargetEntityTypeId());
    $form['link_to_entity'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Link to this @entity_label', ['@entity_label' => $entity_type->getLabel()]),
      '#description' => $this->t('Links the field to this (parent) entity (if supported by the field formatter, overriding referenced entity link settings).'),
      '#default_value' => $this->getSetting('link_to_entity'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    if ($field_name = $this->getSetting('field_name')) {
      $summary[] = $this->t('Field %field_name displayed.', ['%field_name' => $field_name]);
    }
    else {
      $summary[] = $this->t('Field not configured.');
    }

    if ($this->getSetting('link_to_entity')) {
      $entity_type = $this->entityTypeManager->getDefinition($this->fieldDefinition->getTargetEntityTypeId());
      $summary[] = $this->t('Linked to this (parent) @entity_label', ['@entity_label' => $entity_type->getLabel()]);
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    /** @var \Drupal\Core\Entity\FieldableEntityInterface $entity */
    $entities = $this->getEntitiesToView($items, $langcode);

    $build = [];
    foreach ($entities as $delta => $entity) {
      $viewDisplay = $this->getViewDisplay($entity->bundle());
      if (!empty($viewDisplay)) {
        $entityDisplay = $viewDisplay->build($entity);
        // Link to entity functionality:
        if ($this->getSetting('link_to_entity') && ($entity = $items->getEntity()) && $entity->hasLinkTemplate('canonical') && !$entity->isNew()) {
          $entityUrl = $entity->toUrl('canonical', ['language' => $this->languageManager->getLanguage($langcode)]);
          /*
          @todo This implementation overwrites the ['#url'] value of the
          selected field, which only works for fields what support
          ['#url'] and has the advantage that no extra wrapper is created
          AND links in links are prevented. Anyway this will not work for
          all field types.
           */
          //@codingStandardsIgnoreLine
          foreach ($entityDisplay as $field_name => &$field) {
            $field[0]['#url'] = $entityUrl;
          }

          /*
          @todo Alternative from field_link formatter, which should
          work for all field types but may lead to links in links
          and additional wrapper markup.
          $field_name = $this->fieldDefinition->getName();
          $field_output = isset($entityDisplay[$field_name]) ?
          $entityDisplay[$field_name] : [];
          foreach (Element::children($field_output) as $key) {
          $entityDisplay[$field_name][$key] = [
          '#type' => 'link',
          '#url' => $entityUrl,
          '#title' => $field_output[$key],
          ];
          }
           */
        }
        $build[$delta] = $entityDisplay;
      }
    }
    return $build;
  }

  /**
   * Gets list of supported fields.
   *
   * @return array
   *   List of fields that are supported keyed by field machine name.
   */
  protected function getAvailableFieldNames() {
    $field_names = [];
    $entity_type_id = $this->fieldDefinition->getSetting('target_type');

    /** @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface $bundle_info */
    $bundle_info = $this->entityTypeBundleInfo;
    $fieldDefinitionHandlerSettings = $this->fieldDefinition->getSetting('handler_settings');
    $target_bundles = empty($fieldDefinitionHandlerSettings['target_bundles']) ? array_keys($bundle_info->getBundleInfo($entity_type_id)) : $fieldDefinitionHandlerSettings['target_bundles'];
    foreach ($target_bundles as $value) {
      $bundle_field_names = array_map(
        function (FieldDefinitionInterface $field_definition) {
          return $field_definition->getLabel() . ' (' . $field_definition->getName() . ')';
        },
        $this->entityFieldManager->getFieldDefinitions($entity_type_id, $value)
      );
      $field_names = array_merge($field_names, $bundle_field_names);
    }
    return $field_names;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    $entity_type = \Drupal::entityTypeManager()
      ->getDefinition($field_definition->getTargetEntityTypeId());
    return $entity_type->entityClassImplements(FieldableEntityInterface::class);
  }

  /**
   * Helper function to retrieve the $setting from the $form_state.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
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
