<?php

namespace Drupal\datalayer\Hook;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Provides form hook implementations for the datalayer module.
 */
class DatalayerFormHooks {
  use StringTranslationTrait;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new DatalayerFormHooks object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * Implements hook_form_field_config_edit_form_alter().
   */
  #[Hook('form_field_config_edit_form_alter')]
  public function fieldConfigEditFormAlter(array &$form, FormStateInterface $form_state, $form_id): void {
    $datalayer_settings = $this->configFactory->get('datalayer.settings');
    if ($datalayer_settings->get('output_fields')) {
      $field = $form_state->getFormObject()->getEntity();
      $form['third_party_settings']['datalayer']['expose'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Expose field data in JavaScript <code>dataLayer</code> variable.'),
        '#default_value' => $field->getThirdPartySetting('datalayer', 'expose', 0),
        '#description' => $this->t("Checking this box will result in this field's value being included in the <code>dataLayer</code> object provided by the dataLayer module."),
      ];
      $form['third_party_settings']['datalayer']['label'] = [
        '#type' => 'textfield',
        '#title' => $this->t('DataLayer label'),
        '#default_value' => $field->getThirdPartySetting('datalayer', 'label', $field->get('field_name')),
        '#description' => $this->t('Enter the label/key to use when adding this field to the <code>datalayer</code> object. Example; <code>dataLayer.fieldName: value</code>'),
        '#states' => [
          'visible' => [
            ':input[name="third_party_settings[datalayer][expose]"]' => [
              'checked' => TRUE,
            ],
          ],
        ],
      ];
    }
    $form['actions']['submit']['#submit'][] = 'datalayer_field_config_submit';
  }

}
