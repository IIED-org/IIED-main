<?php

namespace Drupal\views_json_source\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form for configure values.
 */
class ViewsJsonSourceSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'views_json_source.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'views_json_source_configuration';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('views_json_source.settings');

    $form['cache_ttl'] = [
      '#type' => 'number',
      '#min' => 0,
      '#size' => 15,
      '#title' => $this->t('Cache Duration (seconds)'),
      '#description' => $this->t('The duration till when the cache needs to be active.<br>EG: 1 day = 86400 seconds.'),
      '#default_value' => $config->get('cache_ttl'),
    ];

    return parent::buildForm($form, $form_state);

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('views_json_source.settings')
      ->set('cache_ttl', $form_state->getValue('cache_ttl'))
      ->save();
  }

}
