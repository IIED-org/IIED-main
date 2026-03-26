<?php

namespace Drupal\session_management\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\session_management\Utilities;

/**
 * Login setting configuration form.
 */
class LoginSettingsForm extends ConfigFormBase
{

  public const SETTINGS = 'session_management.settings';

  /**
   * {@inheritDoc}
   */
  protected function getEditableConfigNames()
  {
    return [static::SETTINGS];
  }

  /**
   * {@inheritDoc}
   */
  public function getFormId()
  {
    return 'login-settings-form';
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
        ],
      ],
    ];

    $form['login_restriction'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => ('Login Restriction & Flood Control <a href="https://plugins.miniorange.com/configure-login-restriction-in-drupal-session-management" target="_blank">How to configure?</a>'),
    ];


    $form['login_restriction']['restriction'] = [
      '#type' => 'vertical_tabs',
    ];

    // IP-Based Restriction
    $form['ip_restriction'] = [
      '#type' => 'details',
      '#title' => ('IP-based restriction'),
      '#description' => $this->t('Control user access by allowing logins only from specific IP addresses or ranges. This helps ensure that only users from approved locations can access your site.'),
      '#group' => 'restriction',
      '#disabled' => false,
    ];

    $form['ip_restriction']['ip_login_restriction'] = [
      '#type' => 'checkbox',
      '#title' => ('Enable IP-based login restriction.'),
      '#default_value' => $config->get('ip_login_restriction') ?? false,
    ];

    $form['ip_restriction']['ip_range_list'] = [
      '#type' => 'textarea',
      '#title' => ("Allowed IP ranges"),
      '#description' => '<i>' . $this->t('List of allowed IP addresses or ranges (one per line). Example: 192.168.0.1 or 192.168.0.0/24') . '</i>',
      '#rows' => 2,
      '#default_value' => is_array($config->get('ip_range_list')) ? implode("\n", $config->get('ip_range_list')) : '',
    ];

    $form['ip_restriction']['ip_message'] = [
      '#type' => 'textarea',
      '#title' => ("Error message"),
      '#description' => $this->t('Error message shown when a user tries to log in from an unauthorized IP address.'),
      '#default_value' => !empty($config->get('ip_message')) ? $config->get('ip_message') : $this->t('Access denied. Your IP address is not authorized to access this site.'),
      '#rows' => 1,
    ];

    $form['flood_control'] = [
      '#type' => 'details',
      '#group' => 'restriction',
      '#title' => ('Flood control settings'),
      // '#description' => $this->t('Manage settings to prevent users from attempting too many login attempts in a short period of time.'),
    ];

    $form['flood_control']['enable_flood_control'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable flood control ' . $premium_tag),
      '#description' => $this->t('Enable automatic protection that temporarily blocks users or IPs that make too many requests in a short time (e.g. repeated failed logins).'),
      '#default_value' => 0, // Default to enabled.
      '#disabled' => false,
    ];

    $form['flood_control']['login_attempts_limit'] = [
      '#type' => 'textfield',
      '#title' => ('Login attempts limit'),
      '#description' => $this->t('Set the maximum number of login attempts allowed before triggering flood control.'),
      '#default_value' => '5',
      '#states' => [
        'visible' => [
          ':input[name="enable_flood_control"]' => ['checked' => TRUE],
        ],
      ],
      '#attributes' => [
        'placeholder' => 'e.g., 5',
      ],
      '#disabled' => true,
    ];

    $form['flood_control']['time_window'] = [
      '#type' => 'textfield',
      '#title' => ('Time window (minutes)'),
      '#description' => $this->t('Set the time window (in minutes) during which the login attempts limit is enforced.'),
      '#default_value' => '15',
      '#states' => [
        'visible' => [
          ':input[name="enable_flood_control"]' => ['checked' => TRUE],
        ],
      ],
      '#attributes' => [
        'placeholder' => 'e.g., 15',
      ],
      '#disabled' => true,
    ];

    $form['flood_control']['block_duration'] = [
      '#type' => 'textfield',
      '#title' => ('Block duration (minutes)'),
      '#description' => $this->t('Set the duration (in minutes) for which a user will be blocked from logging in after exceeding the login attempts limit.'),
      '#default_value' => '30',
      '#states' => [
        'visible' => [
          ':input[name="enable_flood_control"]' => ['checked' => TRUE],
        ],
      ],
      '#attributes' => [
        'placeholder' => 'e.g., 30',
      ],
      '#disabled' => true,
    ];

    $form['flood_control']['flood_message'] = [
      '#type' => 'textarea',
      '#title' => ('Custom error message'),
      '#description' => $this->t('Enter a custom error message to display when a user is blocked due to too many login attempts.'),
      '#default_value' => $this->t('Access denied due to too many failed login attempts. Please try again later.'),
      '#states' => [
        'visible' => [
          ':input[name="enable_flood_control"]' => ['checked' => TRUE],
        ],
      ],
      '#rows' => 1,
      '#disabled' => true,
    ];

    // Admin Bypass
    $form['flood_control']['admin_bypass'] = [
      '#type' => 'checkbox',
      '#title' => ('Admin bypass'),
      '#description' => $this->t('Allow users with administrative roles to bypass flood control.'),
      '#default_value' => 0,
      '#states' => [
        'visible' => [
          ':input[name="enable_flood_control"]' => ['checked' => TRUE],
        ],
      ],
      '#disabled' => true,
    ];

    // Notification
    $form['flood_control']['notify_admin'] = [
      '#type' => 'checkbox',
      '#title' => ('Notify admin on block'),
      '#description' => $this->t('Send a notification to the site administrator when a user is blocked.'),
      '#default_value' => 0,
      '#states' => [
        'visible' => [
          ':input[name="enable_flood_control"]' => ['checked' => TRUE],
        ],
      ],
      '#disabled' => true,
    ];

    // IP Whitelisting
    $form['flood_control']['whitelisted_ips'] = [
      '#type' => 'textarea',
      '#title' => ('Whitelisted IP addresses'),
      '#description' => $this->t('Enter IP addresses that are allowed to bypass flood control, one per line.'),
      '#default_value' => '',
      '#states' => [
        'visible' => [
          ':input[name="enable_flood_control"]' => ['checked' => TRUE],
        ],
      ],
      '#rows' => 3,
      '#attributes' => [
        'placeholder' => 'e.g., 192.168.0.1',
      ],
      '#disabled' => true,
    ];

    // Logging
    $form['flood_control']['enable_logging'] = [
      '#type' => 'checkbox',
      '#title' => ('Enable logging'),
      '#description' => $this->t('Log flood control events for review and auditing.'),
      '#default_value' => 0,
      '#states' => [
        'visible' => [
          ':input[name="enable_flood_control"]' => ['checked' => TRUE],
        ],
      ],
      '#disabled' => true,
    ];

    // Country-Based Restriction
    $form['country_restriction'] = [
      '#type' => 'details',
      '#title' => ('Country-based restriction'),
      '#group' => 'restriction',
      '#disabled' => true,
    ];

    $form['country_restriction']['country_login_restriction'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Country-based login restriction. ' . $premium_tag),
      '#default_value' => $config->get('country_login_restriction') ?? false,
      '#disabled' => true,
      '#description' => $this->t('Limit user logins based on their geographical location. Only users from selected countries will be able to log in, providing an extra layer of security.'),
    ];

    $form['country_restriction']['allowed_countries'] = [
      '#type' => 'select',
      '#title' => ('Select countries'),
      '#description' => $this->t('Select countries.'),
      '#options' => $this->getCountryOptions(),
      '#default_value' => $config->get('allowed_countries') ?? [],
      '#multiple' => TRUE,
      '#size' => 10,
      '#disabled' => true,
    ];

    // Default access behavior.
    $form['country_restriction']['default_behavior'] = [
      '#type' => 'radios',
      '#title' => ('Default access behavior'),
      '#description' => $this->t('Choose the default behavior for login access.'),
      '#options' => [
        'allow' => $this->t('Allow login from all countries except the selected ones.'),
        'restrict' => $this->t('Restrict login to only the selected countries.'),
      ],
      '#default_value' => $config->get('default_behavior') ?? 'allow',
      '#disabled' => true,
    ];

    $form['country_restriction']['country_message'] = [
      '#type' => 'textarea',
      '#title' => ("Error message"),
      '#description' => $this->t('Error message shown when a user tries to log in from a restricted country.'),
      '#default_value' => $this->t('Access denied. Logins from your country are not permitted.'),
      '#rows' => 1,
      '#disabled' => true,
    ];

    // Device-Based Restriction
    $form['device_restriction'] = [
      '#type' => 'details',
      '#title' => ('Device-based restriction'),
      '#group' => 'restriction',
      '#disabled' => true,
    ];

    $form['device_restriction']['device_login_restriction'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Device-based login restriction. ' . $premium_tag),
      '#default_value' => $config->get('device_login_restriction') ?? false,
      '#disabled' => true,
      '#description' => $this->t('Manage user access by restricting logins to approved devices. Users can only log in from devices that have been authorized, enhancing site security.'),
    ];

    $form['device_restriction']['device_list'] = [
      "#type" => "select",
      "#title" => ("Allowed devices"),
      "#description" => ("List of allowed devices (one per line). Example: iPhone, Windows PC"),
      "#options" => $this->getDeviceOptions(),
      "#default_value" => $config->get("device_list") ?? [],
      "#multiple" => TRUE,
      "#size" => 10,
    ];

    $form['device_restriction']['device_message'] = [
      '#type' => 'textarea',
      '#title' => ("Error message"),
      '#description' => $this->t('Error message shown when a user tries to log in from an unauthorized device.'),
      '#default_value' => $this->t('Access denied. Your device is not authorized to access this site.'),
      '#rows' => 1,
      '#disabled' => true,
    ];


    //Time based access control

    $form['time_based_access_control'] = [
      '#type' => 'details',
      '#group' => 'restriction',
      '#title' => ('Time-based access control ' . $premium_tag),
    ];

    $form['time_based_access_control']['absolute_access_control'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable absolute time-based access control ' . $premium_tag),
      '#description' => $this->t('When enabled, you can set a fixed start and end date/time so the resource is available only during that exact period.'),
      '#disabled' => true,
    ];

    $form['time_based_access_control']['absolute_access_control_start'] = [
      '#type' => 'datetime',
      '#title' => ('Start date/time'),
      '#states' => [
        'visible' => [
          ':input[name="absolute_access_control"]' => ['checked' => TRUE],
        ],
      ],
      '#disabled' => true
    ];

    $form['time_based_access_control']['absolute_access_control_end'] = [
      '#type' => 'datetime',
      '#title' => ('End date/time'),
      '#states' => [
        'visible' => [
          ':input[name="absolute_access_control"]' => ['checked' => TRUE],
        ],
      ],
      '#disabled' => true
    ];

    $form['time_based_access_control']['periodic_access_control'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable periodic time-based access control' . $premium_tag),
      '#description' => $this->t('Set access rules based on recurring schedules (e.g., daily, weekly).'),
      '#default_value' => 0,
      '#disabled' => false
    ];

    $form['time_based_access_control']['periodic_access_control_frequency'] = [
      '#type' => 'select',
      '#title' => ('Schedule frequency'),
      '#options' => [
        'daily' => $this->t('Daily'),
        'weekly' => $this->t('Weekly'),
        'monthly' => $this->t('Monthly'),
      ],
      '#states' => [
        'visible' => [
          ':input[name="periodic_access_control"]' => ['checked' => TRUE],
        ],
      ],
      '#disabled' => false
    ];

    $form['time_based_access_control']['periodic_access_control_time_range'] = [
      '#type' => 'fieldset',
      '#title' => ('Time range'),
      '#states' => [
        'visible' => [
          ':input[name="periodic_access_control"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['time_based_access_control']['periodic_access_control_time_range']['periodic_access_control_start'] = [
      '#type' => 'datetime',
      '#title' => ('Start date/time'),
      '#states' => [
        'visible' => [
          ':input[name="periodic_access_control"]' => ['checked' => TRUE],
        ],
      ],
      '#disabled' => true
    ];

    $form['time_based_access_control']['periodic_access_control_time_range']['periodic_access_control_end'] = [
      '#type' => 'datetime',
      '#title' => ('End date/time'),
      '#states' => [
        'visible' => [
          ':input[name="periodic_access_control"]' => ['checked' => TRUE],
        ],
      ],
      '#disabled' => true
    ];


    $form['time_based_access_control']['periodic_access_control_time_range']['start_time'] = [
      '#type' => 'time',
      '#title' => ('Start time'),
    ];

    $form['time_based_access_control']['periodic_access_control_time_range']['end_time'] = [
      '#type' => 'time',
      '#title' => ('End time'),
    ];

    $form['time_based_access_control']['periodic_access_control_days'] = [
      '#type' => 'checkboxes',
      '#title' => ('Days of the week'),
      '#options' => [
        'monday' => $this->t('Monday'),
        'tuesday' => $this->t('Tuesday'),
        'wednesday' => $this->t('Wednesday'),
        'thursday' => $this->t('Thursday'),
        'friday' => $this->t('Friday'),
        'saturday' => $this->t('Saturday'),
        'sunday' => $this->t('Sunday'),
      ],
      '#states' => [
        'visible' => [
          ':input[name="periodic_access_control_frequency"]' => ['value' => 'weekly'],
        ],
      ],
      '#disabled' => true
    ];

    $this->buildBruteForceUserForm($form, $form_state);
    $this->buildSessionRiskMonitoringForm($form, $form_state);
    $this->buildSessionPolicyEngineForm($form, $form_state);

    $form['login_restriction']['submit'] = [
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
    $config = $this->configFactory()->getEditable(self::SETTINGS);

    $config->set('ip_login_restriction', $form_values['ip_login_restriction'])
      ->set('ip_range_list', $this->linesToArray($form_values['ip_range_list']))
      ->set('ip_message', $form_values['ip_message'])
      ->save();

    $this->messenger()->addStatus($this->t("Configuration saved successfully."));
  }

  public function linesToArray(string $lines): array
  {
    $lines = trim($lines);
    $splitLines = [];

    if ($lines) {
      $splitLines = preg_split('/[\n\r]+/', $lines);
      if ($splitLines !== FALSE) {
        foreach ($splitLines as $i => $value) {
          $splitLines[$i] = trim($value);
        }
      }
    }

    return $splitLines;
  }
  /**
   * Helper function to get the list of country options.
   */
  protected function getCountryOptions()
  {
    return \Drupal::service('country_manager')->getList();
  }

  /**
   * Helper function to get the list of device options.
   */
  protected function getDeviceOptions()
  {
    return [
      "iPhone" => "iPhone",
      "Windows PC" => "Windows PC",
      "Android" => "Android",
      "Macbook" => "Macbook",
      "Linux" => "Linux",
      "Chromebook" => "Chromebook",
      "iPad" => "iPad",
      "Android Tablet" => "Android Tablet",
      "Windows Tablet" => "Windows Tablet",
    ];
  }

  protected function buildBruteForceUserForm(array &$form, FormStateInterface $form_state)
  {
    global $base_url;

    $premium_tag = Utilities::getPremiumBadge();

    $form['bruteforce_user'] = [
      '#type' => 'details',
      '#title' => $this->t('Brute force protection'),
      '#group' => 'restriction',
    ];

    $form['bruteforce_user']['description'] = [
      '#type' => 'item',
      '#markup' => $this->t('This feature protects your site from attacks by blocking user which tries to login with random usernames and passwords.'),
    ];

    $form['bruteforce_user']['session_management_user_enable_bruteforce'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable brute force protection ' . $premium_tag),
      '#description' => $this->t('Enable protection against brute force attacks by blocking users who attempt too many failed logins.'),
      '#disabled' => true
    ];

    $form['bruteforce_user']['session_management_user_track_time_value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Track time to check for security violations (hours)'),
      '#description' => $this->t('The time in hours for which the failed login attempts are monitored. After that time, the attempts are deleted and will never be considered again. Provide 0 if you do not want to enable the feature.'),
      '#default_value' => '',
      '#disabled' => true,
      '#attributes' => [
        'type' => 'number',
        'min' => 0,
      ],
    ];

    $form['bruteforce_user']['session_management_user_allowed_attempts_value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Number of login failures before blocking an User'),
      '#description' => $this->t('The number of failed login attempts by an User before it gets blocked. After that count, the user will be blocked from the site until it is unblocked by admin or after the time provided below.'),
      '#default_value' => '',
      '#disabled' => true,
      '#attributes' => [
        'type' => 'number',
        'min' => 1,
      ],
    ];

    $form['bruteforce_user']['session_management_user_blocked_time'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Time period for which User should be blocked (hours)'),
      '#description' => $this->t('The time in hours for which the user will remain in blocked state. After that time, the user will be unblocked. Provide 0 if you want to permanently block an user.'),
      '#default_value' => '',
      '#disabled' => true,
      '#attributes' => [
        'type' => 'number',
        'min' => 0,
      ],
    ];

    $form['bruteforce_user']['session_management_user_attack_allowed_attempts_value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Number of login failures before detecting an attack'),
      '#description' => $this->t('The number of failed login attempts through an IP that can be considered as an attack. After that count, the admin gets a notification email about the attack. Provide a number less than the allowed attempts or else provide 0 if you do not want to send alert mail.'),
      '#default_value' => '',
      '#disabled' => true,
      '#attributes' => [
        'type' => 'number',
        'min' => 0,
      ],
    ];

    $form['bruteforce_user']['session_management_show_remaining_value'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show remaining login attempts to user'),
      '#description' => $this->t('Display the number of remaining login attempts to users when they fail to log in.'),
      '#default_value' => 0,
      '#disabled' => true,
    ];
  }

  /**
   * Builds the session risk monitoring form section.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state interface.
   */
  protected function buildSessionRiskMonitoringForm(array &$form, FormStateInterface $form_state)
  {
    $premium_tag = Utilities::getPremiumBadge();

    $form['session_risk_monitoring'] = [
      '#type' => 'details',
      '#title' => $this->t('Session risk monitoring ' . $premium_tag),
      '#group' => 'restriction',
    ];

    $form['session_risk_monitoring']['description'] = [
      '#type' => 'item',
      '#markup' => $this->t('This feature monitors user sessions for geographically impossible login patterns. It detects when a user appears to be logged in from multiple locations that are physically impossible to reach within the time frame, indicating potential account compromise.'),
    ];

    $form['session_risk_monitoring']['enable_session_risk_monitoring'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable session risk monitoring ' . $premium_tag),
      '#description' => $this->t('Enable monitoring to detect and alert on simultaneous geographically impossible sessions.'),
      '#default_value' => 0,
      '#disabled' => true,
    ];

    $form['session_risk_monitoring']['maximum_travel_speed'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Maximum travel speed (km/h)'),
      '#description' => $this->t('Set the maximum realistic travel speed in kilometers per hour. Sessions from locations that would require exceeding this speed will be flagged.'),
      '#default_value' => '1000',
      '#attributes' => [
        'type' => 'number',
        'min' => 0,
        'placeholder' => 'e.g., 1000',
      ],
      '#disabled' => true,
    ];

    $form['session_risk_monitoring']['action_type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Action on detection'),
      '#description' => $this->t('Choose the action to take when a geographically impossible session is detected.'),
      '#options' => [
        'alert' => $this->t('Alert only - Notify administrators but allow the session.'),
        'restrict' => $this->t('Restrict - Block the suspicious session and require re-authentication.'),
      ],
      '#default_value' => 'alert',
      '#disabled' => true,
    ];

    $form['session_risk_monitoring']['notify_admin_on_detection'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Notify administrator on detection'),
      '#description' => $this->t('Send an email notification to administrators when a geographically impossible session is detected.'),
      '#default_value' => 1,
      '#disabled' => true,
    ];

    $form['session_risk_monitoring']['notify_user_on_detection'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Notify user on detection'),
      '#description' => $this->t('Send an email notification to the user when a suspicious session is detected on their account.'),
      '#default_value' => 1,
      '#disabled' => true,
    ];

    $form['session_risk_monitoring']['enable_logging'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable logging'),
      '#description' => $this->t('Log all session risk monitoring events for review and auditing purposes.'),
      '#default_value' => 1,
      '#disabled' => true,
    ];
  }

  /**
   * Builds the session policy engine form section.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state interface.
   */
  protected function buildSessionPolicyEngineForm(array &$form, FormStateInterface $form_state)
  {
    $premium_tag = Utilities::getPremiumBadge();

    $form['session_policy_engine'] = [
      '#type' => 'details',
      '#title' => $this->t('Session policy engine ' . $premium_tag),
      '#group' => 'restriction',
    ];

    $form['session_policy_engine']['description'] = [
      '#type' => 'item',
      '#markup' => $this->t('This feature allows you to define session rules per content type or route based on sensitivity. For example, financial pages can require fresh authentication, while public pages may have more relaxed session requirements.'),
    ];

    $form['session_policy_engine']['enable_session_policy_engine'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable session policy engine ' . $premium_tag),
      '#description' => $this->t('Enable session rules that can be applied per content type or route based on sensitivity levels.'),
      '#default_value' => 0,
      '#disabled' => true,
    ];

    $form['session_policy_engine']['policy_rules'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Policy rules'),
    ];

    $form['session_policy_engine']['policy_rules']['require_fresh_auth'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Require fresh authentication for sensitive content'),
      '#description' => $this->t('Force users to re-authenticate when accessing sensitive content, regardless of existing session validity.'),
      '#default_value' => 0,
      '#disabled' => true,
    ];

    $form['session_policy_engine']['policy_rules']['session_timeout_sensitive'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Session timeout for sensitive content (minutes)'),
      '#description' => $this->t('Set a shorter session timeout for sensitive content. Users will be required to re-authenticate after this period.'),
      '#default_value' => '15',
      '#attributes' => [
        'type' => 'number',
        'min' => 1,
        'placeholder' => 'e.g., 15',
      ],
      '#disabled' => true,
    ];

    $form['session_policy_engine']['policy_rules']['sensitive_routes'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Sensitive routes'),
      '#description' => '<i>' . $this->t('Enter routes or path patterns that require strict session policies, one per line. Example: /user/*/financial, /admin/commerce/orders') . '</i>',
      '#default_value' => '',
      '#rows' => 3,
      '#attributes' => [
        'placeholder' => 'e.g., /user/*/financial',
      ],
      '#disabled' => true,
    ];

    $form['session_policy_engine']['policy_rules']['sensitive_content_types'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Sensitive content types'),
      '#description' => $this->t('Select content types that should have strict session policies applied.'),
      '#options' => $this->getContentTypeOptions(),
      '#default_value' => [],
      '#disabled' => true,
    ];

    $form['session_policy_engine']['policy_rules']['require_2fa_for_sensitive'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Require fresh authentication for sensitive content'),
      '#description' => $this->t('Enforce fresh authentication when accessing sensitive content or routes.'),
      '#default_value' => 0,
      '#disabled' => true,
    ];

    $form['session_policy_engine']['policy_rules']['custom_error_message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Custom error message'),
      '#description' => $this->t('Enter a custom error message to display when a user is denied access due to session policy restrictions.'),
      '#default_value' => $this->t('Access denied. This content requires fresh authentication. Please log in again.'),
      '#rows' => 2,
      '#disabled' => true,
    ];

    $form['session_policy_engine']['enable_logging'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable logging'),
      '#description' => $this->t('Log all session policy engine events, including access denials and policy enforcement actions.'),
      '#default_value' => 1,
      '#disabled' => true,
    ];
  }

  /**
   * Helper function to get the list of content type options.
   *
   * @return array
   *   An array of content type machine names keyed by machine name.
   */
  protected function getContentTypeOptions()
  {
    $content_types = [];
    try {
      $entity_type_manager = \Drupal::entityTypeManager();
      $node_types = $entity_type_manager->getStorage('node_type')->loadMultiple();
      foreach ($node_types as $node_type) {
        $content_types[$node_type->id()] = $node_type->label();
      }
    } catch (\Exception $e) {
      // If content types cannot be loaded, return empty array.
    }
    return $content_types;
  }
}
