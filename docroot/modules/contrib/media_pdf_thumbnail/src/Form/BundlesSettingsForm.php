<?php

namespace Drupal\media_pdf_thumbnail\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure example settings for this site.
 */
class BundlesSettingsForm extends ConfigFormBase {

  /**
   * Config settings.
   *
   * @var string
   */
  const SETTINGS = 'media_pdf_thumbnail.bundles.settings';

  /**
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * BundlesSettingsForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entityTypeBundleInfo
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeBundleInfoInterface $entityTypeBundleInfo, EntityFieldManagerInterface $entityFieldManager) {
    parent::__construct($config_factory);
    $this->entityTypeBundleInfo = $entityTypeBundleInfo;
    $this->entityFieldManager = $entityFieldManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('config.factory'),
      $container->get('entity_type.bundle.info'),
      $container->get('entity_field.manager'));
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'bundles_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(static::SETTINGS);

    foreach ($this->getFieldsList() as $bundleId => $infos) {
      if (!empty($infos['fields'])) {
        $form[$bundleId] = [
          '#type' => 'fieldset',
          '#title' => t($infos['label']),
          '#collapsible' => FALSE,
          '#collapsed' => FALSE,
        ];

        $form[$bundleId][$bundleId . '_field'] = [
          '#type' => 'select',
          '#title' => $this->t('Field to use to generate thumbnail'),
          '#description' => $this->t('The file attached must be pdf type, otherwise it will be ignored.</br>If that field is multivalued, only the first value will be used.'),
          '#options' => $infos['fields'],
          '#default_value' => $config->get($bundleId . '_field'),
        ];

        $form[$bundleId][$bundleId . '_enable'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Enable'),
          '#default_value' => $config->get($bundleId . '_enable'),
        ];
      }
    }
    return parent::buildForm($form, $form_state);
  }

  /**
   * @return array
   */
  protected function getFieldsList() {
    $bundles = $this->entityTypeBundleInfo->getBundleInfo('media');
    $output = [];
    foreach ($bundles as $id => $bundle) {
      $output[$id]['label'] = $bundle['label'];
      foreach ($this->entityFieldManager->getFieldDefinitions('media',
        $id) as $fieldDefinition) {
        if ($fieldDefinition->getType() == 'file') {
          $output[$id]['fields'][$fieldDefinition->getName()] = $fieldDefinition->getName();
        }
      }
    }
    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $editableConfig = $this->configFactory->getEditable(static::SETTINGS);
    foreach ($this->getFieldsList() as $bundleId => $infos) {
      $editableConfig->set($bundleId . '_field',
        $form_state->getValue($bundleId . '_field'));
      $editableConfig->set($bundleId . '_enable',
        $form_state->getValue($bundleId . '_enable'));
    }
    $editableConfig->save();
    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      static::SETTINGS,
    ];
  }

}
