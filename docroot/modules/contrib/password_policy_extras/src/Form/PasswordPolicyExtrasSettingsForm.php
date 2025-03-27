<?php

namespace Drupal\password_policy_extras\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class PasswordPolicyExtrasSettingsForm.
 *
 * The config form for the password_policy_extras module.
 *
 * @package Drupal\password_policy_extras\Form
 */
class PasswordPolicyExtrasSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'password_policy_extras_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['password_policy_extras.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('password_policy_extras.settings');

    $form['disable_ajax_progress'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable Ajax throbber'),
      '#default_value' => $config->get('disable_ajax_progress'),
    ];

    $form['failed_messages_only'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display failed messages only'),
      '#default_value' => $config->get('failed_messages_only'),
    ];

    $form['hide_password_suggestions'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Hide default Drupal password suggestions'),
      '#default_value' => $config->get('hide_password_suggestions'),
    ];

    $form['display_status_after_pass'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display status below main password field'),
      '#default_value' => $config->get('display_status_after_pass'),
    ];

    $form['display_status_on_focus'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display status on focus'),
      '#default_value' => $config->get('display_status_on_focus'),
    ];

    $form['status_refresh_delay'] = [
      '#type' => 'number',
      '#title' => $this->t('Status refresh delay'),
      '#description' => $this->t('Delay in milliseconds to wait before refreshing the status while typing.  Set to 0 to disable this feature.'),
      '#default_value' => $config->get('status_refresh_delay') ?? 500,
      '#min' => 0,
      '#step' => 100,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('password_policy_extras.settings');

    $config
      ->set('disable_ajax_progress', $form_state->getValue('disable_ajax_progress'))
      ->set('failed_messages_only', $form_state->getValue('failed_messages_only'))
      ->set('hide_password_suggestions', $form_state->getValue('hide_password_suggestions'))
      ->set('display_status_after_pass', $form_state->getValue('display_status_after_pass'))
      ->set('display_status_on_focus', $form_state->getValue('display_status_on_focus'))
      ->set('status_refresh_delay', $form_state->getValue('status_refresh_delay'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
