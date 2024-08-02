<?php

namespace Drupal\layout_paragraphs\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a form for modifying Layout Paragraphs modal settings.
 */
class LayoutParagraphsModalSettingsForm extends ConfigFormBase {

  /**
   * SettingsForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typedConfigManager
   *   The typed config service.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    TypedConfigManagerInterface $typedConfigManager,
  ) {
    parent::__construct($config_factory, $typedConfigManager);
    $this->typedConfigManager = $typedConfigManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('config.typed')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'layout_paragraphs_modal_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'layout_paragraphs.modal_settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $lp_config = $this->configFactory()->getEditable('layout_paragraphs.modal_settings');
    $lp_config_schema = $this->typedConfigManager->getDefinition('layout_paragraphs.modal_settings') + ['mapping' => []];
    $lp_config_schema = $lp_config_schema['mapping'];

    $form['width'] = [
      '#type' => 'textfield',
      '#title' => $lp_config_schema['width']['label'],
      '#description' => $lp_config_schema['width']['description'],
      '#default_value' => $lp_config->get('width'),
    ];

    $form['height'] = [
      '#type' => 'textfield',
      '#title' => $lp_config_schema['height']['label'],
      '#description' => $lp_config_schema['height']['description'],
      '#default_value' => $lp_config->get('height'),
    ];

    $form['autoresize'] = [
      '#type' => 'checkbox',
      '#title' => $lp_config_schema['autoresize']['label'],
      '#description' => $lp_config_schema['autoresize']['description'],
      '#default_value' => $lp_config->get('autoresize'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $lp_config = $this->configFactory()->getEditable('layout_paragraphs.modal_settings');
    $lp_config->set('width', $form_state->getValue('width'));
    $lp_config->set('height', $form_state->getValue('height'));
    $lp_config->set('autoresize', $form_state->getValue('autoresize'));
    $lp_config->save();
    // Confirmation on form submission.
    $this->messenger()->addMessage($this->t('The Layout Paragraphs modal settings have been saved.'));
  }

}
