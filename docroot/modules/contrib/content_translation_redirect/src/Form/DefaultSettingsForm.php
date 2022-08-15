<?php

namespace Drupal\content_translation_redirect\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;

/**
 * Content Translation Redirect default settings form.
 */
class DefaultSettingsForm extends ConfigFormBase {

  use ContentTranslationRedirectFormTrait;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'content_translation_redirect_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['content_translation_redirect.default'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Get default settings.
    $default_settings = $this->config('content_translation_redirect.default');
    $settings = [
      'code' => $default_settings->get('code'),
      'message' => $default_settings->get('message'),
    ];
    $form += $this->redirectSettingsForm($settings, TRUE);
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Save the configuration.
    $this->config('content_translation_redirect.default')
      ->set('code', $form_state->getValue('code'))
      ->set('message', $form_state->getValue('message'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
