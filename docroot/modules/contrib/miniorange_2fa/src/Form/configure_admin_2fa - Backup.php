<?php

// Old file of admin 2FA configuration file. If new modal form works without any error, then delete this file after Jun 2023. 
//
//namespace Drupal\miniorange_2fa\Form;
//
//use Drupal\user\Entity\User;
//use Drupal\Core\Form\FormBase;
//use Drupal\Core\Ajax\AjaxResponse;
//use Drupal\Core\Ajax\MessageCommand;
//use Drupal\Core\Form\FormStateInterface;
//use Drupal\miniorange_2fa\MiniorangeUser;
//use Drupal\miniorange_2fa\UsersAPIHandler;
//use Drupal\miniorange_2fa\MoAuthUtilities;
//use Drupal\miniorange_2fa\MoAuthConstants;
//use Drupal\miniorange_2fa\AuthenticationType;
//use Drupal\Component\Render\FormattableMarkup;
//use Drupal\miniorange_2fa\MiniorangeCustomerSetup;
//use Drupal\miniorange_2fa\AuthenticationAPIHandler;
//use Drupal\miniorange_2fa\MiniorangeCustomerProfile;
//
///**
// * @file
// *  This is used to configure 2fa of the admin
// */
//class configure_admin_2fa extends FormBase
//{
//
//    private MoAuthUtilities $utilities;
//    private $user;
//    private $user_id;
//    private $custom_attribute;
//    private $authMethod;
//    private $user_email;
//    private MiniorangeCustomerProfile $customer;
//    private AuthenticationAPIHandler $auth_api_handler;
//
//    public function __construct()
//    {
//        $this->utilities = new MoAuthUtilities();
//        $this->user = User::load(\Drupal::currentUser()->id());
//        $this->user_id = $this->user->id();
//        $this->custom_attribute = $this->utilities::get_users_custom_attribute($this->user_id);
//        $this->user_email = $this->custom_attribute[0]->miniorange_registered_email;
//        $this->authMethod = $_GET['authMethod'];
//        $this->customer = new MiniorangeCustomerProfile();
//        $this->auth_api_handler = new AuthenticationAPIHandler($this->customer->getCustomerID(), $this->customer->getAPIKey());
//    }
//
//    public function getFormId()
//    {
//        return 'mo_auth_configure_admin_2fa';
//    }
//
//    public function buildForm(array $form, FormStateInterface $form_state)
//    {
//
//        $form['markup_library'] = array(
//            '#attached' => array(
//                'library' => array(
//                    'miniorange_2fa/miniorange_2fa.admin',
//                    'miniorange_2fa/miniorange_2fa.license',
//                ),
//            ),
//        );
//
//        $form['markup_top_2'] = array(
//            '#markup' => '<div class="mo_2fa_table_layout_1"><div class="mo_2fa_table_layout mo_2fa_container">'
//        );
//        $miniorange_user = new MiniorangeUser($this->customer->getCustomerID(), $this->user_email, NULL, NULL, NULL);
//
//        if (in_array($this->authMethod, $this->utilities->mo_TOTP_2fa_mentods())) {
//            $response = $this->auth_api_handler->getGoogleAuthSecret($miniorange_user);
//            if ($response->status == 'SUCCESS') {
//                self::render_totp_based_2fa_configure($form, $form_state, $this->authMethod, $response);
//            } else {
//                $this->utilities->mo_add_loggers_for_failures($response->message, 'error');
//                $this->utilities->redirectUserToSetupTwoFactor('An error occurred while processing your request. Please try again after sometime.');
//            }
//        }
//
//        if ($this->authMethod == AuthenticationType::$KBA['code']) {
//            $message = 'Please choose your security questions (KBA) and answer those:';
//            \Drupal::messenger()->addStatus(t($message));
//            self::render_kba_2fa_configure($form, $form_state, $this->authMethod);
//        }
//
//        if ($this->authMethod == AuthenticationType::$QR_CODE['code'] || $this->authMethod == AuthenticationType::$SOFT_TOKEN['code'] || $this->authMethod == AuthenticationType::$PUSH_NOTIFICATIONS['code']) {
//            $response = $this->auth_api_handler->register($miniorange_user, AuthenticationType::$QR_CODE['code'], NULL, NULL, NULL);
//            if ($response->status == 'IN_PROGRESS') {
//                self::render_qr_based_2fa_configure($form, $form_state, $this->authMethod);
//            } else {
//                $this->utilities->mo_add_loggers_for_failures($response->message, 'error');
//                $this->utilities->redirectUserToSetupTwoFactor('An error occurred while processing your request. Please try again after sometime.');
//            }
//        }
//
//        if ($this->authMethod === AuthenticationType::$HARDWARE_TOKEN['code']) {
//            self::render_hardware_token_2fa_configure($form, $form_state, $this->authMethod);
//        }
//
//
//        if ($this->authMethod == AuthenticationType::$SMS['code'] || $this->authMethod == AuthenticationType::$SMS_AND_EMAIL['code'] || $this->authMethod == AuthenticationType::$OTP_OVER_PHONE['code']) {
//            self::render_sms_based_2fa_configure($form, $this->authMethod);
//        }
//
//        MoAuthUtilities::miniOrange_advertise_case_studies($form, $form_state);
//
//        return $form;
//    }
//
//    public function render_totp_based_2fa_configure(array &$form, &$form_state, $authMethod, $response)
//    {
//        $input = $form_state->getUserInput();
//        if (array_key_exists('secret', $input) === FALSE) {
//            $qrCode = isset($response->qrCodeData) ? $response->qrCodeData : '';
//            $image = new FormattableMarkup('<img src="data:image/jpg;base64, ' . $qrCode . '"/>', [':src' => $qrCode]);
//            $secret = isset($response->secret) ? $response->secret : '';
//        } else {
//            $secret = $input['secret'];
//            $qrCode = $input['qrCode'];
//        }
//
//        $App_Name = strtok($authMethod, " ");
//
//        $variables_and_values = array(
//            'mo_auth_google_auth_app_name',
//        );
//        $mo_db_values = $this->utilities->miniOrange_set_get_configurations($variables_and_values, 'GET');
//
//        $form['header']['#markup'] = t('<h2>Configure ' . $authMethod . '</h2><hr>');
//
//        $step_number_first = $App_Name === 'Google' ? 'Step 2:' : 'Step 1:';
//        /**
//         * Create container to hold @ScanQRCodeAndEnterPasscode form elements.
//         */
//        $form['mo_scan_qr_code_google_authenticator'] = array(
//            '#type' => 'fieldset',
//            '#title' => t($step_number_first . ' Scan below QR Code'),
//            '#attributes' => array('style' => 'padding:2% 3% 8%; margin-bottom:4%;'),
//            '#suffix ' => '<hr>',
//        );
//
//        $googleAppName = $mo_db_values['mo_auth_google_auth_app_name'] == '' ? 'miniOrangeAuth' : urldecode($mo_db_values['mo_auth_google_auth_app_name']);
//
//        $custmization_note = $App_Name === 'GOOGLE' ? '<div class="mo_2fa_highlight_background_note"><strong>Note: </strong>After scanning the below QR code, you will see the app in the Google Authenticator with the account name of <strong> ' . $googleAppName . ' </strong>. If you want to customize the account name goto <a href="' . MoAuthUtilities::get_mo_tab_url('LOGIN') . '">Login Settings</a> tab and navigate to <u>ADVANCE SETTINGS</u> section.</div><br>
//                    <div class="mo_auth_font_type">Scan the below QR Code from Google Authenticator app <strong>or</strong> use the following secret to configure the app.</div>
//                    <br>' : '';
//
//        $form['mo_scan_qr_code_google_authenticator']['actions_2'] = array(
//            '#markup' => t('<br><hr><br>' . $custmization_note)
//        );
//
//        $form['mo_scan_qr_code_google_authenticator']['actions_qrcode'] = array(
//            '#markup' => $image = isset($image) ? $image : '',
//        );
//
//        $secret = $this->utilities->indentSecret($secret);
//
//        $form['mo_scan_qr_code_google_authenticator']['actions_secret_key'] = array(
//            '#markup' => t('<div class="googleauth-secret">
//                            <p>Use the following secret</p>
//                            <p id="googleAuthSecret"><b>' . $secret . '</b></p>
//                            <p>(Spaces don&#39;t matter)</p>
//                          </div>')
//        );
//
//        $step_number_second = $App_Name === 'Google' ? 'Step 3:' : 'Step 2:';
//        $form['mo_scan_qr_code_google_authenticator']['actions_3'] = array(
//            '#markup' => t('<br><div>
//                <div class="googleauth-steps mo_configure_google_authenticator"><br><br><strong>' . $step_number_second . '</strong> ENTER THE PASSCODE GENERATED BY ' . $App_Name . ' AUTHENTICATOR APP.</div><hr>
//                </div>')
//        );
//
//        $form['mo_scan_qr_code_google_authenticator']['mo_auth_googleauth_token'] = array(
//            '#type' => 'textfield',
//            '#title' => t('Passcode:'),
//            '#maxlength' => 8,
//            '#attributes' => array(
//                'placeholder' => t('Enter passcode.'),
//                'class' => array(
//                    'mo2f-textbox',
//                ),
//                'style' => 'width:50%',
//            ),
//            '#required' => TRUE,
//            '#suffix' => '<br>',
//        );
//
//        $form['mo_scan_qr_code_google_authenticator']['secret'] = array(
//            '#type' => 'hidden',
//            '#value' => $secret
//        );
//        $form['mo_scan_qr_code_google_authenticator']['qrCode'] = array(
//            '#type' => 'hidden',
//            '#value' => $qrCode
//        );
//        $form['mo_scan_qr_code_google_authenticator']['methodToConfigure'] = array(
//            '#type' => 'hidden',
//            '#value' => $authMethod
//        );
//        $form['mo_scan_qr_code_google_authenticator']['actions'] = array(
//            '#type' => 'actions'
//        );
//        $form['mo_scan_qr_code_google_authenticator']['actions_submit'] = array(
//            '#type' => 'submit',
//            '#button_type' => 'primary',
//            '#value' => t('Verify and Save'),
//        );
//        $form['mo_scan_qr_code_google_authenticator']['actions_cancel'] = array(
//            '#type' => 'submit',
//            '#value' => t('Cancel'),
//            '#button_type' => 'danger',
//            '#submit' => array('\Drupal\miniorange_2fa\MoAuthUtilities::mo_handle_form_cancel'),
//            '#limit_validation_errors' => array(),
//            '#suffix' => '</div>'
//        );
//    }
//
//    public function render_kba_2fa_configure(array &$form, &$form_state, $authMethod)
//    {
//        $utilities = new MoAuthUtilities();
//
//        $form['mo_configure_security_questions'] = array(
//            '#type' => 'fieldset',
//            '#title' => t('Configure Security Questions (KBA)'),
//            '#attributes' => array('style' => 'padding:2% 2% 6%; margin-bottom:2%'),
//        );
//        $form['mo_configure_security_questions']['markup_configure_kba_note'] = array(
//            '#markup' => '<br><hr><br><div class="mo_2fa_highlight_background_note"><strong>' . t('You can customize the following things of the SECURITY QUESTIONS (KBA) method:') . '</strong><ul><li>' . t('Customize the set of questions. ( You can add your own set of questions )') . '</li><li>' . t('Set number of questions to be asked while login/authentication') . t(' ( Contact us for more details )') . '</li><li>' . t('For customization goto') . ' <a href="' . MoAuthUtilities::get_mo_tab_url('LOGIN') . '">' . t('Login Settings') . '</a> ' . t('tab and navigate to ') . '<u>' . t('CUSTOMIZE KBA QUESTIONS') . '</u>' . t(' section.') . '</li></ul></div>',
//        );
//        $form['mo_configure_security_questions']['mo2f_kbaquestion1'] = array(
//            '#type' => 'select',
//            '#title' => t('1. Question :'),
//            '#attributes' => array('style' => 'width:70%; height:29px'),
//            '#options' => $utilities::mo_get_kba_questions('ONE'),
//            '#prefix' => '<br>',
//        );
//
//        $form['mo_configure_security_questions']['mo2f_kbaanswer1'] = array(
//            '#type' => 'textfield',
//            '#title' => t('Answer:'),
//            '#attributes' => array('style' => 'width:70%', 'placeholder' => t('Enter your answer'),),
//            '#required' => TRUE,
//            '#suffix' => '</div><br>',
//        );
//
//        $form['mo_configure_security_questions']['mo2f_kbaquestion2'] = array(
//            '#type' => 'select',
//            '#attributes' => array('style' => 'width:70%; height:29px'),
//            '#title' => t('2. Question :'),
//            '#options' => $utilities::mo_get_kba_questions('TWO'),
//        );
//
//        $form['mo_configure_security_questions']['mo2f_kbaanswer2'] = array(
//            '#type' => 'textfield',
//            '#title' => t('Answer:'),
//            '#attributes' => array('style' => 'width:70%', 'placeholder' => t('Enter your answer'),),
//            '#required' => TRUE,
//            '#suffix' => '<br>',
//        );
//
//        $form['mo_configure_security_questions']['mo2f_kbaquestion3'] = array(
//            '#type' => 'textfield',
//            '#title' => t('3. Question:'),
//            '#attributes' => array('style' => 'width:70%', 'placeholder' => t('Enter your custom question here'),),
//            '#required' => TRUE,
//        );
//
//        $form['mo_configure_security_questions']['mo2f_kbaanswer3'] = array(
//            '#type' => 'textfield',
//            '#title' => t('Answer:'),
//            '#attributes' => array('style' => 'width:70%', 'placeholder' => t('Enter your answer'),),
//            '#required' => TRUE,
//            '#suffix' => '<br><br>',
//        );
//
//        $form['mo_configure_security_questions']['submit'] = array(
//            '#type' => 'submit',
//            '#button_type' => 'primary',
//            '#value' => t('Configure KBA'),
//        );
//
//        $form['mo_configure_security_questions']['cancel'] = array(
//            '#type' => 'submit',
//            '#value' => t('Cancel'),
//            '#button_type' => 'danger',
//            '#submit' => array('\Drupal\miniorange_2fa\MoAuthUtilities::mo_handle_form_cancel'),
//            '#limit_validation_errors' => array(),
//        );
//    }
//
//    public function render_qr_based_2fa_configure(array &$form, &$form_state, $authMethod)
//    {
//        $form['markup_library'] = array(
//            '#attached' => array(
//                'library' => array(
//                    'miniorange_2fa/miniorange_2fa.admin',
//                    'miniorange_2fa/miniorange_2fa.license',
//                ),
//            ),
//        );
//        /** To check which method ( Soft Token, QR Code, Push Notification' ) is being configured by user
//         * @messageHeader:- Title of the Page
//         */
//        if ($authMethod == AuthenticationType::$SOFT_TOKEN['code']) {
//            $messageHeader = t('Configure Soft Token');
//        } elseif ($authMethod == AuthenticationType::$PUSH_NOTIFICATIONS['code']) {
//            $messageHeader = t('Configure Push Notification');
//        } else {
//            $messageHeader = t('Configure QR Code Authentication');
//        }
//
//        global $base_url;
//        $input = $form_state->getUserInput();
//        $user = User::load(\Drupal::currentUser()->id());
//        $user_id = $user->id();
//        $utilities = new MoAuthUtilities();
//        $custom_attribute = $utilities::get_users_custom_attribute($user_id);
//
//        $form['actions'] = array(
//            '#type' => 'actions'
//        );
//
//        $moModulePath = MoAuthUtilities::moGetModulePath();
//        $androidAppLink = MoAuthUtilities::fileCreateUrl($base_url . '/' . $moModulePath . '/includes/images/android-google-authenticator-app-link.png');
//        $iPhoneAppLink = MoAuthUtilities::fileCreateUrl($base_url . '/' . $moModulePath . '/includes/images/iphone-google-authenticator-app-link.png');
//        $androidAppQR = MoAuthUtilities::fileCreateUrl($base_url . '/' . $moModulePath . '/includes/images/android-mo-authenticator-app-qr.jpg');
//        $iPhoneAppQR = MoAuthUtilities::fileCreateUrl($base_url . '/' . $moModulePath . '/includes/images/iphone-mo-authenticator-app-qr.png');
//
//        if (array_key_exists('txId', $input) === FALSE) {
//            $user_email = $custom_attribute[0]->miniorange_registered_email;
//            $customer = new MiniorangeCustomerProfile();
//            $miniorange_user = new MiniorangeUser($customer->getCustomerID(), $user_email, NULL, NULL, AuthenticationType::$QR_CODE['code']);
//            $auth_api_handler = new AuthenticationAPIHandler($customer->getCustomerID(), $customer->getAPIKey());
//            $response = $auth_api_handler->register($miniorange_user, AuthenticationType::$QR_CODE['code'], NULL, NULL, NULL);
//            $session = $utilities->getSession();
//            $moMfaSession = $session->get("mo_auth", null);
//            $moMfaSession['mo_challenge_response'] = $response;
//
//            $qrCode = isset($response->qrCode) ? $response->qrCode : '';
//            $image = new FormattableMarkup('<img style = "margin-left: 14%", src="data:image/jpg;base64, ' . $qrCode . '"/>', [':src' => $qrCode]);
//
//
//            $form['header']['#markup'] = '<h2>' . $messageHeader . '</h2><hr>';
//
//            /**
//             * Create container to hold @InstallMiniorangeAuthenticator form elements.
//             */
//            $form['mo_install_miniorange_authenticator'] = array(
//                '#type' => 'details',
//                '#title' => t('Step 1: Download & Install the miniOrange Authenticator app'),
//                //'#open' => TRUE,
//                '#attributes' => array('style' => 'padding:0% 2%; margin-bottom:2%')
//            );
//
//            $form['mo_install_miniorange_authenticator']['actions_1'] = array(
//                '#markup' => '
//                  <br>
//                  <div class="maindiv_1">
//                      <div>
//                          <div class="googleauth-download-header"><strong>' . t('Manual Installation') . '</strong></div><hr>
//                          <div class="subDivPosition" style="margin-right: 5%"><br>
//                            ' . t('<h6>iPhone Users</h6>
//                            <ul>
//                              <li>Go to App Store.</li>
//                              <li>Search for miniOrange.</li>
//                              <li>Download and install the app.<br> <b>(NOT MOAuth)</b></li>
//                            </ul>') . '
//                            <a target="_blank" href="https://apps.apple.com/app/id1482362759"><img src="' . $iPhoneAppLink . '"></a>
//                          </div>
//
//                          <div style="margin-left: 7%"><br>
//                           ' . t('<h6>Android Users</h6>
//                            <ul>
//                              <li>Go to Google Play Store.</li>
//                              <li>Search for miniOrange.</li>
//                              <li>Download and install <b> Authenticator</b> app <br> <b>(NOT miniOrange Authenticator)</b></li>
//                            </ul>') . '
//                            <div>
//                               <a target="_blank" href="https://play.google.com/store/apps/details?id=com.miniorange.android.authenticator"><img src="' . $androidAppLink . '"></a>
//                            </div>
//                          </div>
//                      </div>
//                  </div>
//                  <br><br>
//                  <div class="mo_2fa_config_option_or"><strong>OR</strong></div>
//                  <br>
//                  <div class="maindiv_2">
//                      <div class="googleauth-download-header"><strong>' . t('Scan QR Code') . '</strong></div><hr>
//
//                        <div class="subDivPosition">
//                          <div><br>
//                          <pre><img src="' . $iPhoneAppQR . '">           </pre> <!-- Do Not remove the spaces from PRE tag-->
//                          </div>
//                          <span>' . t('Apple App Store <b>(iOS)</b>') . '</span>
//                        </div>
//
//                        <div>
//                          <div><br>
//                          <pre>         <img src="' . $androidAppQR . '"></pre> <!-- Do Not remove the spaces from PRE tag-->
//                          </div>
//                          <pre>      ' . t('Google Play Store <b>(Android)</b>') . '</pre><!-- Do Not remove the spaces from PRE tag-->
//                        </div>
//                    <br><br><br></div>
//            '
//            );
//
//
//            /**
//             * Create container to hold @ScanQRCode form elements.
//             */
//            $form['mo_qr_code_miniorange_authenticator'] = array(
//                '#type' => 'fieldset',
//                '#title' => t('Step 2: Scan below QR Code'),
//                '#attributes' => array('style' => 'padding:2% 2% 8%; margin-bottom:4%; text-align: center;'),
//                '#suffix ' => '<hr>',
//            );
//            $form['mo_qr_code_miniorange_authenticator']['actions_2'] = array(
//                '#markup' => '<br><hr><div class="googleauth-steps"><br></div><div class="mo_2fa_highlight_background_note">' . t('Please scan the below QR Code from miniOrange Authenticator app and the page will load automatically.') . '</div><br><br>'
//            );
//            $form['mo_qr_code_miniorange_authenticator']['actions_qrcode'] = array(
//                '#markup' => $image,
//                '#attributes' => array('style' => 'margin-right:auto; display: block; width: 20%'),
//            );
//
//            /**
//             * Accessed form mo_authentication.js file
//             */
//            $form['mo_qr_code_miniorange_authenticator']['txId'] = array(
//                '#type' => 'hidden',
//                '#value' => $response->txId
//            );
//            $form['mo_qr_code_miniorange_authenticator']['url'] = array(
//                '#type' => 'hidden',
//                '#value' => MoAuthConstants::getBaseUrl() . MoAuthConstants::$AUTH_REGISTRATION_STATUS_API,
//            );
//            $form['mo_qr_code_miniorange_authenticator']['authTypeCode'] = array(
//                '#type' => 'hidden',
//                '#value' => $authMethod
//            );
//        }
//
//        $form['mo_qr_code_miniorange_authenticator']['actions_submit'] = array(
//            '#type' => 'submit',
//            '#value' => t('Save'), //Save
//            '#attributes' => array('class' => array('hidebutton')),
//        );
//        $form['mo_qr_code_miniorange_authenticator']['actions_cancel'] = array(
//            '#type' => 'submit',
//            '#value' => t('Cancel'),
//            '#button_type' => 'danger',
//            '#submit' => array('\Drupal\miniorange_2fa\MoAuthUtilities::mo_handle_form_cancel'),
//            '#limit_validation_errors' => array(),
//            '#attributes' => array('class' => array('mo2f_text_center')),
//            '#suffix' => '</div>',
//            '#prefix' => '<br><br>'
//        );
//    }
//
//    public function render_hardware_token_2fa_configure(array &$form, &$form_state, $authMethod)
//    {
//        $pageTitle = 'Configure Yubikey Hardware Token';
//
//        /**
//         * Create container to hold @ConfigureOTP_Over_SMS_Email_Phone form elements.
//         */
//        $form['mo_hardware_token_2fa_configure'] = array(
//            '#type' => 'fieldset',
//            '#title' => t($pageTitle),
//            '#attributes' => array('style' => 'padding:2% 2% 30% 2%; margin-bottom:2%'),
//        );
//
//        $form['mo_hardware_token_2fa_configure']['mo_configure_hardware_token'] = array(
//            '#type' => 'textfield',
//            '#title' => t('Hardware Token One Time Passcode'),
//            '#description' => t('Insert the Hardware Token in the USB Port and touch button on Hardware token.'),
//            '#attributes' => array('placeholder' => t('Enter the token'), 'autofocus' => 'true', 'autocomplete' => 'off'),
//            '#required' => TRUE,
//            '#maxlength' => 60,
//            '#prefix' => '<br><hr>'
//        );
//
//        $form['mo_hardware_token_2fa_configure']['done'] = array(
//            '#type' => 'submit',
//            '#value' => t('Submit'),
//            '#button_type' => 'primary',
//            '#suffix' => '</div>'
//        );
//
//    }
//
//    public function render_sms_based_2fa_configure(array &$form, $authMethod)
//    {
//        $utilities = new MoAuthUtilities();
//        $user = User::load(\Drupal::currentUser()->id());
//        $user_id = $user->id();
//        $custom_attribute = $utilities::get_users_custom_attribute($user_id);
//        $user_email = $custom_attribute[0]->miniorange_registered_email;
//        $phoneNumber = MoAuthUtilities::getUserPhoneNumber($user_id);
//
//        $variables_and_values = array(
//            'mo_auth_2fa_ivr_remaining',
//            'mo_auth_2fa_sms_remaining',
//            'mo_auth_2fa_email_remaining',
//        );
//        $mo_db_values = $utilities->miniOrange_set_get_configurations($variables_and_values, 'GET');
//        /**
//         * To check which method (OTP Over Email, OTP Over SMS, OTP Over Email and SMS, OTP Over Phone') is being configured by user
//         */
//        if ($authMethod == AuthenticationType::$SMS['code']) {
//            $authTypeCode = AuthenticationType::$SMS['code'];
//            $TransactionExhaust = 'SMS Transactions Remaining';
//            $pageTitle = 'Configure OTP Over SMS';
//            if($mo_db_values['mo_auth_2fa_sms_remaining'] == 0){
//                $pageTitle = $pageTitle.' <span style="color: red">('. $TransactionExhaust .'=0)</span>';
//            }
//            $mo_note = t('<ul><li>Customize SMS template.</li><li>Customize OTP Length and Validity.</li><li>For customization navigate to <u>CUSTOMIZE SMS AND EMAIL TEMPLATE</u> section under <a href="' . MoAuthUtilities::get_mo_tab_url('LOGIN') . '"> Login Settings </a> tab.</li>');
//            $method_name = strtoupper(AuthenticationType::$SMS['name']);
//        } elseif ($authMethod == AuthenticationType::$SMS_AND_EMAIL['code']) {
//            $authTypeCode = AuthenticationType::$SMS_AND_EMAIL['code'];
//            $TransactionExhaust = 'SMS/Email Transactions Remaining';
//            $pageTitle = 'Configure OTP Over SMS and Email';
//            if($mo_db_values['mo_auth_2fa_email_remaining'] != 0){
//                $pageTitle = $pageTitle.' <span style="color: red">('. $TransactionExhaust .'=0)</span>';
//            }
//            $mo_note = t('<ul><li>Customize Email template.</li><li>Customize SMS template.</li><li>Customize OTP Length and Validity.</li><li>For customization navigate to <u>CUSTOMIZE SMS AND EMAIL TEMPLATE</u> section under <a href="' . MoAuthUtilities::get_mo_tab_url('LOGIN') . '"> Login Settings </a> tab.</li>');
//            $method_name = strtoupper(AuthenticationType::$SMS_AND_EMAIL['name']);
//        } elseif ($authMethod == AuthenticationType::$OTP_OVER_PHONE['code']) {
//            $authTypeCode = AuthenticationType::$OTP_OVER_PHONE['code'];
//            $TransactionExhaust = 'IVR Transactions Remaining';
//            $pageTitle = 'Configure OTP Over Phone Call';
//            if($mo_db_values['mo_auth_2fa_ivr_remaining'] == 0){
//                $pageTitle = $pageTitle.' <span style="color: red">('. $TransactionExhaust .'=0)</span>';
//            }
//            $mo_note = '';
//            $method_name = strtoupper(AuthenticationType::$OTP_OVER_PHONE['name']);
//        }
//
//        /**
//         * Create container to hold @ConfigureOTP_Over_SMS_Email_Phone form elements.
//         */
//        $form['mo_sms_based_2fa_configure'] = array(
//            '#type' => 'fieldset',
//            '#title' => t($pageTitle),
//            '#attributes' => array('style' => 'padding:2% 2% 30% 2%; margin-bottom:2%'),
//        );
//
//
//        if ($authMethod != AuthenticationType::$OTP_OVER_PHONE['code'])
//            $form['mo_sms_based_2fa_configure']['header']['#markup'] = t('<br><hr><br><div class="mo_2fa_highlight_background_note"><strong>You can customize the following things of the ' . $method_name . ' method:</strong>' . $mo_note . '</div><br>');
//
//        if ($authMethod == AuthenticationType::$EMAIL['code'] || $authMethod == AuthenticationType::$SMS_AND_EMAIL['code']) {
//            $form['mo_sms_based_2fa_configure']['miniorange_email'] = array(
//                '#type' => 'textfield',
//                '#title' => t('Verify Your Email') . ' <span style="color: red">*</span>',
//                '#value' => $user_email,
//                '#attributes' => array(
//                    'style' => 'width:60%'
//                ),
//                '#disabled' => TRUE
//            );
//        }
//
//        $form['mo_sms_based_2fa_configure']['miniorange_phone'] = array(
//            '#type' => 'textfield',
//            '#prefix' => '<br><br>',
//            '#title' => t('Verify Your Phone number <span style="color: red">*</span>'),
//            '#id' => 'query_phone',
//            '#default_value' => $phoneNumber,
//            '#description' => t('<strong>Note:</strong> Enter phone number with country code Eg. +00xxxxxxxxxx'),
//            '#attributes' => array(
//                'class' => array('query_phone',),
//                'pattern' => '[\+]?[0-9]{1,4}\s?[0-9]{7,12}',
//                'placeholder' => t('Enter phone number with country code Eg. +00xxxxxxxxxx'),
//                'style' => 'width:60%'
//            ),
//        );
//
//        $form['mo_sms_based_2fa_configure']['verifyphone'] = array(
//            '#type' => 'submit',
//            '#value' => t('Verify'),
//            '#ajax' => [
//                'callback' => [$this, 'mo_auth_send_otp'],
//                'wrapper' => 'mo_2fa_ajax',
//                'effect' => 'none',
//            ],
//            '#button_type' => 'primary',
//        );
//
//        $form['mo_sms_based_2fa_configure']['miniorange_saml_customer_setup_resendotp'] = array(
//            '#type' => 'submit',
//            '#value' => t('Resend OTP'),
//            '#ajax' => [
//                'callback' => [$this, 'mo_auth_send_otp'],
//                'wrapper' => 'mo_2fa_ajax',
//                'effect' => 'none',
//            ],
//            '#button_type' => 'primary',
//            '#suffix' => '<br><br>',
//        );
//
//        $form['mo_sms_based_2fa_configure']['miniorange_OTP'] = array(
//            '#type' => 'textfield',
//            '#maxlength' => 8,
//            '#attributes' => array(
//                'placeholder' => t('Enter passcode'),
//                'style' => 'width:60%'
//            ),
//            '#title' => t('OTP <span style="color: red">*</span>'),
//        );
//
//        $form['mo_sms_based_2fa_configure']['authTypeCode'] = array(
//            '#type' => 'hidden',
//            '#value' => $authTypeCode
//        );
//
//        $form['mo_sms_based_2fa_configure']['miniorange_saml_customer_validate_otp_button'] = array(
//            '#type' => 'submit',
//            '#button_type' => 'primary',
//            '#value' => t('Validate OTP'),
//        );
//
//        $form['mo_sms_based_2fa_configure']['actions']['cancel'] = array(
//            '#type' => 'submit',
//            '#value' => t('Cancel'),
//            '#button_type' => 'danger',
//            '#submit' => array('\Drupal\miniorange_2fa\MoAuthUtilities::mo_handle_form_cancel'),
//            '#limit_validation_errors' => array(), //skip the required field validation
//        );
//
//        $form['main_layout_div_end'] = array(
//            '#markup' => '<br></div>',
//        );
//
//    }
//
//
//    public function mo_auth_send_otp(array &$form, FormStateInterface $form_state)
//    {
//        $customer = new MiniorangeCustomerProfile();
//        $utilities = new MoAuthUtilities();
//        $user = User::load(\Drupal::currentUser()->id());
//        $user_id = $user->id();
//
//        $custom_attribute = $utilities::get_users_custom_attribute($user_id);
//        $form_values = $form_state->getValues();
//        $phone_number = $form_values['miniorange_phone'];
//        $user_email = $custom_attribute[0]->miniorange_registered_email;
//        $customer = new MiniorangeCustomerProfile();
//        $custID = $customer->getCustomerID();
//        $api_key = $customer->getAPIKey();
//
//
//        if ($form_values['authTypeCode'] === AuthenticationType::$OTP_OVER_EMAIL['code']) {
//            $currentMethod = "OTP_OVER_EMAIL";
//            $params = array('email' => $user_email);
//            $mo_status_message = "We have sent an OTP to <strong>$user_email</strong>. Please enter the OTP to verify your email.";
//        } elseif ($form_values['authTypeCode'] === AuthenticationType::$SMS['code']) {
//            $currentMethod = "OTP_OVER_SMS";
//            $params = array('phone' => $phone_number);
//            $mo_status_message = "We have sent an OTP to <strong>$phone_number</strong>. Please enter the OTP to verify your phone number.";
//        } elseif ($form_values['authTypeCode'] === AuthenticationType::$SMS_AND_EMAIL['code']) {
//            $currentMethod = "OTP_OVER_SMS_AND_EMAIL";
//            $params = array('phone' => $phone_number, 'email' => $user_email);
//            $mo_status_message = "We have sent an OTP to <strong>$user_email</strong> and <strong>$phone_number</strong>. Please enter the OTP to verify your email and phone number.";
//        } elseif ($form_values['authTypeCode'] === AuthenticationType::$OTP_OVER_PHONE['code']) {
//            $currentMethod = "PHONE_VERIFICATION";
//            $params = array('phone' => $phone_number);
//            $mo_status_message = "You will receive phone call on <strong>$phone_number</strong> shortly, which prompts OTP. Please enter the OTP to verify your phone number.";
//        }
//
//        $customer_config = new MiniorangeCustomerSetup($user_email, $phone_number, NULL, NULL);
//        $send_otp_response = $customer_config->send_otp_token($params, $currentMethod, $custID, $api_key);
//
//        $ajax_response = new AjaxResponse();
//        if ($send_otp_response->status == 'SUCCESS') {
//            // Store txID.
//            \Drupal::configFactory()->getEditable('miniorange_2fa.settings')->set('mo_auth_tx_id', $send_otp_response->txId)->save();
//            $ajax_response->addCommand(new MessageCommand($mo_status_message));
//        } else {
//            $user_api_handler = new UsersAPIHandler($customer->getCustomerID(), $customer->getAPIKey());
//            $response = $user_api_handler->fetchLicense();
//            /**
//             * Delete this if block and fetchLicense_drupal8 function once all the Drupal 8 2FA customers shift on the Drupal 2FA plan.
//             */
//            if (is_object($response) && $response->status == 'FAILED') {
//                $response = $user_api_handler->fetchLicense_drupal8();
//            }
//
//
//            if ($response->smsRemaining == 0 || $response->emailRemaining == 0 || $response->ivrRemaining == 0) {
//                $ajax_response->addCommand(new MessageCommand('The number of OTP transactions have exhausted. Please recharge your account with SMS/Email/IVR transactions.', NULL, ['type' => 'error']));
//            } else {
//                $ajax_response->addCommand(new MessageCommand('There was an unexpected error. Please try again.'));
//                $utilities::mo_add_loggers_for_failures($send_otp_response, 'error');
//            }
//        }
//        return $ajax_response;
//    }
//
//    public function submitForm(array &$form, FormStateInterface $form_state)
//    {
//        $input = $form_state->getUserInput();
//        $form_values = $form_state->getValues();
//        $utilities = new MoAuthUtilities();
//        $customer = new MiniorangeCustomerProfile();
//        $user = User::load(\Drupal::currentUser()->id());
//        $user_id = $user->id();
//        $custom_attribute = $utilities::get_users_custom_attribute($user_id);
//        $user_email = $custom_attribute[0]->miniorange_registered_email;
//        $auth_api_handler = new AuthenticationAPIHandler($customer->getCustomerID(), $customer->getAPIKey());
//        $authMethod = $_GET['authMethod'];
//        $configured_methods = $utilities::mo_auth_get_configured_methods($this->custom_attribute);
//        $user_api_handler = new UsersAPIHandler($customer->getCustomerID(), $customer->getAPIKey());
//        $miniorange_user = new MiniorangeUser($customer->getCustomerID(), $user_email, NULL, NULL, $authMethod);
//
//
//        if (in_array($authMethod, $utilities->mo_TOTP_2fa_mentods())) {
//            $secret = preg_replace('/\s+/', '', $input['secret']);
//            $otpToken = $input['mo_auth_googleauth_token'];
//            $response = $auth_api_handler->register($miniorange_user, AuthenticationType::$GOOGLE_AUTHENTICATOR['code'], $secret, $otpToken, NULL);
//            // Clear all the messages
//            \Drupal::messenger()->deleteAll();
//            // read API response
//            if (is_object($response) && $response->status == 'SUCCESS') {
//                \Drupal::messenger()->addStatus(t(''));
//                /**
//                 * Delete all the configured TOTP methods as only one can be used at a time
//                 */
//                $configured_methods = array_values(array_diff($configured_methods, $utilities->mo_TOTP_2fa_mentods()));
//                array_push($configured_methods, $authMethod);
//
//                $config_methods = implode(', ', $configured_methods);
//                $response = $user_api_handler->update($miniorange_user);
//                if (is_object($response) && $response->status == 'SUCCESS') {
//                    // Save User
//                    $available = $utilities::check_for_userID($user_id);
//                    $database = \Drupal::database();
//                    if ($available == TRUE) {
//                        $database->update('UserAuthenticationType')->fields(['activated_auth_methods' => $authMethod, 'configured_auth_methods' => $config_methods, 'qr_code_string' => $input['qrCode']])->condition('uid', $user_id, '=')->execute();
//                    } else {
//                        \Drupal::messenger()->addError(t('Error while updating authentication method.'));
//                        return;
//                    }
//                    $message = t(ucwords(strtolower($authMethod)) . ' configured successfully.');
//                    $utilities::show_error_or_success_message($message, 'status');
//                    return;
//                }
//            } elseif (is_object($response) && $response->status == 'FAILED') {
//                \Drupal::messenger()->addError(t('The passcode you have entered is incorrect. Please try again.'));
//                return;
//            }
//            $message = t('An error occured while processing your request. Please try again.');
//            $utilities->show_error_or_success_message($message, 'error');
//        } elseif ($authMethod == AuthenticationType::$QR_CODE['code'] || $authMethod == AuthenticationType::$SOFT_TOKEN['code'] || $authMethod == AuthenticationType::$PUSH_NOTIFICATIONS['code']) {
//            $txId = $input['txId'];
//            $response = $auth_api_handler->getRegistrationStatus($txId);
//            // Clear all the messages
//            \Drupal::messenger()->deleteAll();
//            if (is_object($response) && $response->status == 'SUCCESS') {
//                /**
//                 * If one of the methods in Soft Token, QR Code Authentication, Push Notification is configured then all three methods are configured.
//                 */
//                if (!in_array(AuthenticationType::$SOFT_TOKEN['code'], $configured_methods)) {
//                    array_push($configured_methods, AuthenticationType::$SOFT_TOKEN['code']);
//                }
//                if (!in_array(AuthenticationType::$QR_CODE['code'], $configured_methods)) {
//                    array_push($configured_methods, AuthenticationType::$QR_CODE['code']);
//                }
//                if (!in_array(AuthenticationType::$PUSH_NOTIFICATIONS['code'], $configured_methods)) {
//                    array_push($configured_methods, AuthenticationType::$PUSH_NOTIFICATIONS['code']);
//                }
//
//                $config_methods = implode(', ', $configured_methods);
//
//                $updateResponse = $user_api_handler->update($miniorange_user); //update users 2FA method in miniOrange dashboard
//                if ($updateResponse->status == 'SUCCESS') {
//                    // Save user
//                    $user_id = $user->id();
//                    $available = $utilities::check_for_userID($user_id);
//                    $database = \Drupal::database();
//
//                    if ($available == TRUE) {
//                        $database->update('UserAuthenticationType')->fields(['configured_auth_methods' => $config_methods, 'activated_auth_methods' => $authMethod])->condition('uid', $user_id, '=')->execute();
//                    } else {
//                        \Drupal::messenger()->addError(t("Error while updating authentication method."));
//                        return;
//                    }
//
//                    $message = t('QR Code Authentication configured successfully.');
//                    if ($authMethod == AuthenticationType::$SOFT_TOKEN['code']) {
//                        $message = t('Soft Token configured successfully.');
//                    } elseif ($authMethod == AuthenticationType::$PUSH_NOTIFICATIONS['code']) {
//                        $message = t('Push Notifications configured successfully.');
//                    }
//                    MoAuthUtilities::show_error_or_success_message($message, 'status');
//                    return;
//                } else {
//                    $utilities->mo_add_loggers_for_failures($updateResponse->message, 'error');
//                    $utilities->redirectUserToLoginPage('An error occurred while processing your request. Please try again after sometime.');
//                }
//            }
//        } elseif ($authMethod == AuthenticationType::$KBA['code']) {
//            $qa1 = array(
//                "question" => $form_values['mo2f_kbaquestion1'],
//                "answer" => $form_values['mo2f_kbaanswer1'],
//            );
//            $qa2 = array(
//                "question" => $form_values['mo2f_kbaquestion2'],
//                "answer" => $form_values['mo2f_kbaanswer2'],
//            );
//            $qa3 = array(
//                "question" => $form_values['mo2f_kbaquestion3'],
//                "answer" => $form_values['mo2f_kbaanswer3'],
//            );
//
//            $kba = array($qa1, $qa2, $qa3);
//            $response = $auth_api_handler->register($miniorange_user, AuthenticationType::$KBA['code'], NULL, NULL, $kba);
//            // Clear all the messages
//            \Drupal::messenger()->deleteAll();
//            // read API response
//            if (is_object($response) && $response->status == 'SUCCESS') {
//                $configured_methods = $utilities::mo_auth_get_configured_methods($custom_attribute);
//                if (!in_array(AuthenticationType::$KBA['code'], $configured_methods)) {
//                    array_push($configured_methods, AuthenticationType::$KBA['code']);
//                }
//                $config_methods = implode(', ', $configured_methods);
//                $user_api_handler = new UsersAPIHandler($customer->getCustomerID(), $customer->getAPIKey());
//                $response = $user_api_handler->update($miniorange_user);
//                if (is_object($response) && $response->status == 'SUCCESS') {
//                    // Save User
//                    $user_id = $user->id();
//                    $available = $utilities::check_for_userID($user_id);
//                    $database = \Drupal::database();
//                    if ($available == TRUE) {
//                        $database->update('UserAuthenticationType')->fields(['activated_auth_methods' => AuthenticationType::$KBA['code'], 'configured_auth_methods' => $config_methods])->condition('uid', $user_id, '=')->execute();
//                    } else {
//                        \Drupal::messenger()->addError('Error while updating authentication method.');
//                        return;
//                    }
//
//                    $message = t('KBA Authentication configured successfully.');
//                    MoAuthUtilities::show_error_or_success_message($message, 'status');
//                    return;
//                }
//            } elseif (is_object($response) && $response->status == 'FAILED') {
//                $message = t('An error occurred while configuring KBA Authentication. Please try again.');
//                MoAuthUtilities::show_error_or_success_message($message, 'error');
//                return;
//            }
//            $message = t('An error occurred while processing your request. Please try again.');
//            MoAuthUtilities::show_error_or_success_message($message, 'error');
//            return;
//
//        } elseif ($authMethod == AuthenticationType::$HARDWARE_TOKEN['code']) {
//            $hardware_token = $form_values['mo_configure_hardware_token'];
//            $response = $auth_api_handler->register($miniorange_user, AuthenticationType::$HARDWARE_TOKEN['code'], NULL, $hardware_token, NULL);
//            if (is_object($response) && $response->status == 'SUCCESS') {
//                $updateResponse = $user_api_handler->update($miniorange_user); //update users 2FA method in miniOrange dashboard
//                if (is_object($updateResponse) && $updateResponse->status == 'SUCCESS') {
//                    // Save User
//                    $configured_methods = $utilities::mo_auth_get_configured_methods($custom_attribute);
//                    if (!in_array(AuthenticationType::$KBA['code'], $configured_methods)) {
//                        array_push($configured_methods, AuthenticationType::$HARDWARE_TOKEN['code']);
//                    }
//                    $config_methods = implode(', ', $configured_methods);
//                    $available = $utilities::check_for_userID($user_id);
//                    $database = \Drupal::database();
//                    if ($available == TRUE) {
//                        $database->update('UserAuthenticationType')->fields(['activated_auth_methods' => AuthenticationType::$HARDWARE_TOKEN['code'], 'configured_auth_methods' => $config_methods])->condition('uid', $user_id, '=')->execute();
//                    } else {
//                        \Drupal::messenger()->addError('Error while updating authentication method.');
//                        return;
//                    }
//                    $message = t('Hardware Token configured successfully.');
//                    MoAuthUtilities::show_error_or_success_message($message, 'status');
//                    return;
//                } else {
//                    $message = t('An error occurred while processing your request. Please try again.');
//                    MoAuthUtilities::show_error_or_success_message($message, 'error');
//                    return;
//                }
//            } elseif (is_object($response) && $response->status == 'FAILED') {
//                $message = t('An error occurred while configuring Hardware Token. Please try again.');
//                MoAuthUtilities::show_error_or_success_message($message, 'error');
//                return;
//            }
//            $message = t('An error occurred while processing your request. Please try again.');
//            MoAuthUtilities::show_error_or_success_message($message, 'error');
//            return;
//        } elseif ($authMethod == AuthenticationType::$SMS['code'] || $authMethod == AuthenticationType::$EMAIL['code'] || $authMethod == AuthenticationType::$SMS_AND_EMAIL['code'] || $authMethod == AuthenticationType::$OTP_OVER_PHONE['code'] || $authMethod == AuthenticationType::$EMAIL_VERIFICATION['code']) {
//            $otpToken = str_replace(' ', '', $form_values['miniorange_OTP']);
//
//            $phone_number = $form_values['miniorange_phone'];
//            $transactionId = \Drupal::config('miniorange_2fa.settings')->get('mo_auth_tx_id');
//            $customer = new MiniorangeCustomerProfile();
//            $cKey = $customer->getCustomerID();
//            $customerApiKey = $customer->getAPIKey();
//            $utilities = new MoAuthUtilities();
//            $custom_attribute = $utilities::get_users_custom_attribute($user_id);
//            $user_email = $custom_attribute[0]->miniorange_registered_email;
//
//            $customer_config = new MiniorangeCustomerSetup($user_email, NULL, NULL, NULL);
//            $otp_validation = $customer_config->validate_otp_token($transactionId, $otpToken, $cKey, $customerApiKey);
//            \Drupal::configFactory()->getEditable('miniorange_2fa.settings')->clear('mo_auth_tx_id')->save();
//            if ( !is_object($otp_validation) && empty( $otpToken ) ) {
//                \Drupal::messenger()->addError(t('Please enter OTP first.'));
//                return;
//            }
//            elseif (is_object($otp_validation) && !empty( $otpToken )&& $otp_validation->status == 'FAILED') {
//                \Drupal::messenger()->addError(t("Validation Failed. Please enter the correct OTP."));
//                return;
//            } elseif (is_object($otp_validation) && $otp_validation->status == 'SUCCESS') {
//                $form_state->setRebuild();
//                if (!in_array($authMethod, $configured_methods)) {
//                    array_push($configured_methods, $authMethod);
//                }
//
//                $config_methods = implode(', ', $configured_methods);
//                $user_api_handler = new UsersAPIHandler($customer->getCustomerID(), $customer->getAPIKey());
//
//                // Updating the authentication method for the user
//                $miniorange_user->setAuthType($authMethod);
//                $response = $user_api_handler->update($miniorange_user);
//
//                if (is_object($response) && $response->status == 'SUCCESS') {
//                    // Save User
//                    $user_id = $user->id();
//                    $available = $utilities::check_for_userID($user_id);
//                    $database = \Drupal::database();
//
//                    if ($available == TRUE) {
//                        $database->update('UserAuthenticationType')->fields(['activated_auth_methods' => $authMethod, 'configured_auth_methods' => $config_methods, 'phone_number' => $phone_number])->condition('uid', $user_id, '=')->execute();
//                    } else {
//                        \Drupal::messenger()->addError(t("Error while updating authentication method."));
//                        return;
//                    }
//
//                    if ($authMethod == AuthenticationType::$OTP_OVER_EMAIL['code']) {
//                        $message = t('OTP Over Email has been configured successfully.');
//                    } elseif ($authMethod == AuthenticationType::$SMS['code']) {
//                        $message = t('OTP Over SMS has been configured successfully.');
//                    } elseif ($authMethod == AuthenticationType::$SMS_AND_EMAIL['code']) {
//                        $message = t('OTP Over SMS and Email has been configured successfully.');
//                    } elseif ($authMethod == AuthenticationType::$OTP_OVER_PHONE['code']) {
//                        $message = t('OTP Over Phone Call has been configured successfully.');
//                    }
//
//                    MoAuthUtilities::show_error_or_success_message($message, 'status');
//                    return;
//                }
//                return;
//            }
//            $message = t('An error occurred while processing your request. Please try again.');
//            MoAuthUtilities::show_error_or_success_message($message, 'error');
//        }
//    }
//}
