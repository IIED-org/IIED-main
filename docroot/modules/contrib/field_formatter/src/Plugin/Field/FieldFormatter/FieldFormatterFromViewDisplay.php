<?php

namespace Drupal\field_formatter\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'link' formatter.
 *
 * @FieldFormatter(
 *   id = "field_formatter_from_view_display",
 *   label = @Translation("Field formatter from view display"),
 *   field_types = {
 *     "entity_reference",
 *     "entity_reference_revisions"
 *   }
 * )
 */
class FieldFormatterFromViewDisplay extends FieldFormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The display repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepository
   */
  protected $entityDisplayRepository;

  /**
   * Constructs a FieldFormatterFromViewDisplay object.
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
   *   Third party settings.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager object for retrieving the correct language code.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   *   The entity display repository.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, LanguageManagerInterface $language_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info, EntityFieldManagerInterface $entity_field_manager, EntityTypeManagerInterface $entity_type_manager, EntityDisplayRepositoryInterface $entity_display_repository) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings, $language_manager, $entity_type_bundle_info, $entity_field_manager);
    $this->entityTypeManager = $entity_type_manager;
    $this->entityDisplayRepository = $entity_display_repository;
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
      $container->get('entity_field.manager'),
      $container->get('entity_type.manager'),
      $container->get('entity_display.repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $settings = parent::defaultSettings() + [
      'view_mode' => 'default',
      'field_name' => '',
    ];
    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);
    $options = [];

    $er_target_entity_type = $this->fieldDefinition->getSetting('target_type');
    $er_handler_settings = $this->fieldDefinition->getSetting('handler_settings');
    $display_repository = $this->entityDisplayRepository;
    if (!empty($er_handler_settings['target_bundles'])) {
      $er_target_bundles = $er_handler_settings['target_bundles'];
      foreach ($er_target_bundles as $er_target_bundle => $er_target_bundle_val) {
        $optionsByBundle = $display_repository->getViewModeOptionsByBundle($er_target_entity_type, $er_target_bundle);
        // Add option by key to prevent duplicates:
        foreach ($optionsByBundle as $key => $option) {
          $options[$key] = $option;
        }
      }
    }
    else {
      $options = $display_repository->getViewModeOptions($er_target_entity_type);
    }

    // Sort options in alphabetcial order:
    asort($options);
    $form['view_mode'] = [
      '#title' => $this->t('View mode'),
      '#type' => 'select',
      '#options' => $options,
      '#default_value' => $this->getSetting('view_mode'),
      '#empty_option' => 'Default',
      '#empty_value' => 'default',
    ];

    $form['field_name'] = [
      '#type' => 'select',
      '#title' => $this->t('Field name'),
      '#default_value' => $this->getSetting('field_name'),
      '#options' => $this->getAvailableFieldNames(),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function getViewDisplay($bundle_id) {
    if (!isset($this->viewDisplay[$bundle_id])) {
      $field_name = $this->getSetting('field_name');
      $entity_type_id = $this->fieldDefinition->getSetting('target_type');
      if (($view_mode = $this->getSetting('view_mode')) && $view_display = EntityViewDisplay::load($entity_type_id . '.' . $bundle_id . '.' . $view_mode)) {
        /** @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface $view_display */
        $components = $view_display->getComponents();
        foreach ($components as $component_name => $component) {
          if ($component_name != $field_name) {
            $view_display->removeComponent($component_name);
          }
        }
        $this->viewDisplay[$bundle_id] = $view_display;
      }
    }
    return $this->viewDisplay[$bundle_id];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    if ($view_mode = $this->getSetting('view_mode')) {
      $summary[] = $this->t('View display %view_mode used.', ['%view_mode' => $view_mode]);
    }
    else {
      $summary[] = $this->t('View display not configured.');
    }

    return $summary;
  }

}
