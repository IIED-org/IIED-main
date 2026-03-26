<?php

namespace Drupal\session_management\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\session_management\Utilities;

/**
 * Provide form to configure the user session related functionality.
 */
class SessionSettingsForm extends FormBase
{


  public const SETTINGS = 'session_management.settings';

  /**
   * {@inheritdoc}
   */
  public function getFormId()
  {
    return 'session-settings-form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state)
  {
    $config = $this->config(self::SETTINGS);
    $premium_tag = Utilities::getPremiumBadge();

    $form['libraries'] = [
      '#attached' => [
        'library' => [
          "session_management/session_management.mo_session",
        ],
      ],
    ];

    $form['session_monitor'] = [
      '#type' => 'details',
      '#title' => $this->t('Session Monitor Configuration <a href="https://www.drupal.org/docs/extending-drupal/contributed-modules/contributed-module-documentation/user-session-management-and-monitoring-guide/drupal-session-monitor-configuration" target="_blank">How to configure?</a>'),
      '#open' => TRUE,
    ];

    $form['session_monitor']['enable_session_monitor'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display all sessions registered by the user'),
      '#description' => $this->t('<a href=":mo_sessions" target="_blank">View all sessions here</a>', [':mo_sessions' => Url::fromRoute('session_management.session_manage', ['user' => \Drupal::currentUser()->id()])->toString()]),
      '#default_value' => $config->get('enable_session_monitor'),
      '#prefix' => '<div class="mo-two-columns-container"><div class="child">',
    ];

    $form['session_monitor']['date_time_format'] = [
      '#type' => 'select',
      '#title' => $this->t('Select day-time format'),
      '#options' => $this->getDateTimeFormatOptions(),
      '#default_value' => $config->get('date_time_format'),
      '#description' => $this->t("<a href=':dateLink' target='_blank'>Date and time formats</a>", [':dateLink' => Url::fromRoute('entity.date_format.collection')->toString()]),
      '#suffix' => '</div>'
    ];

    $form['session_monitor']['session_deletion'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow session deletion ' . $premium_tag),
      '#description' => $this->t('Allows users to delete their current active sessions, enhancing security by allowing them to log out from potentially compromised devices.'),
      '#disabled' => TRUE,
      '#prefix' => '<div class="child">',
    ];

    $form['session_monitor']['session_history'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable session history ' . $premium_tag),
      '#description' => $this->t('Allows users to check their session history, allowing them to review their recent login activities and take action if any suspicious activities are detected.'),
      '#disabled' => TRUE,
      '#suffix' => '</div></div>',
    ];

    // USER SESSION LIMIT CONFIGURATION.
    $form['user_session_limit'] = [
      '#type' => 'details',
      '#title' => $this->t('User Session Limit <a href="https://www.drupal.org/docs/extending-drupal/contributed-modules/contributed-module-documentation/user-session-management-and-monitoring-guide/drupal-user-session-limit" target="_blank">How to configure?</a>'),
      '#tree' => TRUE,
      '#open' => TRUE,
    ];

    $form['user_session_limit']['enable_session_limiter'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable session limiting'),
      '#default_value' => $config->get('enable_session_limiter'),
      '#prefix' => '<div class="mo-two-columns-container"><div class="child">',
    ];

    $form['user_session_limit']['session_limit_count'] = [
      '#type' => 'number',
      '#min' => 1,
      '#title' => $this->t('Session limit count'),
      '#default_value' => $config->get('session_limit_count') ?? 1,
      '#description' => $this->t("Maximum number of allowed concurrent sessions."),
      '#required' => TRUE,
      '#attributes' => ['style' => ['width: 30%']],
      '#suffix' => '</div>',
    ];

    $form['user_session_limit']['session_restriction'] = [
      '#type' => 'radios',
      '#title' => $this->t('When session limit is reached ' . $premium_tag),
      '#description' => $this->t('Choose how to handle new login attempts when the session limit is reached.'),
      '#default_value' => 'delete_oldest',
      '#options' => [
        'select_session' => $this->t('Allow login and prompt the user to select which session they want to delete.'),
        'delete_oldest' => $this->t('Allow login and automatically delete the oldest active session.'),
        'no_login' => $this->t('Do not allow login.'),
      ],
      '#disabled' => TRUE,
      '#prefix' => '<div class="child">',
    ];

    $form['user_session_limit']['exclude_admin'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Exclude Admin from session limitation ' . $premium_tag),
      '#disabled' => TRUE,
      '#suffix' => '</div></div>',
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save configuration'),
      '#button_type' => 'primary',
    ];

    Utilities::addSupportButton($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state)
  {

    $form_values = $form_state->getValues();
    if ($form_values['enable_session_monitor'] !== $this->config(self::SETTINGS)->get('enable_session_monitor')) {
      \Drupal::service('router.builder')->rebuild();
    }

    $this->configFactory()->getEditable(self::SETTINGS)
      ->set('date_time_format', $form_values['date_time_format'])
      ->set('enable_session_monitor', $form_values['enable_session_monitor'])
      ->set('enable_session_limiter', $form_values['user_session_limit']['enable_session_limiter'])
      ->set('session_limit_count', $form_values['user_session_limit']['session_limit_count'])
      ->save();

    \Drupal::messenger()->addStatus('Configuration Saved Successfully.');
  }

  /**
   * List all the Date -Time format option.
   */
  public function getDateTimeFormatOptions(): array
  {

    $dateTimeFormatOptions = [];

    $dateTimeFormat = \Drupal::service('entity_type.manager')->getStorage('date_format');
    foreach ($dateTimeFormat->loadMultiple() as $format_id => $format) {
      $pattern = $format->getPattern();
      $dateTimeFormatOptions[$pattern] = $format->label() . " " . $pattern . "   Eg: " . date($pattern);
    }

    $dateTimeFormatOptions['time_passed'] = $this->t('Eg. x-time ago');

    return $dateTimeFormatOptions;
  }
}
