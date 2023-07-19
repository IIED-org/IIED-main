<?php

namespace Drupal\message_notify_ui\Plugin\MessageNotifyUiSenderSettingsForm;

use Drupal\Core\Form\FormStateInterface;
use Drupal\message_notify\MessageNotifier;
use Drupal\message_notify_ui\MessageNotifyUiSenderSettingsFormBase;
use Drupal\message_notify_ui\MessageNotifyUiSenderSettingsFormInterface;

/**
 * Message notify plugin form for email.
 *
 * @MessageNotifyUiSenderSettingsForm(
 *  id = "message_notify_ui_sender_settings_form",
 *  label = @Translation("The plugin ID."),
 *  notify_plugin = "email"
 * )
 */
class MessageNotifyUiSenderMailSettingsForm extends MessageNotifyUiSenderSettingsFormBase implements MessageNotifyUiSenderSettingsFormInterface {

  /**
   * {@inheritdoc}
   */
  public function form() {
    return [
      'use_custom' => [
        '#type' => 'checkbox',
        '#title' => t('Use custom email'),
        '#description' => t('Use the message owner message'),
      ],
      'email' => [
        '#type' => 'email',
        '#title' => t('Email address'),
        '#description' => t('The email address'),
        '#states' => [
          'visible' => [
            ':input[name="use_custom"]' => ['checked' => TRUE],
          ],
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function validate(array $form, FormStateInterface $formState) {
    if ($formState->getValue('use_custom') && !$formState->getValue('email')) {
      $formState->setErrorByName('email', t('The email field cannot be empty.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submit(MessageNotifier $notifier, FormStateInterface $formState) {
    $settings = [];

    if ($formState->getValue('use_custom')) {
      $settings['mail'] = $formState->getValue('email');
    }

    if ($formState->getValue('language')) {
      $settings['language override'] = $formState->getValue($formState->getValue('language'));
    }

    if ($notifier->send($this->getMessage(), $settings, 'email')) {
      \Drupal::messenger()->addMessage(t('The email sent successfully.'));
    }
  }

}
