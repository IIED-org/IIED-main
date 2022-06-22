<?php

namespace Drupal\layout_builder_styles\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigManager;

/**
 * Class ModuleSettingsForm.
 */
class ModuleSettingsForm extends FormBase {

  /**
   * Drupal\Core\Config\ConfigManager definition.
   *
   * @var \Drupal\Core\Config\ConfigManager
   */
  protected $configManager;

  /**
   * Constructs a new RestrictionPluginConfigForm object.
   */
  public function __construct(ConfigManager $config_manager) {
    $this->configManager = $config_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'layout_builder_styles_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->configFactory()->get('layout_builder_styles.settings');
    $form['multiselect'] = [
      '#type' => 'radios',
      '#title' => $this->t('Multiple styles'),
      '#default_value' => $config->get('multiselect'),
      '#options' => [
        'single' => $this->t('Limit one style per given section or block.'),
        'multiple' => $this->t('Allow multiple styles to be selected on a given section or block.'),
      ],
      '#description' => $this->t('Limiting to one style per section or block is useful for sites which need each style choice to dictate the markup of the template. When this option is chosen, a corresponding block theme hook suggestion is provided. Allowing multiple styles is useful for sites whose style declarations correspond wholly to CSS modifications, and whose styles are designed to be used in conjunction. <strong>Note</strong>: if you switch this setting from "multiple" to "single" after Layout Builder Styles is already in use, any blocks that had been assigned multiple styles will initially continue to render with multiple styles until the block form is revisted; at that point, the Layout Builder Style options will be reset to no selections, and content editors should take care to reapply the single style they want to use.'),
    ];
    $form['form_type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Form element for multiple styles'),
      '#default_value' => $config->get('form_type'),
      '#options' => [
        'checkboxes' => $this->t('Checkboxes'),
        'multiple-select' => $this->t('Select (multiple) box'),
      ],
      '#description' => $this->t('Choose whether the styles selector should display as multiple checkboxes or a select (multiple) box.'),
      '#states' => [
        'visible' => [
          ':input[name="multiselect"]' => ['value' => 'multiple'],
        ],
      ],
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save configuration'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $multiselect = $form_state->getValue('multiselect');
    $form_type = $form_state->getValue('form_type');
    $config = $this->configFactory()->getEditable('layout_builder_styles.settings');
    $config->set('multiselect', $multiselect);
    $config->set('form_type', $form_type);
    $config->save();
  }

}
