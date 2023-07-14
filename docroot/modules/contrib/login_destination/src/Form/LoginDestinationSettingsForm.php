<?php

namespace Drupal\login_destination\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Provides form for managing module settings.
 */
class LoginDestinationSettingsForm extends ConfigFormBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'login_destination_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['login_destination.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('login_destination.settings');
    $form['settings']['preserve_destination'] = [
      '#type' => 'checkbox',
      '#default_value' => $config->get('preserve_destination'),
      '#title' => $this->t('Preserve the destination parameter'),
      '#description' => $this->t("The 'destination' GET parameter will have priority over the settings of this module. With this setting enabled, redirect from the user login block will not work."),
    ];
    $form['settings']['immediate_redirect'] = [
      '#type' => 'checkbox',
      '#default_value' => $config->get('immediate_redirect'),
      '#title' => $this->t('Redirect immediately after using one-time login link'),
      '#description' => $this->t('User will be redirected before given the possibility to change their password.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('login_destination.settings');
    $config->set('preserve_destination', $form_state->getValue('preserve_destination'));
    $config->set('immediate_redirect', $form_state->getValue('immediate_redirect'));
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
