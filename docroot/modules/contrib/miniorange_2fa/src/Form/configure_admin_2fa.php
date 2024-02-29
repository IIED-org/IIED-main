<?php

namespace Drupal\miniorange_2fa\Form;

use Drupal\Core\Url;
use Drupal\user\Entity\User;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Ajax\OpenDialogCommand;
use Drupal\Core\Form\FormStateInterface;
use Drupal\miniorange_2fa\MiniorangeUser;
use Drupal\miniorange_2fa\UsersAPIHandler;
use Drupal\miniorange_2fa\MoAuthUtilities;
use Drupal\miniorange_2fa\MoAuthConstants;
use Drupal\miniorange_2fa\AuthenticationType;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\miniorange_2fa\MiniorangeCustomerSetup;
use Drupal\miniorange_2fa\AuthenticationAPIHandler;
use Drupal\miniorange_2fa\MiniorangeCustomerProfile;

/**
 * @file
 *  This is used to configure 2fa of the admin
 */
class configure_admin_2fa extends FormBase
{

    private MoAuthUtilities $utilities;
    private $user;
    private $user_id;
    private $custom_attribute;
    private $authMethod;
    private $user_email;
    private MiniorangeCustomerProfile $customer;
    private AuthenticationAPIHandler $auth_api_handler;

    public function __construct()
    {
        $this->utilities = new MoAuthUtilities();
        $this->user = User::load(\Drupal::currentUser()->id());
        $this->user_id = $this->user->id();
        $this->custom_attribute = $this->utilities::get_users_custom_attribute($this->user_id);
        $this->user_email = $this->custom_attribute[0]->miniorange_registered_email;
        $this->authMethod = $_GET['authMethod'];
        $this->customer = new MiniorangeCustomerProfile();
        $this->auth_api_handler = new AuthenticationAPIHandler($this->customer->getCustomerID(), $this->customer->getAPIKey());
    }

    public function getFormId()
    {
        return 'mo_auth_configure_admin_2fa';
    }

    public function buildForm(array $form, FormStateInterface $form_state) {
        $form['#attached']['library'] = [
            'miniorange_2fa/miniorange_2fa.admin',
            'core/drupal.ajax',
            'miniorange_2fa/miniorange_2fa.country_flag_dropdown',
            'miniorange_2fa/miniorange_2fa.custom_kba_validation',
        ];

        $customer_id     = $this->customer->getCustomerID();
        $user_email      = $this->user_email;
        $miniorange_user = new MiniorangeUser($customer_id, $user_email, NULL, NULL, NULL);

        /** Switch statement to render form according to 2FA method **/
        switch ($this->authMethod) {
            case AuthenticationType::$HARDWARE_TOKEN['code']:
                self::render_hardware_token_2fa_configure($form, $form_state, $this->authMethod);
                break;

            case AuthenticationType::$KBA['code']:
                unset($form['#attached']['library']['miniorange_2fa/miniorange_2fa.license']);
                self::render_kba_2fa_configure($form);
                break;

            case AuthenticationType::$SMS_AND_EMAIL['code']:
            case AuthenticationType::$OTP_OVER_PHONE['code']:
            case AuthenticationType::$SMS['code']:
                self::render_sms_based_2fa_configure($form, $form_state, $this->authMethod);
                break;

            case AuthenticationType::$QR_CODE['code']:
            case AuthenticationType::$SOFT_TOKEN['code']:
            case AuthenticationType::$PUSH_NOTIFICATIONS['code']:
                $form['#attached']['library'][] = 'miniorange_2fa/miniorange_2fa.license';
                $response = $this->auth_api_handler->register($miniorange_user, AuthenticationType::$QR_CODE['code'], NULL, NULL, NULL);
                if (isset($response->status) && $response->status == 'IN_PROGRESS') {
                    self::render_qr_based_2fa_configure($form, $form_state, $this->authMethod);
                }
                else {
                    $this->utilities->mo_add_loggers_for_failures($response->message, 'error');
                    $this->utilities->redirectUserToSetupTwoFactor('An error occurred while processing your request. Please try again after sometime.');
                }
                break;

            default:
                $response = $this->auth_api_handler->getGoogleAuthSecret($miniorange_user);
                if (isset($response->status) && $response->status == 'SUCCESS') {
                    self::render_totp_based_2fa_configure($form, $form_state, $this->authMethod, $response);
                }
                else {
                    $this->utilities->mo_add_loggers_for_failures($response->message, 'error');
                    $this->utilities->redirectUserToSetupTwoFactor('An error occurred while processing your request. Please try again after sometime.');
                }
                break;
        }

        return $form;
    }

