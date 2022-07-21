<?php

namespace Drupal\gin_lb\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SettingsForm.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'gin_lb.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'gin_lb_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('gin_lb.settings');
    $form['toastify_cdn'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Load Toastify From CDN'),
      '#description' => $this->t('If you uncheck this box, you will have load Toastify yourself in your theme.'),
      '#default_value' => $config->get('toastify_cdn'),
    ];
    $form['enable_preview_regions'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable "Regions" preview by default'),
      '#default_value' => $config->get('enable_preview_regions'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('gin_lb.settings')
      ->set('toastify_cdn', $form_state->getValue('toastify_cdn'))
      ->set('enable_preview_regions', $form_state->getValue('enable_preview_regions'))
      ->save();
  }

}
