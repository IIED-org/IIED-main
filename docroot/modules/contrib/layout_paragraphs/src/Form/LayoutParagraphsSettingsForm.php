<?php

namespace Drupal\layout_paragraphs\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class LayoutParagraphsSettingsForm.
 */
class LayoutParagraphsSettingsForm extends ConfigFormBase {

  /**
   * The typed config service.
   *
   * @var \Drupal\Core\Config\TypedConfigManagerInterface
   */
  protected $typedConfigManager;

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
    TypedConfigManagerInterface $typedConfigManager
  ) {
    parent::__construct($config_factory);
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
    $lp_config = $this->configFactory()->getEditable('layout_paragraphs.settings');
    $lp_config_schema = $this->typedConfigManager->getDefinition('layout_paragraphs.settings') + ['mapping' => []];
    $lp_config_schema = $lp_config_schema['mapping'];

    $form['show_paragraph_labels'] = [
      '#type' => 'checkbox',
      '#title' => $lp_config_schema['show_paragraph_labels']['label'],
      '#description' => $lp_config_schema['show_paragraph_labels']['description'],
      '#default_value' => $lp_config->get('show_paragraph_labels'),
    ];

    $form['show_layout_labels'] = [
      '#type' => 'checkbox',
      '#title' => $lp_config_schema['show_layout_labels']['label'],
      '#description' => $lp_config_schema['show_layout_labels']['description'],
      '#default_value' => $lp_config->get('show_layout_labels'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $lp_config = $this->configFactory()->getEditable('layout_paragraphs.settings');
    $lp_config->set('show_paragraph_labels', $form_state->getValue('show_paragraph_labels'));
    $lp_config->set('show_layout_labels', $form_state->getValue('show_layout_labels'));
    $lp_config->save();
    // Confirmation on form submission.
    $this->messenger()->addMessage($this->t('The Layout Paragraphs settings have been saved.'));
  }

}