    public function render_totp_based_2fa_configure(array &$form, &$form_state, $authMethod, $response)
    {
        $input       = $form_state->getUserInput();
        $totp_method = AuthenticationType::getAuthType($authMethod);

        if (array_key_exists('secret', $input) === false) {
            $qrCode = $response->qrCodeData ?? '';
            $secret = $response->secret ?? '';
        } else {
            $secret = $input['secret'];
            $qrCode = $input['qrCode'];
        }

        $App_Name             = strtok($authMethod, " ");
        $variables_and_values = ['mo_auth_google_auth_app_name'];
        $mo_db_values         = $this->utilities->miniOrange_set_get_configurations($variables_and_values, 'GET');
        $ajax_wrapper_id      = 'modal_support_form';

        /** Main form to configure TOTP methods**/
        $form['#title']  = $this->t('Configure @method_name', ['@method_name' => $totp_method['name']]);
        $form['#prefix'] = '<div id="' . $ajax_wrapper_id . '">';
        $form['#suffix'] = '</div>';

        $form['mo_status_messages'] = array(
            '#type'     => 'status_messages',
            '#weight'   => -10,
        );

        $form['step_one'] = array(
            '#markup' => '
        <h5>'. $this->t('Step 1 : Download the @method_name app', ['@method_name' => $totp_method['name']]).'</h5>
        <ul>
            <li class="two-step-verification--app-item--ios">iPhone, iPod Touch, or iPad: <a target="_blank" href="'.$totp_method['ios-link'].'">'.$this->t('Download').'</a></li>
            <li class="two-step-verification--app-item--android ">Android devices: <a target="_blank" href="'.$totp_method['android-link'].'">'.$this->t('Download').'</a></li>
        </ul><hr>',
        );

        $form['step_two'] = array(
            '#markup' => '<h5>'. t('Step 2: Scan this QR code with the app') . '</h5>'
        );

        $googleAppName = $mo_db_values['mo_auth_google_auth_app_name'] == '' ? 'miniOrangeAuth' : urldecode($mo_db_values['mo_auth_google_auth_app_name']);
        $customization_note = $App_Name === 'GOOGLE' ? '<div class="mo_2fa_highlight_background_note"><strong>Note: </strong>After scanning the below QR code, you will see the app in the Google Authenticator with the account name of <strong> ' . $googleAppName . ' </strong>. If you want to customize the account name goto <a href="' . MoAuthUtilities::get_mo_tab_url('LOGIN') . '">Login Settings</a> tab and navigate to <u>ADVANCE SETTINGS</u> section.</div><br>' : '';

        $form['mo_scan_qr_code_google_authenticator'] = array(
            '#markup' => t($customization_note)
        );

        $form['mo_scan_qr_code_google_authenticator']['actions_qrcode'] = array(
            '#markup' =>  new FormattableMarkup('<img src="data:image/jpg;base64, ' . $qrCode . '"/>', [':src' => $qrCode])?? '',
            '#prefix' => '<div class="container-inline conatiner-flex">',
            '#suffix' => '	&nbsp;&nbsp;',
        );

        $form['mo_scan_qr_code_google_authenticator']['secret'] = array(
            '#type' => 'hidden',
            '#value' => $secret
        );

        $form['mo_scan_qr_code_google_authenticator']['qrCode'] = array(
            '#type' => 'hidden',
            '#value' => $qrCode
        );

        $secret = $this->utilities->indentSecret($secret);

        $form['mo_scan_qr_code_google_authenticator']['actions_secret_key'] = array(
            '#markup' => t('<div class="qr_code_text">
                            <p><b>'.t('Can\'t scan the code? ').'</b>' .t('You can add the code to your application manually using the following details:').'</p>
                            <p id="googleAuthSecret"><b>Key: </b> <code class="mo_2fa_highlight_background_note">' . $secret . '</code> (Spaces does\'t matter)</p>
                            <p><b>'.t('Time based: ').'</b>'.t('Yes').'</p>
                          </div></div><hr>')
        );

        $form['mo_scan_qr_code_google_authenticator']['actions_3'] = array(
            '#markup' => '<h5>' . $this->t('Step 3: Enter the passcode generated by the app') . '</h5>'
        );

        $form['mo_scan_qr_code_google_authenticator']['mo_auth_google_auth_token'] = array(
            '#type' => 'textfield',
            '#title' => t('Passcode:'),
            '#maxlength' => 8,
            '#id' => 'passcode',
            '#attributes' => array(
                'placeholder' => t('Enter passcode'),
                'class' => array(
                    'mo2f-textbox',
                ),
            ),
            '#required' => true,
            '#prefix' => '<div class="container-inline">',
            '#suffix' => '&nbsp;&nbsp;',
        );

        $form['mo_scan_qr_code_google_authenticator']['action'] = [
            '#type' => 'actions'
        ];

        $form['mo_scan_qr_code_google_authenticator']['validate_code'] = array(
            '#type' => 'submit',
            '#button_type' => 'primary',
            '#value' => t('Verify'),
            '#suffix' => '</div>',
            '#attributes' => array('class' => array('use-ajax')),
            '#ajax' => array(
                'callback' => '::submit_totp_form',
                'wrapper' => $ajax_wrapper_id,
                'progress'  => array(
                    'type'    => 'throbber',
                ),
            ),
        );

        $form['mo_scan_qr_code_google_authenticator']['methodToConfigure'] = array(
            '#type' => 'hidden',
            '#value' => $authMethod
        );
    }

    //@todo add validate_answer function here also
    public function render_kba_2fa_configure(array &$form)
    {
        $utilities = new MoAuthUtilities();
        unset($form['#attached']['library']['miniorange_2fa/miniorange_2fa.license']);
        $pattern = MoAuthConstants::ALPHANUMERIC_LENGTH_PATTERN; // current pattern - '^[\w\s?]{3,}$'

        $form['#title'] = $this->t('Configure Security Questions (KBA)');

        $form['mo2fa_kba_details'] = [
            '#type' => 'details',
            '#title' => $this->t('Customization Note'),
        ];

        $form['mo2fa_kba_details']['markup_configure_kba_note'] = array(
            '#markup' => '<div class="mo_2fa_highlight_background_note"><strong>' . t('You can customize the following things of the SECURITY QUESTIONS (KBA) method:') . '</strong><ul><li>' . t('Customize the set of questions. ( You can add your own set of questions )') . '</li><li>' . t('Set number of questions to be asked while login/authentication') . t(' ( Contact us for more details )') . '</li><li>' . t('For customization goto') . ' <a target="_blank" href="' . MoAuthUtilities::get_mo_tab_url('LOGIN') . '#customize_kba">' . t('Login Settings') . '</a> ' . t('tab and navigate to ') . '<u>' . t('CUSTOMIZE KBA QUESTIONS') . '</u>' . t(' section.') . '</li></ul></div>',
        );

        $form['mo2f_kba_table'] = [
            '#type' => 'table'
        ];

        for($row = 0; $row < 3; $row++) {
          if($row < 2) {
            $form['mo2f_kba_table'][$row]['question'] = [
              '#type' => 'select',
              '#title' => t('@number. Question :', ['@number' => $row + 1]),
              '#options' => $utilities::mo_get_kba_questions($row + 1),
            ];
          } else {
            $form['mo2f_kba_table'][$row]['question'] = [
              '#type' => 'textfield',
              '#title' => t('@number. Question:', ['@number' => $row + 1]),
              '#attributes' => [
                'placeholder' => t('Enter your custom question here'),
                'pattern'  => '^[\w\s?]{3,}$',
                'title' => 'Only alphanumeric characters (with question mark) are allowed and include at least three characters.',
                ],
              '#required' => TRUE,
            ];
          }

          $form['mo2f_kba_table'][$row]['answer'] = [
            '#type' => 'textfield',
            '#title' => t('Answer:'),
            '#required' => TRUE,
            '#attributes' => [
              'placeholder' => t('Enter your answer'),
              'class' => ['custom-kba-validation'],
              'id' => 'kba-answer-'. ($row + 1),
              'pattern'  => $pattern,
              'title' => $this->t(MoAuthConstants::VALIDATION_MESSAGE),
            ],
          ];
        }

        $form['mo2f_kba_action'] = [
            '#type' => 'actions'
        ];

        $form['mo2f_kba_action']['mo_2fa_kba_submit'] = array(
            '#type' => 'submit',
            '#button_type' => 'primary',
            '#value' => t('Configure KBA'),
        );
    }

    public function render_qr_based_2fa_configure(array &$form, &$form_state, $authMethod)
    {
        global $base_url;
        $input            = $form_state->getUserInput();
        $user             = User::load(\Drupal::currentUser()->id());
        $user_id          = $user->id();
        $qrMethod         = AuthenticationType::getAuthType($authMethod);
        $utilities        = new MoAuthUtilities();
        $custom_attribute = $utilities::get_users_custom_attribute($user_id);

        if (array_key_exists('txId', $input) === false) {
            $user_email       = $custom_attribute[0]->miniorange_registered_email;
            $customer         = new MiniorangeCustomerProfile();
            $miniorange_user  = new MiniorangeUser($customer->getCustomerID(), $user_email, null, null, AuthenticationType::$QR_CODE['code']);
            $auth_api_handler = new AuthenticationAPIHandler($customer->getCustomerID(), $customer->getAPIKey());
            $response         = $auth_api_handler->register($miniorange_user, AuthenticationType::$QR_CODE['code'], null, null, null);
            $session          = $utilities->getSession();
            $moMfaSession     = $session->get("mo_auth", null);
            $moMfaSession['mo_challenge_response'] = $response;

            $qrCode = $response->qrCode ?? '';
            $image = new FormattableMarkup('<img class="mo_2fa_qr_code_image" src="data:image/jpg;base64, ' . $qrCode . '"/>', [':src' => $qrCode]);

            /** Main form of QR code based methods **/
            $form['#title'] = $this->t('Configure @method_name', ['@method_name' => $qrMethod['name']]);

            $form['mo_install_miniorange_authenticator'] = array(
                '#markup' => '<h5>'. $this->t('Step 1 : Download the miniOrange authenticator app').'</h5>
                              <ul>
                                <li class="two-step-verification--app-item--ios">iPhone, iPod Touch, or iPad: <a target="_blank" href="'.$qrMethod['ios-link'].'">'.$this->t('Download MFA-Authenticator').'</a></li>
                                <li class="two-step-verification--app-item--android ">Android devices: <a target="_blank" href="'.$qrMethod['android-link'].'">'.$this->t('Download Authenticator').'</a></li>
                              </ul><hr>',
            );

            $form['step_two'] = array(
                '#markup' => '<h5>'. t('Step 2: Scan this QR code with the app, the form will submit automatically') . '</h5>',
            );

            $form['mo_qr_code_miniorange_authenticator']['actions_qrcode'] = array(
                '#markup' => $image,
            );

            /**
             * Accessed form mo_authentication.js file
             */
            $form['mo_qr_code_miniorange_authenticator']['txId'] = array(
                '#type'  => 'hidden',
                '#value' => $response->txId
            );
            $form['mo_qr_code_miniorange_authenticator']['url'] = array(
                '#type'  => 'hidden',
                '#value' => MoAuthConstants::getBaseUrl() . MoAuthConstants::$AUTH_REGISTRATION_STATUS_API,
            );
            $form['mo_qr_code_miniorange_authenticator']['authTypeCode'] = array(
                '#type'  => 'hidden',
                '#value' => $authMethod
            );
        }

        $form['actions_submit'] = array(
            '#type' => 'submit',
            '#value' => t('Save'), //Save
            '#id' => 'mo-auth-configure-admin-2fa',
            '#hidden' => TRUE,
            '#attributes' => array('class' => array('hidebutton',)),
        );
    }

    public function render_hardware_token_2fa_configure(array &$form, &$form_state, $authMethod)
    {
        $form['#title'] = $this->t('Configure Yubikey Hardware Token');

        $form['mo_configure_hardware_token'] = array(
            '#type'        => 'textfield',
            '#title'       => $this->t('Hardware Token One Time Passcode'),
            '#description' => $this->t('Insert the Hardware Token in the USB Port and touch button on Hardware token.'),
            '#attributes'  => array('placeholder' => $this->t('Enter the token'), 'autofocus' => true, 'autocomplete' => 'off'),
            '#required'    => true,
            '#maxlength'   => 60,
        );

        $form['mo_hardware_token_action'] = array(
            '#type' => 'actions',
        );

        $form['mo_hardware_token_action']['done'] = array(
            '#type'        => 'submit',
            '#value'       => t('Submit'),
            '#button_type' => 'primary',
        );
    }

    /**
     * Most challenging modal form. Please make changes carefully. <br>
     * To render this form total 3 functions are used.
     * 1. render_sms_based_2fa_configure()
     * 2. mo_auth_send_otp()
     * 3. submit_otp_form()
     *
     * Steps:
     * ### 1. First show text box to enter the phone number and send OTP.
     *  - If Success -> form_state->set('name', 'OTP') and show Form with text
     * box to enter OTP
     *  - If Failure -> Show Error and redirect to configure_admin_2fa form
     *
     * ### 2. Form with text box to enter the OTP gives you 3 additional
     * options
     *  - Change number -> Open the same form again
     *  - Resend OTP -> Send OTP again using same mobile number
     *  - Verify OTP -> Verify the OTP and do action accordingly
     *
     * ### 3. If OTP entered is correct then redirect to configure_admin_2fa
     * form otherwise show error on same page
     *
     * @param array $form
     * @param \Drupal\Core\Form\FormStateInterface $form_state
     * @param $authMethod
     *
     * @return void
     */
    public function render_sms_based_2fa_configure(array &$form, FormStateInterface $form_state, $authMethod)
    {
        $user             = User::load(\Drupal::currentUser()->id());
        $user_id          = $user->id();
        $utilities        = new MoAuthUtilities();
        $custom_attribute = $utilities::get_users_custom_attribute($user_id);
        $user_email       = $custom_attribute[0]->miniorange_registered_email;
        $phoneNumber      = MoAuthUtilities::getUserPhoneNumber($user_id);
        $authMethodArray  = AuthenticationType::getAuthType($authMethod);

        $variables_and_values = ['mo_auth_2fa_ivr_remaining', 'mo_auth_2fa_sms_remaining', 'mo_auth_2fa_email_remaining',];
        $mo_db_values         = $utilities->miniOrange_set_get_configurations($variables_and_values, 'GET');
        $authTypeCode         = $authMethodArray['code'];
        $pageTitle            = $this->t('Configure @method_name', ['@method_name' => $authMethodArray['name']]);


        /**
         * To check which method (OTP Over Email, OTP Over SMS, OTP Over Email and SMS, OTP Over Phone') is being configured by user
         */
        switch ($authMethod) {
            case AuthenticationType::$SMS['code']:
                $mo_note = t('<ul><li>Customize SMS template.</li><li>Customize OTP Length and Validity.</li><li>For customization navigate to <u>CUSTOMIZE SMS AND EMAIL TEMPLATE</u> section under <a target="_blank" href="' . MoAuthUtilities::get_mo_tab_url('LOGIN') . '#sms_template"> Login Settings </a> tab.</li>');
                $mo_db_values['mo_auth_2fa_sms_remaining'] == 0 ? \Drupal::messenger()->addWarning('Zero SMS Transactions Remaining') : '';
                break;

            case AuthenticationType::$SMS_AND_EMAIL['code']:
                $mo_note = t('<ul><li>Customize Email template.</li><li>Customize SMS template.</li><li>Customize OTP Length and Validity.</li><li>For customization navigate to <u>CUSTOMIZE SMS AND EMAIL TEMPLATE</u> section under <a target="_blank" href="' . MoAuthUtilities::get_mo_tab_url('LOGIN') . '#sms_template"> Login Settings </a> tab.</li>');
                $mo_db_values['mo_auth_2fa_email_remaining'] == 0 ? \Drupal::messenger()->addWarning('Zero SMS/Email Transactions Remaining') : '';
                break;

            case AuthenticationType::$OTP_OVER_PHONE['code']:
                $mo_db_values['mo_auth_2fa_ivr_remaining'] == 0 ? \Drupal::messenger()->addWarning('Zero IVR Transactions Remaining') : '';
                break;
        };

        /** Main Content of the form **/
        $form['#title']  = $pageTitle;
        $form['#prefix'] = '<div id="otp-based-methods">';
        $form['#suffix'] = '</div>';

        /** Message area for modal form */
        $renderer        = \Drupal::service('renderer');
        $status_messages = ['#type' => 'status_messages'];
        $form['#prefix'].= $renderer->renderRoot($status_messages);

        if ($authMethod != AuthenticationType::$OTP_OVER_PHONE['code'] && ($form_state->get('name') != 'OTP')) {
            $form['mo_totp_based_methods']['details'] = [
                '#type'  => 'details',
                '#title' => $this->t('Customization Note'),
            ];

            $form['mo_totp_based_methods']['details']['header'] = [
                '#type'   => 'item',
                '#markup' => t('<div class="mo_2fa_highlight_background_note"><strong>You can customize the following things of the ' . $authMethodArray['name'] . ' method:</strong>' . $mo_note . '</div><br>'),
            ];
        }

        if (($authMethod == AuthenticationType::$EMAIL['code'] || $authMethod == AuthenticationType::$SMS_AND_EMAIL['code']) && ($form_state->get('name') != 'OTP')) {
            $form['mo_totp_based_methods']['email'] = [
                '#type' => 'textfield',
                '#title' => t('Verify Your Email'),
                '#value' => $user_email,
                '#required' => true,
                '#disabled' => true
            ];
        }

        if ($form_state->get('name') != 'OTP') {
            $form['mo_totp_based_methods']['phone'] = [
                '#type'          => 'tel',
                '#title'         => t('Phone Number'),
                '#default_value' => $phoneNumber,
                '#required'      => TRUE,
                '#attributes'    => [
                    'id'           => 'query_phone',
                    'autocomplete' =>'on',
                ],
            ];

            $form['mo_totp_based_methods']['phone_full'] = [
               '#type' => 'hidden',
               '#default_value' => $phoneNumber,
            ];
        }

        $form['mo_totp_based_methods']['authTypeCode'] = [
            '#type' => 'hidden',
            '#value' => $authTypeCode
        ];

        $form['mo_totp_based_methods']['action'] = [
            '#type' => 'actions',
        ];

        /** Form to enter OTP, after successfully send OTP **/
        if ($form_state->get('name') != 'OTP') {
            $form['mo_totp_based_methods']['action']['request_otp'] = [
                '#type'        => 'submit',
                '#submit'      => ['::submit_otp_form'],
                '#button_type' => 'primary',
                '#value'       => $this->t('Request OTP'),
                '#ajax'        => [
                    'callback'   => '::mo_auth_send_otp',
                    'wrapper'    => 'otp-based-methods'
                ],
            ];
        }

        if ($form_state->get('name') == 'OTP') {
            $form['mo_totp_based_methods']['miniorange_OTP'] = [
                '#type'       => 'textfield',
                '#title'      => t('OTP'),
                '#required'   => true,
                '#maxlength'  => 8,
                '#attributes' => [ 'placeholder' => t('Enter passcode'),],
            ];

            $form['mo_totp_based_methods']['action']['verify_phone'] = [
                '#type'        => 'submit',
                '#button_type' => 'primary',
                '#name'        => 'verify_phone',
                '#value'       => $this->t('Verify'),
                '#ajax'   => [
                    'event'    => 'click',
                    'callback' => '::submit_otp_form',
                    'wrapper'  => 'otp-based-methods'
                ],
            ];

            $form['mo_totp_based_methods']['action']['resend_otp'] = [
                '#type'   => 'submit',
                '#name'   => 'resend_otp',
                '#value'  => $this->t('Resend OTP'),
                '#limit_validation_errors' => [],
                '#submit' => ['::mo_auth_resend_otp'],
                '#ajax'   => [
                    'event'    => 'click',
                    'callback' => '::mo_auth_send_otp',
                    'wrapper'  => 'otp-based-methods'
                ],
            ];
        }
    }

    /** This function is used for ajax-callback of 'Save' button of TOTP form  **/
    public function submit_totp_form(array &$form, FormStateInterface $form_state)
    {
        $input              = $form_state->getUserInput();
        $utilities          = new MoAuthUtilities();
        $customer           = new MiniorangeCustomerProfile();
        $user               = User::load(\Drupal::currentUser()->id());
        $user_id            = $user->id();
        $custom_attribute   = $utilities::get_users_custom_attribute($user_id);
        $user_email         = $custom_attribute[0]->miniorange_registered_email;
        $auth_api_handler   = new AuthenticationAPIHandler($customer->getCustomerID(), $customer->getAPIKey());
        $authMethod         = $_GET['authMethod'];
        $configured_methods = $utilities::mo_auth_get_configured_methods($this->custom_attribute);
        $user_api_handler   = new UsersAPIHandler($customer->getCustomerID(), $customer->getAPIKey());
        $miniorange_user    = new MiniorangeUser($customer->getCustomerID(), $user_email, null, null, $authMethod);
        $ajax_response      = new AjaxResponse();

        if ($form_state->hasAnyErrors()) {
            $ajax_response->addCommand(new ReplaceCommand('#modal_support_form', $form));
            return $ajax_response;
        }

        $secret   = preg_replace('/\s+/', '', $input['secret']);
        $otpToken = $form_state->getValue('mo_auth_google_auth_token');
        $response = $auth_api_handler->register($miniorange_user, AuthenticationType::$GOOGLE_AUTHENTICATOR['code'], $secret, $otpToken, null);
        \Drupal::messenger()->deleteAll();  // Clear all the messages

        if (is_object($response) && $response->status == 'SUCCESS') {
            \Drupal::messenger()->addStatus(t(''));
            /**
             * Delete all the configured TOTP methods as only one can be used at a time
             */
            $configured_methods = array_values(array_diff($configured_methods, $utilities->mo_TOTP_2fa_mentods()));
            array_push($configured_methods, $authMethod);

            $config_methods = implode(', ', $configured_methods);
            $response       = $user_api_handler->update($miniorange_user);

            if (is_object($response) && $response->status == 'SUCCESS') {
                // Save User
                $available = $utilities::check_for_userID($user_id);
                $database  = \Drupal::database();

                if ($available) {
                    $database->update('UserAuthenticationType')->fields(['activated_auth_methods' => $authMethod, 'configured_auth_methods' => $config_methods, 'qr_code_string' => $input['qrCode']])->condition('uid', $user_id, '=')->execute();
                } else {
                    \Drupal::messenger()->addError(t('Error while updating authentication method.'));
                    $ajax_response->addCommand(new RedirectCommand(Url::fromRoute('miniorange_2fa.setup_twofactor')->toString()));
                    return $ajax_response;
                }

                $message = $this->t('%method_name configured successfully.', ['%method_name' => ucwords(strtolower($authMethod))]);
                \Drupal::messenger()->addStatus($message);
                $ajax_response->addCommand(new RedirectCommand(Url::fromRoute('miniorange_2fa.setup_twofactor')->toString()));
                return $ajax_response;
            }
        } elseif (is_object($response) && $response->status == 'FAILED') {
            $message  = $this->t('The passcode you have entered is incorrect. Please try again.');
            $ajax_response->addCommand(new OpenDialogCommand('#error', 'Error', $message, ['minWidth' => 500], []));
            return $ajax_response;
        }
        
        $message = t('An error occurred while processing your request. Please try again.');
        \Drupal::logger('miniorange_2fa')->debug('<pre><code>'.print_r($response, TRUE).'</pre></code>');
        \Drupal::messenger()->addError($message);
        $ajax_response->addCommand(new RedirectCommand(Url::fromRoute('miniorange_2fa.setup_twofactor')->toString()));
        return $ajax_response;
    }

    /** This function is used for ajax-callback of 'Request OTP' and 'Resend OTP' button **/
    public function mo_auth_send_otp(array &$form, FormStateInterface $form_state)
    {
        $renderer         = \Drupal::service('renderer');
        $status_messages  = ['#type' => 'status_messages'];
        $form['#prefix'] .= $renderer->renderRoot($status_messages);

        if ($form_state->hasAnyErrors()) {
            return $form;
        }

        $utilities        = new MoAuthUtilities();
        $ajax_response    = new AjaxResponse();
        $customer         = new MiniorangeCustomerProfile();
        $custID           = $customer->getCustomerID();
        $api_key          = $customer->getAPIKey();
        $form_values      = $form_state->getValues();
        $button_name      = $form_state->getTriggeringElement()['#name'];
        $send             = $button_name == 'resend_otp' ? 'resent' : 'send';
        $user             = User::load(\Drupal::currentUser()->id());
        $custom_attribute = $utilities::get_users_custom_attribute($user->id());
        $user_email       = $custom_attribute[0]->miniorange_registered_email;
        $phone_number     = isset($form_values['phone_full']) ? trim($form_values['phone_full']) : '';
        $authMethod       = isset($form_values['authTypeCode']) ? $form_values['authTypeCode'] : '';

        /** For resend OTP we are taking phone number from form_state**/
        if ($button_name == 'resend_otp') {
            $authMethod   = $form_state->get('authTypeCode');
            $phone_number = $form_state->get('phone');
        }

        switch ($authMethod) {
            case AuthenticationType::$OTP_OVER_EMAIL['code']:
                $currentMethod      = "OTP_OVER_EMAIL";
                $params             = array('email' => $user_email);
                $mo_status_message  = t('We have @send an OTP to %user_email. Please enter the OTP to verify your email.', ['@send' => $send, '%user_email' => $user_email]);
                break;

            case AuthenticationType::$SMS['code']:
                $currentMethod      = "OTP_OVER_SMS";
                $params             = array('phone' => $phone_number);
                $mo_status_message  = t('We have @send an OTP to %phone_number. Please enter the OTP to verify your phone number. ', ['@send' => $send, '%phone_number' => $phone_number]);
                break;

            case AuthenticationType::$SMS_AND_EMAIL['code']:
                $currentMethod      = "OTP_OVER_SMS_AND_EMAIL";
                $params             = array('phone' => $phone_number, 'email' => $user_email);
                $mo_status_message  = t('We have @send an OTP to %user_email and %phone_number. Please enter the OTP to verify your email and phone number. ', ['@send' => $send, '%user_email' => $user_email, '%phone_number' => $phone_number]);
                break;

            case AuthenticationType::$OTP_OVER_PHONE['code']:
                $currentMethod      = "PHONE_VERIFICATION";
                $params             = array('phone' => $phone_number);
                $mo_status_message  = t('You will receive phone call on %phone_number shortly, which prompts OTP. Please enter the OTP to verify your phone number. ', ['%phone_number' => $phone_number]);
                break;
        }

        $mo_status_message .=  '<a class="use-ajax js-form-submit"  data-dialog-type = "modal"  data-ajax-progress="fullscreen" data-dialog-options="{&quot;width&quot;:&quot;50%&quot;}" href="configure_admin_2fa?authMethod=' . $authMethod . '">Change number</a>';
        $customer_config    = new MiniorangeCustomerSetup($user_email, $phone_number, null, null);
        $send_otp_response  = $customer_config->send_otp_token($params, $currentMethod, $custID, $api_key);


        if ($send_otp_response->status == 'SUCCESS') {
            // Store txID.
            \Drupal::messenger()->addStatus(t($mo_status_message));
            \Drupal::configFactory()->getEditable('miniorange_2fa.settings')->set('mo_auth_tx_id', $send_otp_response->txId)->save();
            return $form;
        } else {
            $user_api_handler = new UsersAPIHandler($customer->getCustomerID(), $customer->getAPIKey());
            $response         = $user_api_handler->fetchLicense();

            if ($response->smsRemaining == 0 || $response->emailRemaining == 0 || $response->ivrRemaining == 0) {
                \Drupal::messenger()->addError($this->t('The number of OTP transactions have exhausted. Please recharge your account with SMS/Email/IVR transactions.'));
            } else {
                \Drupal::messenger()->addError($this->t('There was an unexpected error. Please try again.'));
            }
            $ajax_response->addCommand(new RedirectCommand(Url::fromRoute('miniorange_2fa.setup_twofactor')->toString()));
            return $ajax_response;
        }
    }

    /** This function is used for ajax-callback of 'Verify' button and used as submit handler for 'Resend OTP' and 'Request OTP' button **/
    public function submit_otp_form(array &$form, FormStateInterface $form_state)
    {
        $form_values = $form_state->getValues();

        /** Set form_state variable 'name' to 'OTP' in order to display only text box for entering OTP **/
        if ($form_state->get('name') != 'OTP') {
            $form_state->set('name', 'OTP');
            $form_state->set('phone', $form_values['phone_full']);
            $form_state->set('authTypeCode', $form_values['authTypeCode']);
            $form_state->setRebuild();
            return;
        }

        /** If user click on 'Resend OTP' button then don't do anything, just rebuild form **/
        if ($form_state->getTriggeringElement()['#name'] == 'resend_otp') {
            $form_state->setRebuild();
            return;
        }

        $database           = \Drupal::database();
        $user               = User::load(\Drupal::currentUser()->id());
        $user_id            = $user->id();
        $authMethod         = $_GET['authMethod'];
        $utilities          = new MoAuthUtilities();
        $customer           = new MiniorangeCustomerProfile();
        $ajax_response      = new AjaxResponse();
        $custom_attribute   = $utilities::get_users_custom_attribute($user_id);
        $user_email         = $custom_attribute[0]->miniorange_registered_email;
        $authMethodArray    = AuthenticationType::getAuthType($authMethod);
        $configured_methods = $utilities::mo_auth_get_configured_methods($custom_attribute);
        $miniorange_user    = new MiniorangeUser($customer->getCustomerID(), $user_email, null, null, $this->authMethod);
        $customer_config    = new MiniorangeCustomerSetup($user_email, null, null, null);
        $user_api_handler   = new UsersAPIHandler($customer->getCustomerID(), $customer->getAPIKey());
        $cKey               = $customer->getCustomerID();
        $customerApiKey     = $customer->getAPIKey();
        $status_messages    = ['#type' => 'status_messages'];
        $renderer           = \Drupal::service('renderer');
        $form['#prefix']   .= $renderer->renderRoot($status_messages);

        if ($form_state->hasAnyErrors()) {
            return $form;
        }

        $otpToken       = str_replace(' ', '', $form_values['miniorange_OTP']);
        $phone_number   = $form_state->get('phone');
        $transactionId  = \Drupal::config('miniorange_2fa.settings')->get('mo_auth_tx_id');
        $otp_validation = $customer_config->validate_otp_token($transactionId, $otpToken, $cKey, $customerApiKey);

        \Drupal::configFactory()->getEditable('miniorange_2fa.settings')->clear('mo_auth_tx_id')->save();

        if (is_object($otp_validation) && !empty($otpToken)&& $otp_validation->status == 'FAILED') {
            \Drupal::messenger()->addError(t("Validation Failed. Please enter the correct OTP."));
            return $form;
        } elseif (is_object($otp_validation) && $otp_validation->status == 'SUCCESS') {
            $form_state->setRebuild();
            if (!in_array($authMethod, $configured_methods)) {
                array_push($configured_methods, $authMethod);
            }

            $config_methods = implode(', ', $configured_methods);

            // Updating the authentication method for the user
            $miniorange_user->setAuthType($authMethod);
            $response = $user_api_handler->update($miniorange_user);

            if (is_object($response) && $response->status == 'SUCCESS') {
                $available = $utilities::check_for_userID($user_id);
                if ($available) {
                    $database->update('UserAuthenticationType')->fields(['activated_auth_methods' => $authMethod, 'configured_auth_methods' => $config_methods, 'phone_number' => $phone_number])->condition('uid', $user_id, '=')->execute();
                } else {
                    \Drupal::messenger()->addError(t("Error while updating authentication method."));
                    $ajax_response->addCommand(new RedirectCommand(Url::fromRoute('miniorange_2fa.setup_twofactor')->toString()));
                    return $ajax_response;
                }
                $message = $this->t('%method_name has been configured successfully.', ['%method_name' => $authMethodArray['name']]);
                \Drupal::messenger()->addStatus($message);
                $ajax_response->addCommand(new RedirectCommand(Url::fromRoute('miniorange_2fa.setup_twofactor')->toString()));
                return $ajax_response;
            }
        }

        $message = t('An error occurred while processing your request. Please try again.');
        \Drupal::messenger()->addError($message);
        $ajax_response->addCommand(new RedirectCommand(Url::fromRoute('miniorange_2fa.setup_twofactor')->toString()));
        return $ajax_response;
    }



    /**
    * @param array $form
    * @param \Drupal\Core\Form\FormStateInterface $form_state
    *
    * Don't delete this empty function. This function is used for Resend OTP button.
    * limit_validation_errors property only work on button if it has submit callback
    * Resend OTP button only require ajax callback therefore empty submit callback function is created.
    */
    public function mo_auth_resend_otp(array &$form, FormStateInterface $form_state) {

    }

    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        /** If button_name is send OTP or resend OTP then don't do any process in submit form **/
        $button_name        = $form_state->getTriggeringElement()['#name'];
        if ($button_name == 'resend_otp' || $button_name == 'verify_phone') {
            return;
        }

        $input              = $form_state->getUserInput();
        $form_values        = $form_state->getValues();
        $utilities          = new MoAuthUtilities();
        $customer           = new MiniorangeCustomerProfile();
        $user               = User::load(\Drupal::currentUser()->id());
        $user_id            = $user->id();
        $custom_attribute   = $utilities::get_users_custom_attribute($user_id);
        $user_email         = $custom_attribute[0]->miniorange_registered_email;
        $auth_api_handler   = new AuthenticationAPIHandler($customer->getCustomerID(), $customer->getAPIKey());
        $authMethod         = $_GET['authMethod'];
        $configured_methods = $utilities::mo_auth_get_configured_methods($this->custom_attribute);
        $user_api_handler   = new UsersAPIHandler($customer->getCustomerID(), $customer->getAPIKey());
        $miniorange_user    = new MiniorangeUser($customer->getCustomerID(), $user_email, null, null, $authMethod);

        $qr_based_methods = function () use ($input, $auth_api_handler, $miniorange_user, $user_api_handler, $utilities, $authMethod, $user, $configured_methods) {
            $txId            = $input['txId'];
            $authMethodArray = AuthenticationType::getAuthType($authMethod);
            $response        = $auth_api_handler->getRegistrationStatus($txId);
            \Drupal::messenger()->deleteAll();  // Clear all the messages

            if (is_object($response) && $response->status == 'SUCCESS') {
                /**
                 * If one of the methods in Soft Token, QR Code Authentication, Push Notification is configured then all three methods are configured.
                 */

                if (!in_array(AuthenticationType::$SOFT_TOKEN['code'], $configured_methods)) {
                    $configured_methods[] = AuthenticationType::$SOFT_TOKEN['code'];
                }
                if (!in_array(AuthenticationType::$QR_CODE['code'], $configured_methods)) {
                    $configured_methods[] = AuthenticationType::$QR_CODE['code'];
                }
                if (!in_array(AuthenticationType::$PUSH_NOTIFICATIONS['code'], $configured_methods)) {
                    $configured_methods[] = AuthenticationType::$PUSH_NOTIFICATIONS['code'];
                }

                $config_methods = implode(', ', $configured_methods);
                $updateResponse = $user_api_handler->update($miniorange_user); //update users 2FA method in miniOrange dashboard

                if ($updateResponse->status == 'SUCCESS') {
                    // Save user
                    $database  = \Drupal::database();
                    $user_id   = $user->id();
                    $available = $utilities::check_for_userID($user_id);

                    if ($available) {
                        $database->update('UserAuthenticationType')->fields(['configured_auth_methods' => $config_methods, 'activated_auth_methods' => $authMethod])->condition('uid', $user_id, '=')->execute();
                    } else {
                        \Drupal::messenger()->addError(t("Error while updating authentication method."));
                        return;
                    }

                    $message = t('@method_name configured successfully.', ['@method_name' => $authMethodArray['name']]);
                    MoAuthUtilities::show_error_or_success_message($message, 'status');
                    return;
                } else {
                    $utilities->mo_add_loggers_for_failures($updateResponse->message, 'error');
                    $utilities->redirectUserToLoginPage('An error occurred while processing your request. Please try again after sometime.');
                }
            }
        };

        $hardware_token   = function () use ($form_values, $auth_api_handler, $miniorange_user, $user_api_handler, $utilities, $custom_attribute, $user_id) {
            $hardware_token = $form_values['mo_configure_hardware_token'];
            $response       = $auth_api_handler->register($miniorange_user, AuthenticationType::$HARDWARE_TOKEN['code'], null, $hardware_token, null);

            if (is_object($response) && $response->status == 'SUCCESS') {
                $updateResponse = $user_api_handler->update($miniorange_user); //update users 2FA method in miniOrange dashboard
                if (is_object($updateResponse) && $updateResponse->status == 'SUCCESS') {
                    // Save User
                    $configured_methods = $utilities::mo_auth_get_configured_methods($custom_attribute);
                    if (!in_array(AuthenticationType::$HARDWARE_TOKEN['code'], $configured_methods)) {
                        $configured_methods[] = AuthenticationType::$HARDWARE_TOKEN['code'];
                    }
                    $config_methods = implode(', ', $configured_methods);
                    $available      = $utilities::check_for_userID($user_id);
                    $database       = \Drupal::database();

                    if ($available) {
                        $database->update('UserAuthenticationType')->fields(['activated_auth_methods' => AuthenticationType::$HARDWARE_TOKEN['code'], 'configured_auth_methods' => $config_methods])->condition('uid', $user_id, '=')->execute();
                    } else {
                        \Drupal::messenger()->addError('Error while updating authentication method.');
                        return;
                    }

                    $message = t('Hardware Token configured successfully.');
                    MoAuthUtilities::show_error_or_success_message($message, 'status');
                } else {
                    $message = t('An error occurred while processing your request. Please try again.');
                    MoAuthUtilities::show_error_or_success_message($message, 'error');
                }
                return;
            } elseif (is_object($response) && $response->status == 'FAILED') {
                $message = t('An error occurred while configuring Hardware Token. Please try again.');
                MoAuthUtilities::show_error_or_success_message($message, 'error');
                return;
            }

            $message = t('An error occurred while processing your request. Please try again.');
            MoAuthUtilities::show_error_or_success_message($message, 'error');
            return;
        };

        $kba_method       = function () use ($form_values, $auth_api_handler, $miniorange_user, $user_api_handler, $utilities, $custom_attribute, $user, $customer) {
            $questions = [];
            for ($row = 0; $row<=2; $row++) {
                $questions[$row] = [
                    "question" => trim($form_values['mo2f_kba_table'][$row]['question']),
                    "answer"   => trim($form_values['mo2f_kba_table'][$row]['answer']),
                ];
            }

            $kba      = array($questions[0], $questions[1], $questions[2]);
            $response = $auth_api_handler->register($miniorange_user, AuthenticationType::$KBA['code'], null, null, $kba);
            \Drupal::messenger()->deleteAll();      // Clear all the messages

            if (is_object($response) && $response->status == 'SUCCESS') {     // read API response
                $configured_methods = $utilities::mo_auth_get_configured_methods($custom_attribute);

                if (!in_array(AuthenticationType::$KBA['code'], $configured_methods)) {
                    $configured_methods[] = AuthenticationType::$KBA['code'];
                }

                $config_methods   = implode(', ', $configured_methods);
                $user_api_handler = new UsersAPIHandler($customer->getCustomerID(), $customer->getAPIKey());
                $response         = $user_api_handler->update($miniorange_user);

                if (is_object($response) && $response->status == 'SUCCESS') {
                    // Save User
                    $user_id    = $user->id();
                    $available  = $utilities::check_for_userID($user_id);
                    $database   = \Drupal::database();

                    if ($available) {
                        $database->update('UserAuthenticationType')->fields(['activated_auth_methods' => AuthenticationType::$KBA['code'], 'configured_auth_methods' => $config_methods])->condition('uid', $user_id, '=')->execute();
                    } else {
                        \Drupal::messenger()->addError('Error while updating authentication method.');
                        return;
                    }

                    $message = t('KBA Authentication configured successfully.');
                    MoAuthUtilities::show_error_or_success_message($message, 'status');
                    return;
                }
            } elseif (is_object($response) && $response->status == 'FAILED') {
                $message = t('An error occurred while configuring KBA Authentication. Please try again.');
                MoAuthUtilities::show_error_or_success_message($message, 'error');
                return;
            }

            $message = t('An error occurred while processing your request. Please try again.');
            MoAuthUtilities::show_error_or_success_message($message, 'error');
            return;
        };

        switch ($authMethod) {
            case AuthenticationType::$QR_CODE['code']:
            case AuthenticationType::$SOFT_TOKEN['code']:
            case AuthenticationType::$PUSH_NOTIFICATIONS['code']:
                $qr_based_methods();
                break;

            case AuthenticationType::$HARDWARE_TOKEN['code']:
                $hardware_token();
                break;

            case AuthenticationType::$KBA['code']:
                $kba_method();
                break;
        }
    }

}
