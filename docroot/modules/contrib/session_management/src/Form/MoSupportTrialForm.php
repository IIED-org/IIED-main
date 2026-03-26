<?php

namespace Drupal\session_management\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\session_management\ContactAPI\MiniorangeContact;

/**
 * Unified modal form for Support contact and Trial request.
 */
class MoSupportTrialForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'session_management_mo_support_trial_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {

    $form['#prefix'] = '<div id="modal_support_form">';
    $form['#suffix'] = '</div>';

    $form['status_messages'] = [
      '#type' => 'status_messages',
      '#weight' => -10,
    ];

    $form['#attached']['library'][] = 'session_management/session_management.mo_session';
    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';

    $route_name = \Drupal::routeMatch()->getRouteName();
    $mode = $route_name === 'session_management.request_trial' ? 'trial' : 'support';

    $user_email = \Drupal::currentUser()->getEmail();

    $form['mode'] = [
      '#type' => 'hidden',
      '#value' => $mode,
    ];

    if ($mode === 'support') {
      $form['intro'] = [
        '#type' => 'markup',
        '#markup' => $this->t('<p>Need any help? We can help you with configuring <strong>User Session Management</strong> on your site. Just send us a query, and we will get back to you soon.</p>'),
      ];

      $form['support_email'] = [
        '#type' => 'email',
        '#title' => $this->t('Email'),
        '#default_value' => $user_email,
        '#required' => TRUE,
        '#attributes' => [
          'placeholder' => $this->t('Enter your email'),
          'style' => 'width:99%;margin-bottom:1%;',
        ],
      ];

      $form['support_topic'] = [
        '#type' => 'select',
        '#title' => $this->t('What are you looking for'),
        '#options' => [
          'I need Technical Support' => $this->t('I need Technical Support'),
          'I want to Schedule a Setup Call/Demo' => $this->t('I want to Schedule a Setup Call/Demo'),
          'I have Sales enquiry' => $this->t('I have Sales enquiry'),
          'I have a custom requirement' => $this->t('I have a custom requirement'),
          'My reason is not listed here' => $this->t('My reason is not listed here'),
        ],
      ];

      $form['support_query'] = [
        '#type' => 'textarea',
        '#required' => TRUE,
        '#title' => $this->t('Query'),
        '#attributes' => [
          'placeholder' => $this->t('Describe your query here!'),
          'style' => 'width:99%',
          'data-maxlength' => 255,
        ],
        '#rows' => 3,
        '#maxlength' => 255,
      ];
    }
    else {
      $form['trial_email'] = [
        '#type' => 'email',
        '#title' => $this->t('Email'),
        '#default_value' => $user_email,
        '#required' => TRUE,
        '#attributes' => [
          'placeholder' => $this->t('Enter your email'),
          'style' => 'width:99%;margin-bottom:1%;'
        ],
      ];

      $form['trial_phone'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Phone number'),
        '#required' => FALSE,
        '#attributes' => [
          'placeholder' => $this->t('Enter your phone number'),
          'pattern' => '^[\+]?[0-9\s\-\(\)]{7,15}$',
          'maxlength' => 15,
          'title' => $this->t('Enter a valid phone number (7-15 digits, may include +, spaces, hyphens, parentheses)'),
        ],
      ];

      $form['trial_query'] = [
        '#type' => 'textarea',
        '#required' => TRUE,
        '#title' => $this->t('Specify your use case'),
        '#attributes' => [
          'placeholder' => $this->t('Describe your requirement or use case here'),
          'style' => 'width:99%',
          'data-maxlength' => 255,
        ],
        '#rows' => 2,
        '#maxlength' => 255,
      ];

      $form['trial_features'] = [
        '#type' => 'checkboxes',
        '#title' => $this->t('Features you are interested in'),
        '#options' => [
          'admin_session_reports' => $this->t('Detailed user session reports for admins'),
          'location_country_ip_login_control' => $this->t('Location, Country, IP-based login control'),
          'time_based_login_control' => $this->t('Time-based login control'),
          'custom_session_time_limits' => $this->t('Custom time limits for user sessions'),
          'force_logout_after_specified_time' => $this->t('Force logout after a specified time'),
          'auto_logout_after_inactivity' => $this->t('Automatic user logout after inactivity'),
          'redirect_after_auto_logout' => $this->t('Redirect users after auto-logout'),
          'end_users_delete_other_device_sessions' => $this->t('Option for end users to delete their other device sessions'),
          'session_history_for_end_users' => $this->t('Session history accessible to end users'),
          'limit_active_sessions_per_user' => $this->t('Limit the number of active sessions per user'),
          'inspect_analyze_user_sessions' => $this->t('Inspect and analyze user sessions'),
          'single_click_logout_all_devices' => $this->t('Single-click logout from all devices'),
          'logout_on_password_change' => $this->t('Logout users on password change'),
        ],
        '#description' => $this->t('Select one or more features.'),
      ];
    }

    $form['actions'] = ['#type' => 'actions'];

    $form['actions']['send'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#attributes' => [
        'class' => [
          'use-ajax',
          'button--primary',
        ],
      ],
      '#ajax' => [
        'callback' => [$this, 'submitModalFormAjax'],
        'event' => 'click',
      ],
    ];

    return $form;
  }

  /**
   * AJAX submit handler to process the support/trial request in modal.
   */
  public function submitModalFormAjax(array $form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    if ($form_state->hasAnyErrors()) {
      $response->addCommand(new ReplaceCommand('#modal_support_form', $form));
      return $response;
    }

    $values = $form_state->getValues();
    $mode = $values['mode'] ?? 'support';

    if ($mode === 'support') {
      $email = $values['support_email'];
      $topic = $values['support_topic'] ?? '';
      $message = trim($values['support_query']);

      $modules_info = \Drupal::service('extension.list.module')->getExtensionInfo('session_management');
      $module_version = $modules_info['version'] ?? '';
      $subject = 'Support request for Drupal-' . \Drupal::VERSION . ' User Session Management Module' . ($module_version !== '' ? ' | ' . $module_version : '') . ' | ' . phpversion() . ' - ' . $email;
      $content = '<div>Hello,<br><br>'
        . 'Company :<a href="' . $_SERVER['SERVER_NAME'] . '" target="_blank">' . $_SERVER['SERVER_NAME'] . '</a><br><br>'
        . 'Email:<a href="mailto:' . $email . '" target="_blank">' . $email . '</a><br><br>'
        . '<strong>Support Topic: </strong>' . $topic . '<br><br>'
        . 'Query: ' . nl2br(htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'))
        . '</div>';

      $contact = new MiniorangeContact($email);
      $result = $contact->notify($subject, $content);

      if (!$result || !isset($result['status']) || $result['status'] !== 'SUCCESS') {
        $this->messenger()->addError($this->t('Error while sending query.<br>Please mail us at <a href=":mail?subject=Drupal User Session Management - Need assistance"><i>:mail</i></a>, and we will get back to you as soon as we can.', [
          ':mail' => 'drupalsupport@xecurify.com',
        ]));
      }
      else {
        $this->messenger()->addStatus($this->t('Support query successfully sent. We will get back to you shortly on your provided mail <i>@email</i>', [
          '@email' => $email,
        ]));
      }
    }
    else {
      $email = $values['trial_email'];
      $phone = $values['trial_phone'] ?? '';
      $selected_features_values = array_filter($values['trial_features'] ?? []);
      $selected_feature_labels = [];
      if (!empty($selected_features_values) && isset($form['trial_features']['#options']) && is_array($form['trial_features']['#options'])) {
        foreach (array_keys($selected_features_values) as $feature_key) {
          if (isset($form['trial_features']['#options'][$feature_key])) {
            $selected_feature_labels[] = $form['trial_features']['#options'][$feature_key];
          }
        }
      }

      $use_case = trim($values['trial_query']);

      $features_html = '';
      if (!empty($selected_feature_labels)) {
        $features_html = '<ul style="margin:0; padding-left:18px;">';
        foreach ($selected_feature_labels as $label) {
          $features_html .= '<li>' . htmlspecialchars($label, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</li>';
        }
        $features_html .= '</ul>';
      }

      $modules_info = \Drupal::service('extension.list.module')->getExtensionInfo('session_management');
      $module_version = $modules_info['version'] ?? '';
      $subject = 'Trial request for Drupal-' . \Drupal::VERSION . ' User Session Management Module' . ($module_version !== '' ? ' | ' . $module_version : '') . ' | ' . phpversion() . ' - ' . $email;
      $features_text = !empty($selected_feature_labels) ? implode(', ', $selected_feature_labels) : '';
      $content = '<div>Hello,<br><br>'
        . 'Company :<a href="' . $_SERVER['SERVER_NAME'] . '" target="_blank">' . $_SERVER['SERVER_NAME'] . '</a><br><br>'
        . (!empty($phone) ? ('Phone Number: ' . htmlspecialchars($phone, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '<br><br>') : '')
        . 'Email:<a href="mailto:' . $email . '" target="_blank">' . $email . '</a><br><br>'
        . (!empty($features_text) ? ('Interested Features: ' . htmlspecialchars($features_text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '<br><br>') : '')
        . 'Trial request details: ' . nl2br(htmlspecialchars($use_case, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'))
        . '</div>';

      $contact = new MiniorangeContact($email);
      $result = $contact->notify($subject, $content);

      if (!$result || !isset($result['status']) || $result['status'] !== 'SUCCESS') {
        $this->messenger()->addError($this->t('Error while sending trial request.<br>Please mail us at <a href=":mail?subject=Drupal User Session Management - Trial request"><i>:mail</i></a>, and we will get back to you as soon as we can.', [
          ':mail' => 'drupalsupport@xecurify.com',
        ]));
      }
      else {
        $this->messenger()->addStatus($this->t('Trial request sent. We will get back to you shortly on <i>@email</i>.', [
          '@email' => $email,
        ]));
      }
    }

    $redirect = $_SERVER['HTTP_REFERER'] ?? Url::fromRoute('session_management.config_form')->toString();
    $response->addCommand(new RedirectCommand($redirect));
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    $values = $form_state->getValues();
    $mode = $values['mode'] ?? 'support';

    if ($mode === 'trial') {
      // Validate phone number if provided
      $phone = trim($values['trial_phone'] ?? '');
      if (!empty($phone)) {
        // Remove all non-digit characters except + at the beginning
        $clean_phone = preg_replace('/[^\d+]/', '', $phone);
        if (!preg_match('/^\+?[0-9]{7,15}$/', $clean_phone)) {
          $form_state->setErrorByName('trial_phone', $this->t('Please enter a valid phone number (7-15 digits, optionally starting with +).'));
        }
      }

      // Validate trial query length
      $query = trim($values['trial_query'] ?? '');
      if (strlen($query) > 255) {
        $form_state->setErrorByName('trial_query', $this->t('Your use case description cannot exceed 255 characters. Current length: @length characters.', [
          '@length' => strlen($query),
        ]));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {}

}
