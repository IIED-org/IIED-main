<?php

namespace Drupal\session_management\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\session_management\Utilities;

/**
 * Configuration class for saving auto logout feature configuration.
 */
class AutoLogoutSettingsForm extends ConfigFormBase
{

  public const SETTINGS = 'session_management.settings';

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames()
  {
    return [static::SETTINGS];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId()
  {
    return 'autologout-settings-form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state)
  {

    $config = $this->config(static::SETTINGS);
    $premium_tag = Utilities::getPremiumBadge();

    $form['libraries'] = [
      '#attached' => [
        'library' => [
          "session_management/session_management.mo_session",
          "core/drupal.dialog.ajax",
        ],
      ],
    ];

    $form['#disabled'] = FALSE;



    $form['auto_logout_detail'] = [
      '#type' => 'details',
      '#title' => $this->t('Auto Logout Settings <a href="https://www.drupal.org/docs/extending-drupal/contributed-modules/contributed-module-documentation/user-session-management-and-monitoring-guide/how-to-enable-auto-logout-in-drupal" target="_blank">How to configure?</a>'),
      '#open' => TRUE,
    ];

    $form['auto_logout_detail']['autologout_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable automatic logout'),
      '#description' => $this->t('Turn this on to automatically sign users out after a period of inactivity.'),
      '#default_value' => $config->get('autologout_enabled'),
      '#prefix' => '<div class="mo-two-columns-container"><div class="child">',
    ];

    $form['auto_logout_detail']['autologout_timeout'] = [
      '#type' => 'number',
      '#title' => $this->t('Logout timeout (in seconds)'),
      '#description' => $this->t('The duration of user inactivity (in seconds), that triggers the display of a logout notification dialog.'),
      '#min' => '50',
      '#default_value' => $config->get('autologout_timeout') ?? 50,
    ];

    $form['auto_logout_detail']['autologout_response_time'] = [
      '#type' => 'number',
      '#title' => $this->t('Response time (in seconds)'),
      '#description' => $this->t('The time allowed for user response to the logout dialog box.'),
      '#min' => '5',
      '#default_value' => $config->get('autologout_response_time') ?? 5,
      '#suffix' => '</div>',
    ];

    $form['auto_logout_detail']['exclude_admin_autologout'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Exempt administrators from automatic logout ' . $premium_tag),
      '#description' => $this->t('Users with administrative roles will not be automatically signed out.'),
      '#disabled' => true,
      '#prefix' => '<div class="child">',
    ];

    $form['auto_logout_detail']['redirect_after_logout'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Redirect after auto-logout ' . $premium_tag),
      '#disabled' => TRUE,
      '#attributes' => [
        'placeholder' => $this->t('eg; https://example.com/node1')
      ],
      '#suffix' => '</div></div>',
    ];

    $form['auto_logout_detail']['modal_info']['modal_link'] = [
      '#type' => 'link',
      '#title' => $this->t('Configure modal dialog settings'),
      '#url' => Url::fromRoute('session_management.modal_info'),
      '#attributes' => [
        'class' => ['use-ajax'],
        'data-dialog-type' => 'modal',
        'data-dialog-options' => '{"width":"600px"}',
      ],
      '#prefix' => '<div class="modal-info-link">',
      '#suffix' => '</div>',
    ];

    $form['auto_logout_detail']['rb_logout_detail']['role_based_logout'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable role-based logout ' . $premium_tag),
      '#disabled' => TRUE,
    ];

    $rows = [
      [
        'roles' => [
          'data' => [
            '#type' => 'select',
            '#options' => $this->getRolesList(),
          ],
        ],
        'logout_time' => [
          'data' => [
            '#type' => 'number',
            '#min' => '5',
            '#default_value' => 50,
          ],
        ],
      ],
    ];

    $form['auto_logout_detail']['rb_logout_detail']['role_based_logout_table'] = [
      '#type' => 'table',
      '#header' => [
        'roles' => $this->t('Select role'),
        'logout_time' => $this->t('Logout time'),
      ],
      '#rows' => $rows,
      '#disabled' => TRUE
    ];

    $form['auto_logout_detail']['rb_logout_detail']['add_rows'] = [
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#value' => $this->t('Add row +'),
      '#disabled' => TRUE
    ];

    $form['force_logout_detail'] = [
      '#type' => 'fieldset',
      '#open' => TRUE,
    ];

    $form['force_logout_detail']['force_logout'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Force logout even if activity is detected ' . $premium_tag),
      '#description' => $this->t('If enabled, the user will be signed out when the timeout is reached even if they appear active (overrides activity checks). Use with caution — it can interrupt users who are working.'),
      '#default_value' => false,
    ];

    $form['force_logout_detail']['fl_fieldset'] = [
      '#type' => 'fieldset',
      '#states' => [
        'visible' => [
          ':input[name = "force_logout"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['force_logout_detail']['fl_fieldset']['force_logout_time'] = [
      '#type' => 'number',
      '#title' => $this->t('Force user logout time ' . $premium_tag),
      '#description' => $this->t('Number of seconds of no activity before showing the logout warning dialog.'),
      '#min' => '30',
      '#disabled' => true,
    ];

    $form['force_logout_detail']['fl_fieldset']['show_logout_timer'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display user logout timer ' . $premium_tag),
      '#description' => $this->t("Display a user logout clock or countdown timer (like a stopwatch) for the logged-in user's session"),
      '#disabled' => true,
    ];

    $form['browser_session_destroy_detail'] = [
      '#type' => 'fieldset',
      '#open' => TRUE,
    ];

    $form['browser_session_destroy_detail']['browser_session_destroy'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Terminate session on browser close ' . $premium_tag),
      '#description' => $this->t('Immediately end the account’s active session when the user closes their browser. This forces sign-in again when they reopen the site.'),
      '#disabled' => TRUE,
      '#prefix' => '<div class="mo-two-columns-container "><div class="child">',
      '#suffix' => '</div>',
    ];

    $form['browser_session_destroy_detail']['logout_password_change'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Logout users on password change ' . $premium_tag),
      '#description' => $this->t('Immediately terminate every active session for the user when their password is changed. This prevents anyone with an old session from staying logged in after a password reset.'),
      '#disabled' => TRUE,
      '#prefix' => '<div class="child">',
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
  public function getRolesList(): array
  {
    $roles = \Drupal::entityTypeManager()->getStorage('user_role')->loadMultiple();
    $option = [];

    foreach ($roles as $role) {
      $option[$role->getOriginalId()] = $role->label();
    }
    unset($option['anonymous'], $option['authenticated']);

    return $option;
  }

  /**
   * Get current modal settings display.
   */
  protected function getCurrentModalSettings($config)
  {
    $width = $config->get('mo_modal_width') ?? 400;
    $title = $config->get('mo_modal_title') ?? \Drupal::config('system.site')->get('name') . ' Alert';
    $message = $config->get('mo_modal_message') ?? "You've been inactive for a while. Would you like to continue your session?";
    $yes_text = $config->get('mo_modal_yes_button_text') ?? "Accept";
    $no_text = $config->get('mo_modal_no_button_text') ?? "Deny";

    $output = '<div class="current-modal-settings">';
    $output .= '<h4>' . $this->t('Current Settings:') . '</h4>';
    $output .= '<ul>';
    $output .= '<li><strong>' . $this->t('Width:') . '</strong> ' . $width . 'px</li>';
    $output .= '<li><strong>' . $this->t('Title:') . '</strong> ' . $title . '</li>';
    $output .= '<li><strong>' . $this->t('Message:') . '</strong> ' . substr($message, 0, 50) . '...</li>';
    $output .= '<li><strong>' . $this->t('Yes Button:') . '</strong> ' . $yes_text . '</li>';
    $output .= '<li><strong>' . $this->t('No Button:') . '</strong> ' . $no_text . '</li>';
    $output .= '</ul>';
    $output .= '</div>';

    return $output;
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
    $config = $this->configFactory()->getEditable(self::SETTINGS);

    $fields = [
      'autologout_enabled',
      'autologout_timeout',
      'autologout_response_time',
      'redirect_after_logout',
    ];
    foreach ($fields as $field) {
      $config->set($field, $form_values[$field]);
    }
    $config->save();

    $this->messenger()->addStatus($this->t("Configuration saved successfully."));
  }
}
