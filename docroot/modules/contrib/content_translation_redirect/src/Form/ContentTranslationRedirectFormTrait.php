<?php

namespace Drupal\content_translation_redirect\Form;

/**
 * Provides code for redirect settings form.
 */
trait ContentTranslationRedirectFormTrait {

  /**
   * Form elements for redirect settings.
   *
   * @param array $settings
   *   The redirect settings.
   * @param bool $default
   *   Is default settings (TRUE) or bundle settings (FALSE).
   *
   * @return array
   *   The form structure.
   */
  protected function redirectSettingsForm(array $settings, $default = FALSE) {
    // Is default settings.
    if ($default) {
      $status_code_options = $this->getStatusCodeOptions();
      $message_description = $this->t('Leave blank to not display the message. You can use <i>%language</i> to display the name of the language.');
    }
    // Is bundle settings.
    else {
      $status_code_options = ['' => $this->t('- Use default -')] + $this->getStatusCodeOptions();
      $message_description = $this->t('Leave blank to use the default value. You can use <i>%language</i> to display the name of the language.');
    }

    // Redirect status code.
    $form['code'] = [
      '#type' => 'select',
      '#title' => $this->t('Redirect status'),
      '#options' => $status_code_options,
      '#default_value' => isset($settings['code']) ? $settings['code'] : '',
    ];
    // Message after redirection.
    $form['message'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Message after redirection'),
      '#description' => $message_description,
      '#default_value' => isset($settings['message']) ? $settings['message'] : '',
    ];
    return $form;
  }

  /**
   * Redirect status codes.
   *
   * @return array
   *   Redirect status codes.
   */
  protected function getStatusCodeOptions() {
    return [
      300 => $this->t('300 Multiple Choices'),
      301 => $this->t('301 Moved Permanently'),
      302 => $this->t('302 Found'),
      303 => $this->t('303 See Other'),
      304 => $this->t('304 Not Modified'),
      305 => $this->t('305 Use Proxy'),
      307 => $this->t('307 Temporary Redirect'),
    ];
  }

}
