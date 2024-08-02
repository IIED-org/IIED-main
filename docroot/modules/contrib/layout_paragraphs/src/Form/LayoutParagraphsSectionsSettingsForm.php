<?php

namespace Drupal\layout_paragraphs\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Layout\LayoutPluginManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Defines a form for modifying Layout Paragraphs sections settings.
 */
class LayoutParagraphsSectionsSettingsForm extends ConfigFormBase {

  /**
   * The entity type bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * The Entity Type Manager service property.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The Layouts Manager.
   *
   * @var \Drupal\Core\Layout\LayoutPluginManagerInterface
   */
  protected $layoutPluginManager;

  /**
   * SettingsForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typedConfigManager
   *   The typed config service.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Core entity type manager service.
   * @param \Drupal\Core\Layout\LayoutPluginManagerInterface $layout_plugin_manager
   *   Core layout plugin manager service.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    TypedConfigManagerInterface $typedConfigManager,
    EntityTypeBundleInfoInterface $entity_type_bundle_info,
    EntityTypeManagerInterface $entity_type_manager,
    LayoutPluginManagerInterface $layout_plugin_manager
  ) {
    parent::__construct($config_factory, $typedConfigManager);
    $this->typedConfigManager = $typedConfigManager;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
    $this->entityTypeManager = $entity_type_manager;
    $this->layoutPluginManager = $layout_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('config.typed'),
      $container->get('entity_type.bundle.info'),
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.core.layout')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'layout_paragraphs_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'layout_paragraphs.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $paragraph_bundles = $this->entityTypeBundleInfo->getBundleInfo('paragraph');
    $form['description'] = [
      '#type' => 'markup',
      '#markup' => '<p>' . $this->t('Choose at least one paragraph type to use as a Layout Section.') . '</p>',
    ];
    $layout_options = $this->layoutPluginManager->getLayoutOptions();
    $paragraphs_type_storage = $this->entityTypeManager->getStorage('paragraphs_type');
    foreach ($paragraph_bundles as $name => $paragraph_bundle) {
      /** @var \Drupal\paragraphs\Entity\ParagraphsType $paragraphs_type */
      $paragraphs_type = $paragraphs_type_storage->load($name);
      $layout_paragraphs_behavior = $paragraphs_type->getBehaviorPlugin('layout_paragraphs');
      $layout_paragraphs_behavior_config = $layout_paragraphs_behavior->getConfiguration();
      $form[$name] = [
        '#type' => 'fieldset',
        '#title' => $paragraph_bundle['label'],
        '#description' => $paragraphs_type->getDescription(),
      ];
      $form[$name][$name] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Use as a Layout Section'),
        '#default_value' => !empty($layout_paragraphs_behavior_config['enabled']),
      ];
      $form[$name][$name . '_layouts'] = [
        '#type' => 'select',
        '#title' => $this->t('Available Layouts for @label Paragraphs', ['@label' => $paragraph_bundle['label']]),
        '#options' => $layout_options,
        '#multiple' => TRUE,
        '#default_value' => array_keys($layout_paragraphs_behavior_config['available_layouts']),
        '#size' => count($layout_options) < 8 ? count($layout_options) * 2 : 10,
        '#states' => [
          'visible' => [
            ':input[name="' . $name . '"]' => ['checked' => TRUE],
          ],
        ],
      ];
    }
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $paragraph_bundles = $this->entityTypeBundleInfo->getBundleInfo('paragraph');
    $paragraphs_type_storage = $this->entityTypeManager->getStorage('paragraphs_type');
    $layouts = $this->layoutPluginManager->getSortedDefinitions();
    foreach (array_keys($paragraph_bundles) as $name) {
      /** @var \Drupal\paragraphs\Entity\ParagraphsType $paragraphs_type */
      $paragraphs_type = $paragraphs_type_storage->load($name);
      $layout_paragraphs_behavior = $paragraphs_type->getBehaviorPlugin('layout_paragraphs');
      if ($form_state->getValue($name)) {
        $layout_paragraphs_behavior->setConfiguration(['enabled' => TRUE]);
        $config = [
          'enabled' => TRUE,
          'available_layouts' => [],
        ];
        foreach ($form_state->getValue($name . '_layouts') as $layout_id) {
          $config['available_layouts'][$layout_id] = $layouts[$layout_id]->getLabel();
        }
        $layout_paragraphs_behavior->setConfiguration($config);
      }
      else {
        $layout_paragraphs_behavior->setConfiguration(['enabled' => FALSE]);
      }
      $paragraphs_type->save();
    }
    $this->messenger()->addMessage($this->t('The Layout Paragraphs settings have been saved.'));
  }

}
