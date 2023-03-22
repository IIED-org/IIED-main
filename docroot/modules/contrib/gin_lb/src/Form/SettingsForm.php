<?php

namespace Drupal\gin_lb\Form;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * GinLb Configuration form.
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

    $form['toastify_loading'] = [
      '#type' => 'select',
      '#title' => $this->t('Load Toastify'),
      '#description' => $this->t('Define how to load toasity.'),
      '#options' => [
        'cdn' => 'CDN',
        'composer' => 'Composer',
        'custom' => 'custom',
      ],
      '#default_value' => $config->get('toastify_loading'),
    ];

    $form['enable_preview_regions'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable "Regions" preview by default'),
      '#default_value' => $config->get('enable_preview_regions'),
    ];

    $form['hide_discard_revert_button'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Hide "Discard changes" and "Revert to defaults" button'),
      '#description' => $this->t('Layout builder provides buttons to revert to defaults and a discard changes -
      check this option if you want to hide these.'),
      '#default_value' => $config->get('hide_discard_revert_button'),
    ];

    $form['save_behavior'] = [
      '#type' => 'select',
      '#title' => $this->t('After save behavior'),
      '#options' => [
        'stay' => $this->t('Stay on edit page'),
        'default' => $this->t('Layout builder default behavior.'),
      ],
      '#default_value' => $config->get('save_behavior'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $this->config('gin_lb.settings')
      ->set('toastify_loading', $form_state->getValue('toastify_loading'))
      ->set('enable_preview_regions', $form_state->getValue('enable_preview_regions'))
      ->set('save_behavior', $form_state->getValue('save_behavior'))
      ->save();
    Cache::invalidateTags($this->config('gin_lb.settings')->getCacheTags());
  }

}
