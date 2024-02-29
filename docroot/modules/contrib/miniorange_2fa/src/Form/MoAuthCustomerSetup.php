<?php
/**
 * @file
 * Contains form for customer setup.
 */

namespace Drupal\miniorange_2fa\Form;

use Drupal\Core\Url;
use Drupal\user\Entity\User;
use Drupal\Core\Render\Markup;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\miniorange_2fa\MiniorangeUser;
use Drupal\miniorange_2fa\UsersAPIHandler;
use Drupal\miniorange_2fa\MoAuthUtilities;
use Drupal\miniorange_2fa\MoAuthConstants;
use Drupal\miniorange_2fa\AuthenticationType;
use Drupal\miniorange_2fa\MiniorangeCustomerSetup;
use Drupal\miniorange_2fa\MiniorangeCustomerProfile;

/**
 * Customer setup form().
 */
class MoAuthCustomerSetup extends FormBase
{
    public function getFormId()
    {
        return 'miniorange_2fa_customer_setup';
    }

    public function buildForm(array $form, FormStateInterface $form_state)
    {

        global $base_url;
        $user_obj = User::load(\Drupal::currentUser()->id());
        $user_id = $user_obj->id();
        $current_status = \Drupal::config('miniorange_2fa.settings')->get('mo_auth_status');
        $form['markup_library'] = array(
            '#attached' => array(
                'library' => array(
                    'miniorange_2fa/miniorange_2fa.license',
                    "miniorange_2fa/miniorange_2fa.admin",
                    "core/drupal.dialog.ajax",
                ),
            ),);

        if ($current_status == 'VALIDATE_OTP') {

            $form['markup_top_2'] = array(
                '#markup' => '<div class="mo_2fa_table_layout_1"><div class="mo_2fa_table_layout mo_2fa_container">'
            );

            /**
             * Create container to hold @Login form elements.
             */
            $form['mo_login_form'] = array(
                '#type' => 'fieldset',
                '#title' => t('Verify OTP'),
                '#attributes' => array('style' => 'padding:2% 2% 30% 2%; margin-bottom:2%'),
            );

            $form['mo_login_form']['mo_auth_customer_otp_token'] = array(
                '#type' => 'textfield',
                '#title' => t('Please enter the OTP you received'),
                '#attributes' => array('autofocus' => 'true'),
                '#required' => TRUE,
                '#description' => '<strong>' . t('Note:') . '</strong> ' . t('We have sent an OTP to') . ' <strong><em>' . \Drupal::config('miniorange_2fa.settings')->get('mo_auth_customer_admin_email') . '</em></strong>. '. t('Please enter the OTP to verify your email.'),
                '#maxlength' => 8,
                '#prefix' => '<br><hr><br>',
                '#suffix' => '<br>',
            );
            $form['mo_login_form']['mo_auth_customer_validate_otp_button'] = array(
                '#type' => 'submit',
                '#button_type' => 'primary',
                '#value' => t('Validate OTP'),
                '#submit' => array('::mo_auth_validate_otp_submit'),
            );
            $form['mo_login_form']['Mo_auth_customer_setup_resendotp'] = array(
                '#type' => 'submit',
                '#value' => t('Resend OTP'),
                '#limit_validation_errors' => array(),
                '#submit' => array('::mo_auth_resend_otp'),
            );
            $form['mo_login_form']['Mo_auth_customer_setup_back'] = array(
                '#type' => 'submit',
                '#button_type' => 'danger',
                '#value' => t('Back'),
                '#limit_validation_errors' => array(),
                '#submit' => array('::mo_auth_back'),
            );

            $form['mo_login_form']['main_layout_div_end'] = array(
                '#markup' => '<br></div>',
            );

            MoAuthUtilities::miniOrange_know_more_about_2fa($form, $form_state);

            return $form;

        } elseif ($current_status == 'PLUGIN_CONFIGURATION') {

            $utilities = new MoAuthUtilities();
            $custom_attribute = $utilities::get_users_custom_attribute($user_id);
            $user_email = isset($custom_attribute[0]) && is_object($custom_attribute[0]) ? $custom_attribute[0]->miniorange_registered_email : '-';
            $customer = new MiniorangeCustomerProfile();
            $user_api_handler = new UsersAPIHandler($customer->getCustomerID(), $customer->getAPIKey());
            $miniorange_user = new MiniorangeUser($customer->getCustomerID(), $user_email, '', '', '');
            $response = $user_api_handler->get($miniorange_user);
            $authType = AuthenticationType::getAuthType(is_object($response) && $response->status != 'FAILED' ? $response->authType : '-');

            $variables_and_values = array(
                'mo_user_limit_exceed',
                'mo_auth_customer_admin_email',
                'mo_auth_customer_id',
                'mo_auth_customer_api_key',
                'mo_auth_customer_token_key',
                'mo_auth_customer_app_secret',
                'mo_auth_2fa_license_type',
                'mo_auth_2fa_license_plan',
                'mo_auth_2fa_license_no_of_users',
                'mo_auth_2fa_ivr_remaining',
                'mo_auth_2fa_sms_remaining',
                'mo_auth_2fa_email_remaining',
                'mo_auth_2fa_license_expiry',
                'mo_auth_2fa_support_expiry',
            );
            $mo_db_values = $utilities->miniOrange_set_get_configurations($variables_and_values, 'GET');

            $form['markup_top_1'] = array(
                '#markup' => '<div class="mo_2fa_table_layout_1"><div id="Register_Section" class="mo_2fa_table_layout mo_2fa_container">'
            );

            /** Show message if user creation limit exceeded */
            $mo_user_limit = $mo_db_values['mo_user_limit_exceed'];
            if (isset($mo_user_limit) && $mo_user_limit == TRUE) {
                $form['markup_top_2'] = array(
                    '#markup' => '<div class="users_2fa_limit_exceeded_message">' . t('Your user creation limit has been completed. Please upgrade your license to add more users. Please ignore if already upgraded.') . ' </div>'
                );
            }

            $form['markup_top'] = array(
                '#markup' => '<div class="mo_2fa_welcome_message">' . t('Thank you for registering with miniOrange') . '</div>'
            );

            $form['mo_profile_information'] = array(
                '#type' => 'details',
                '#title' => t('Profile Details'),
                '#attributes' => array('style' => 'margin-bottom:2%;'),
                //'#open' => TRUE,
            );

            $mo_table_content = array(
                array('2 Factor Registered Email', $mo_db_values['mo_auth_customer_admin_email']),
                array('Activated 2nd Factor', isset($authType['name']) ? $authType['name'] : ''),
                array('Xecurify Registered Email', $user_email),
                array('Customer ID', $mo_db_values['mo_auth_customer_id']),
                //array( 'Token Key', $mo_db_values['mo_auth_customer_token_key'] ), //TODO: Remove this after 3.08 release
                //array( 'App Secret', $mo_db_values['mo_auth_customer_app_secret'] ), //TODO: Remove this after 3.08 release
                array('Drupal Version', MoAuthUtilities::mo_get_drupal_core_version()),
                array('PHP Version', phpversion()),
            );

            $form['mo_profile_information']['miniorange_testing_form_element'] = array(
                '#type' => 'table',
                '#header' => array('ATTRIBUTE', 'VALUE'),
                '#rows' => $mo_table_content,
                '#empty' => t('Something is not right. Please run the update script or contact us at') . '<a href="mailto:drupalsupport@xecurify.com">drupalsupport@xecurify.com</a>',
                '#responsive' => TRUE,
                '#sticky' => FALSE, //headers will move with the scroll
                '#size' => 2,
            );

            $form['mo_profile_information']['miniorange_customer_Remove_Account_info'] = array(
                '#markup' => '<br/><h3>Remove Account:</h3><p>This section will help you to remove your current logged in account without losing your current configurations.</p>'
            );

            $form['mo_profile_information']['miniorage_remove_account'] = array(
                '#type' => 'link',
                '#title' => $this->t('Remove Account'),
                '#url' => Url::fromRoute('miniorange_2fa.modal_form'),
                '#attributes' => [
                    'class' => [
                        'use-ajax',
                        'button',
                    ],
                ],
                '#suffix' => '<br/><br/>',
            );

            $form['mo_license_information'] = array(
                '#type' => 'fieldset',
                '#title' => t('License info'),
                '#attributes' => array('style' => 'padding:2% 2%; margin-bottom:7%'),
            );

            $isLicenseExpired = MoAuthUtilities::getIsLicenseExpired($mo_db_values['mo_auth_2fa_license_expiry']);

            $cron_run_interval = \Drupal::config('automated_cron.settings')->get('interval');
            $cron_run_interval = $cron_run_interval !=0 ? \Drupal::service('date.formatter')->formatInterval($cron_run_interval) : 'Never';
            $last_cron_run     = \Drupal::state()->get('system.cron_last');
            $last_cron_run     = \Drupal::service('date.formatter')->formatTimeDiffSince($last_cron_run);
            $cron_message      = MoAuthUtilities::getCronInformation();

            $NoofUsers = '';
            if (isset($mo_db_values['mo_auth_2fa_license_type']) && $mo_db_values['mo_auth_2fa_license_type'] == MoAuthConstants::$LICENSE_TYPE) {
                $NoofUsers = [
                    'data' => Markup::create('</span><a class="js-form-submit form-submit use-ajax" href="contact_us">ADD MORE USERS</a>')
                ];
            }

            $updateLicense = '';
            if ($isLicenseExpired['LicenseGoingToExpire']) {
                $updateLicense = [
                    'data' => Markup::create('</span><a class="js-form-submit form-submit use-ajax" href="contact_us">ADD MORE USERS</a>')
                ];
            }

            $mo_license_table_content = array(
                array('License Type', $mo_db_values['mo_auth_2fa_license_type'], ''),
                array('License Plan', $mo_db_values['mo_auth_2fa_license_plan'], ''),
                array('No. of Users', $mo_db_values['mo_auth_2fa_license_no_of_users'], $NoofUsers),
            );
            $mo_license_table_content_2 = array(
                array('IVR Transactions Remaining', $mo_db_values['mo_auth_2fa_ivr_remaining'], ''),
                array('SMS Transactions Remaining', $mo_db_values['mo_auth_2fa_sms_remaining'], ''),
                array('Email Transactions Remaining', $mo_db_values['mo_auth_2fa_email_remaining'], ''),
                array('License Expiry', $mo_db_values['mo_auth_2fa_license_expiry'], $updateLicense),
                array('Support Expiry', $mo_db_values['mo_auth_2fa_support_expiry'], ''),
                array('Cron Run Interval', $cron_message, ''),
            );


            if ($mo_db_values['mo_auth_2fa_license_type'] !== 'DEMO') {
                $mo_license_table_content = array_merge($mo_license_table_content, $mo_license_table_content_2);
            }

            $form['mo_license_information']['miniorange_hidden_value'] = array(
                '#type' => 'hidden',
                '#value' => 'User_Logged_in',
            );

            $form['mo_license_information']['miniorange_customer-license'] = array(
                '#type' => 'table',
                '#header' => array('ATTRIBUTE', 'VALUE', 'ACTION'),
                '#rows' => $mo_license_table_content,
                '#empty' => t('Something is not right. Please run the update script or contact us at') . ' <a href="mailto:drupalsupport@xecurify.com">drupalsupport@xecurify.com</a>',
                '#responsive' => TRUE,
                '#sticky' => FALSE, //headers will move with the scroll
                '#size' => 2,
                '#prefix' => '<br>',
                '#suffix' => '<br>',
            );

            $form['mo_license_information']['fecth_customer_license'] = array(
                '#type' => 'submit',
                '#button_type' => 'primary',
                '#value' => t('Check License'),
                '#submit' => array('::mo_auth_fetch_customer_license'),
                //'#suffix' => '</div>',
            );

            if ($mo_db_values['mo_auth_2fa_license_type'] === 'DEMO') {
                $form['mo_license_information']['miniorage_request_demo'] = array(
                    '#type' => 'link',
                    '#title' => $this->t('Request 7 Days Trial'),
                    '#url' => Url::fromRoute('miniorange_2fa.request_demo'),
                    '#attributes' => [
                        'class' => [
                            'use-ajax',
                            'button',
                        ],
                    ],
                );
            }

            $form['mo_license_information']['markup_end'] = array(
                '#markup' => '<br/><br/></div>'
            );

            MoAuthUtilities::miniOrange_know_more_about_2fa($form, $form_state);

            return $form;
        }

        $url = $base_url . '/admin/config/people/miniorange_2fa/customer_setup';
        $tab = isset($_GET['tab']) && $_GET['tab'] == 'login' ? $_GET['tab'] : 'register';

        $form['markup_start'] = array(
            '#markup' => '<div class="mo_2fa_table_layout_1"><div class="mo_2fa_table_layout mo_2fa_container">
                         '
        );

        if ($tab == 'register') {
            /**
             * Create container to hold @Register form elements.
             */
            $form['mo_register_form'] = array(
                '#type' => 'fieldset',
                '#title' => t('Register/Login with miniOrange'),
                '#attributes' => array('style' => 'padding:2%; margin-bottom:2%'),
            );
            $form['mo_register_form']['markup_msg_1'] = array(
                '#markup' => '<br><hr><br>
                            <div class="mo_2fa_highlight_background_note">' . t('To configure the Two-Factor Authentication Module, complete the short registration below with a valid email ID. Proceed after verifying the OTP we send to this email.') . '</div>
                        '
            );

            $form['mo_register_form']['Mo_auth_customer_register_username'] = array(
                '#type' => 'textfield',
                '#id' => "email_id",
                '#title' => t('Email') . '<span style="color: red">*</span>',
                '#description' => t('<b>Note:</b> Please enter valid Email ID . (We discourage the use of disposable emails)'),
                '#attributes' => array(
                    'autofocus' => 'true',
                    'style' => 'width:60%;'
                ),
            );

            $form['mo_register_form']['Mo_auth_customer_register_password'] = array(
                '#type' => 'password_confirm',
            );

            $form['mo_register_form']['Mo_auth_customer_register_button'] = array(
                '#type' => 'submit',
                '#value' => t('Register'),
                '#limit_validation_errors' => array(),
                '#prefix' => '<br><div class="ns_row"><div class="ns_name">',
                '#suffix' => '</div>'
            );

            $form['mo_register_form']['already_account_link'] = array(
                '#markup' => '<a href="' . $url . '/?tab=login" class="button button--primary"><b>' . t('Already have an account?') . '</b></a>',
                '#prefix' => '<div class="ns_value">',
                '#suffix' => '</div></div><br><br></div>'
            );
        } else {
            /**
             * Create container to hold @Login form elements.
             */

            $form['mo_login_form'] = array(
                '#type' => 'fieldset',
                '#title' => t('Login with miniOrange'),
                '#attributes' => array('style' => 'padding:2% 2% 15% 2%; margin-bottom:2%'),
            );

            $form['mo_login_form']['markup_16'] = array(
                '#markup' => '<br><hr><br>
                              <div class="mo_2fa_highlight_background_note" style="width:35% !important;">' . t('Please login with your miniOrange account.') . '</br></div>'
            );

            $form['mo_login_form']['Mo_auth_customer_login_username'] = array(
                '#type' => 'email',
                '#title' => t('Email') . ' <span style="color: red">*</span>',
                '#attributes' => array('style' => 'width:50%'),
            );

            $form['mo_login_form']['Mo_auth_customer_login_password'] = array(
                '#type' => 'password',
                '#title' => t('Password') . ' <span style="color: red">*</span>',
                '#attributes' => array('style' => 'width:50%'),
            );

            $form['mo_login_form']['Mo_auth_customer_login_button'] = array(
                '#type' => 'submit',
                '#value' => t('Login'),
                '#limit_validation_errors' => array(),
                '#prefix' => '<br><div class="ns_row"><div class="ns_name">',
                '#suffix' => '</div>'
            );

            $form['mo_login_form']['register_link'] = array(
                '#markup' => '<a href="' . $url . '" class="button button--primary"><b>' . t('Create an account?') . '</b></a>',
                '#prefix' => '<div class="ns_value">',
                '#suffix' => '</div></div><br></div>'
            );
        }

        MoAuthUtilities::miniOrange_know_more_about_2fa($form, $form_state);

        return $form;
    }

    public function validateForm(array &$form, FormStateInterface $form_state)
    {
        $form_values = $form_state->getValues();

        if (isset($form_values['Mo_auth_customer_register_username']) && !\Drupal::service('email.validator')->isValid(trim($form_values['Mo_auth_customer_register_username']))
            && !isset($form_values['mo_auth_customer_otp_token'])
            && !isset($form_values['Mo_auth_customer_login_username'])
            && !isset($form_values['miniorange_hidden_value'])) {
            $form_state->setErrorByName('Mo_auth_customer_register_username', $this->t('The email address is not valid.'));
        }
    }

    //Handle submit for customer setup.
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        global $base_url;
        $check_loggers = MoAuthUtilities::get_mo_tab_url('LOGS');
        $user = User::load(\Drupal::currentUser()->id());
        $user_id = $user->id();

        $tab = isset($_GET['tab']) ? $_GET['tab'] : 'register';

        if ($tab == 'register') {
            $username = trim($form['mo_register_form']['Mo_auth_customer_register_username']['#value']);
            $phone = '';
            $password = trim($form['mo_register_form']['Mo_auth_customer_register_password']['#value']['pass1']);
        } else {
            $username = trim($form['mo_login_form']['Mo_auth_customer_login_username']['#value']);
            $password = trim($form['mo_login_form']['Mo_auth_customer_login_password']['#value']);
            $phone = '';
        }

        $customer_config = new MiniorangeCustomerSetup($username, $phone, $password, NULL);

        $check_customer_response = $customer_config->checkCustomer();
        $utilities = new MoAuthUtilities();
        if (is_object($check_customer_response) && $check_customer_response->status == 'CUSTOMER_NOT_FOUND') {
            if ($tab == 'login') {
                \Drupal::messenger()->addError(t('The account with username <strong>@username</strong> does not exist.', array('@username' => $username)));
                return;
            }
            // Create customer.
            // Store email and phone.
            $variables_and_values = array(
                'mo_auth_customer_admin_email' => $username,
                'mo_auth_customer_admin_phone' => $phone,
                'mo_auth_customer_admin_password' => $password,
            );

            $utilities->miniOrange_set_get_configurations($variables_and_values, 'SET');

            $send_otp_response = $customer_config->sendOtp();
            if ($send_otp_response->status == 'SUCCESS') {
                // Store txID.
                $variables_and_values_2 = array(
                    'mo_auth_tx_id' => $send_otp_response->txId,
                    'mo_auth_status' => 'VALIDATE_OTP'
                );
                $utilities->miniOrange_set_get_configurations($variables_and_values_2, 'SET');
                \Drupal::messenger()->addStatus(t('We have sent an OTP to <strong>@username</strong>. Please enter the OTP to verify your email.', array('@username' => $username)));
            } else if ($send_otp_response->status == 'FAILED') {
                MoAuthUtilities::mo_add_loggers_for_failures($check_customer_response->message, 'error');
                \Drupal::messenger()->addError(t('Failed to send an OTP. Please check your internet connection.') . ' <a href="' . $check_loggers . ' " target="_blank">' . t('Click here') . ' </a>' . t('for more details.'));
                return;
            }
        } elseif (is_object($check_customer_response) && $check_customer_response->status == 'SUCCESS' && $check_customer_response->message == 'Customer already exists.') {
            // Customer exists. Retrieve keys.
            $customer_keys_response = $customer_config->getCustomerKeys();
            if (json_last_error() == JSON_ERROR_NONE) {
                $this->mo_auth_save_customer($user_id, $customer_keys_response, $username, $phone);
                \Drupal::messenger()->addStatus(t('Your account has been retrieved successfully.'));
            } else {
                \Drupal::messenger()->addError(t('Invalid credentials'));
                return;
            }
        } elseif (is_object($check_customer_response) && $check_customer_response->status == 'TRANSACTION_LIMIT_EXCEEDED') {
            MoAuthUtilities::mo_add_loggers_for_failures($check_customer_response->message, 'error');
            \Drupal::messenger()->addError(t('Failed to send an OTP. Please check your internet connection.') . ' <a href="' . $check_loggers . ' " target="_blank">' . t('Click here') . ' </a>' . t('for more details.'));
            return;

        } elseif (is_object($check_customer_response) && $check_customer_response->status == 'CURL_ERROR') {
            \Drupal::messenger()->addError(t('cURL is not enabled. Please enable cURL'));
            return;
        } else {
            MoAuthUtilities::mo_add_loggers_for_failures(isset($check_customer_response->message) ? $check_customer_response->message : '', 'error');
            \Drupal::messenger()->addError(t('Something went wrong, Please try again. Click <a href="' . $check_loggers . ' " target="_blank"> here </a> for more details.'));
            return;
        }
    }

    // Validate OTP.

    function mo_auth_save_customer($user_id, $json, $username, $phone)
    {

        $utilities = new MoAuthUtilities();
        $variables_and_values = array(
            'mo_auth_customer_admin_email' => $username,
            'mo_auth_customer_admin_phone' => $phone,
            'mo_auth_customer_id' => isset($json->id) ? $json->id : '',
            'mo_auth_customer_api_key' => isset($json->apiKey) ? $json->apiKey : '',
            'mo_auth_customer_token_key' => isset($json->token) ? $json->token : '',
            'mo_auth_customer_app_secret' => isset($json->appSecret) ? $json->appSecret : '',
        );
        $utilities->miniOrange_set_get_configurations($variables_and_values, 'SET');

        //Stores the user id of the user who activates the module.
        $MiniorangeUser = array(
            'mo_auth_firstuser_id' => \Drupal::currentUser()->id(),
        );
        $utilities->miniOrange_set_get_configurations($MiniorangeUser, 'SET');

        $auth_method = AuthenticationType::$EMAIL['code'] . ', ' . AuthenticationType::$EMAIL_VERIFICATION['code'];
        $available = $utilities::check_for_userID($user_id);
        $database = \Drupal::database();
        $fields = array(
            'uid' => $user_id,
            'configured_auth_methods' => $auth_method,
            'miniorange_registered_email' => $username,
        );

        if ($available == FALSE) {
            $database->insert('UserAuthenticationType')->fields($fields)->execute();
        } elseif ($available == TRUE) {
            $database->update('UserAuthenticationType')->fields(['miniorange_registered_email' => $username])->condition('uid', $user_id, '=')->execute();
        }

        $utilities->miniOrange_set_get_configurations(array('mo_auth_status' => 'PLUGIN_CONFIGURATION'), 'SET');

        // Update the customer second factor to OTP Over Email in miniOrange
        $customer = new MiniorangeCustomerProfile();
        $miniorange_user = new MiniorangeUser($customer->getCustomerID(), $username, '', '', AuthenticationType::$EMAIL['code']);
        $user_api_handler = new UsersAPIHandler($customer->getCustomerID(), $customer->getAPIKey());
        $user_api_handler->update($miniorange_user);
        $license_response = $user_api_handler->fetchLicense();

        $license_type = 'DEMO';
        $license_plan = 'DEMO';
        $no_of_users = 1;

        if (is_object($license_response) && $license_response->status == 'CURL_ERROR') {
            \Drupal::messenger()->addError(t('cURL is not enabled. Please enable cURL'));
            return;
        } elseif (is_object($license_response) && isset($license_response->licenseExpiry) && $utilities->license_expired($license_response->licenseExpiry) && $license_response->status == 'SUCCESS') {
            $license_type = $license_response->licenseType;
            /**Delete the OR part once all the Drupal 8 2FA customers shift on the Drupal 2FA plan.*/

            if ($license_type == MoAuthConstants::$LICENSE_TYPE || $license_type == 'DRUPAL8_2FA_MODULE') {
                $license_plan = $license_response->licensePlan;
            }
            $no_of_users = $license_response->noOfUsers;
        }

        $mo_db_values = $utilities->miniOrange_set_get_configurations(array('mo_auth_enable_two_factor'), 'GET');

        $variables_and_values_2 = array(
            'mo_auth_2fa_license_type' => $license_type,
            'mo_auth_2fa_license_plan' => $license_plan,
            'mo_auth_2fa_license_no_of_users' => $no_of_users,
            'mo_auth_2fa_ivr_remaining' => isset($license_response->ivrRemaining) ? $license_response->ivrRemaining : '-',
            'mo_auth_2fa_sms_remaining' => isset($license_response->smsRemaining) ? $license_response->smsRemaining : '-',
            'mo_auth_2fa_email_remaining' => isset($license_response->emailRemaining) ? $license_response->emailRemaining : '-',
            'mo_auth_2fa_license_expiry' => isset($license_response->licenseExpiry) ? date('Y-M-d H:i:s', strtotime($license_response->licenseExpiry)) : '-',
            'mo_auth_2fa_support_expiry' => isset($license_response->supportExpiry) ? date('Y-M-d H:i:s', strtotime($license_response->supportExpiry)) : '-',
            'mo_auth_enable_two_factor' => $mo_db_values['mo_auth_enable_two_factor'] == '' ? TRUE : $mo_db_values['mo_auth_enable_two_factor'],
            'mo_auth_enforce_inline_registration' => $license_type == 'DEMO' ? FALSE : TRUE,
        );
        $utilities->miniOrange_set_get_configurations($variables_and_values_2, 'SET');
    }

    // Resend OTP.

    function mo_auth_validate_otp_submit(&$form, FormStateInterface $form_state)
    {
        $utilities = new MoAuthUtilities();

        $variables = array(
            'mo_auth_customer_admin_email',
            'mo_auth_customer_admin_phone',
            'mo_auth_tx_id',
            'mo_auth_customer_admin_password'
        );
        $mo_db_values = $utilities->miniOrange_set_get_configurations($variables, 'GET');

        $user = User::load(\Drupal::currentUser()->id());
        $user_id = $user->id();
        $otp_token = $form_state->getValue('mo_auth_customer_otp_token');
        if (empty($otp_token)) {
            \Drupal::messenger()->addError(t('The <b>OTP</b> field is mandatory.'));
            return;
        }
        $username = ($mo_db_values['mo_auth_customer_admin_email'] == '') ? NULL : $mo_db_values['mo_auth_customer_admin_email'];
        $phone = ($mo_db_values['mo_auth_customer_admin_phone'] == '') ? NULL : $mo_db_values['mo_auth_customer_admin_phone'];
        $txId = ($mo_db_values['mo_auth_tx_id'] == '') ? NULL : $mo_db_values['mo_auth_tx_id'];
        $customerSetup = new MiniorangeCustomerSetup($username, $phone, NULL, $otp_token);

        // Validate OTP.
        $validate_otp_response = $customerSetup->validate_otp_token($txId, $otp_token, MoAuthConstants::$DEFAULT_CUSTOMER_ID, MoAuthConstants::$DEFAULT_CUSTOMER_API_KEY);

        if ($validate_otp_response->status == 'CURL_ERROR') {
            \Drupal::messenger()->addError(t('cURL is not enabled. Please enable cURL'));
            return;
        } elseif ($validate_otp_response->status == 'SUCCESS') {
            // OTP Validated. Create customer.
            $password = $mo_db_values['mo_auth_customer_admin_password'];
            $customer_config = new MiniorangeCustomerSetup($username, $phone, $password, NULL);
            $create_customer_response = $customer_config->createCustomer();

            if ($create_customer_response->status == 'CURL_ERROR') {
                \Drupal::messenger()->addError(t('cURL is not enabled. Please enable cURL'));
                return;
            } elseif ($create_customer_response->status == 'SUCCESS') {
                // OTP Validated. Show Configuration page.
                $utilities->miniOrange_set_get_configurations(array('mo_auth_status' => 'PLUGIN_CONFIGURATION'), 'SET');
                $utilities->miniOrange_set_get_configurations(array('mo_auth_tx_id'), 'CLEAR');
                // Customer created.
                $this->mo_auth_save_customer($user_id, $create_customer_response, $username, $phone);
                \Drupal::messenger()->addStatus(t('Your account has been created successfully. Email Verification has been set as your default 2nd-factor method.'));
                return;
            } elseif ($create_customer_response->status == 'INVALID_EMAIL_QUICK_EMAIL') {
                \Drupal::messenger()->addError(t('There was an error creating an account for you.<br> You may have entered an invalid Email-Id
                <strong>(We discourage the use of disposable emails) </strong>
                <br>Please try again with a valid email.'));
                return;
            } else {
                MoAuthUtilities::mo_add_loggers_for_failures($create_customer_response->message, 'error');
                \Drupal::messenger()->addError(t('An error occurred while creating your account. Please try again or contact us at' . ' <a href="mailto:info@xecurify.com">info@xecurify.com</a>.'));
                return;
            }
        } else {
            \Drupal::messenger()->addError(t('The OTP you have entered is incorrect. Please try again.'));
            return;
        }
    }

    function mo_auth_resend_otp(&$form, $form_state)
    {
        $utilities = new MoAuthUtilities();
        $utilities->miniOrange_set_get_configurations(array('mo_auth_tx_id'), 'CLEAR');
        $variables = array(
            'mo_auth_customer_admin_email',
            'mo_auth_customer_admin_phone',
        );
        $mo_db_values = $utilities->miniOrange_set_get_configurations($variables, 'GET');
        $username = $mo_db_values['mo_auth_customer_admin_email'];
        $phone = $mo_db_values['mo_auth_customer_admin_phone'];
        $customer_config = new MiniorangeCustomerSetup($username, $phone, NULL, NULL);
        $send_otp_response = $customer_config->sendOtp();
        if ($send_otp_response->status == 'SUCCESS') {
            // Store txID.
            $variables_2 = array(
                'mo_auth_tx_id' => $send_otp_response->txId,
                'mo_auth_status' => 'VALIDATE_OTP',
            );
            $utilities->miniOrange_set_get_configurations($variables_2, 'SET');
            \Drupal::messenger()->addStatus(t('We have sent an OTP to <strong>@username</strong>. Please enter the OTP to verify your email.', array('@username' => $username)));
            return;
        } elseif ($send_otp_response->status == 'CURL_ERROR') {
            \Drupal::messenger()->addError(t('cURL is not enabled. Please enable cURL'));
            return;
        }
    }

    /**
     * Handle back button submit for customer
     * setup.
     */
    function mo_auth_back($form, &$form_state)
    {
        MoAuthUtilities::miniOrange_set_get_configurations(array('mo_auth_status' => 'CUSTOMER_SETUP'), 'SET');
        $variables = array(
            'mo_auth_customer_admin_email',
            'mo_auth_customer_admin_phone',
            'mo_auth_tx_id',
        );
        MoAuthUtilities::miniOrange_set_get_configurations($variables, 'CLEAR');
        \Drupal::messenger()->addStatus(t('Register/Login with your miniOrange Account'));
        return;
    }

    function mo_auth_fetch_customer_license($form, &$form_state, $triggered_element = 'FORM')
    {
        //$check_loggers    = MoAuthUtilities::get_mo_tab_url('LOGS');
        $utilities = new MoAuthUtilities();
        $customer = new MiniorangeCustomerProfile();
        $user_api_handler = new UsersAPIHandler($customer->getCustomerID(), $customer->getAPIKey());
        $response = $user_api_handler->fetchLicense();

        /**
         * Check if license is expired or not found
         */
        if (is_object($response) && $response->status == 'SUCCESS' && $utilities->license_expired($response->licenseExpiry)) {
            $license_type = $response->licenseType;
            /**Delete the OR part once all the Drupal 8 2FA customers shift on the Drupal 2FA plan.*/
            $license_plan = $license_type == MoAuthConstants::$LICENSE_TYPE || $license_type == 'DRUPAL8_2FA_MODULE' ? $response->licensePlan : 'DEMO';
            $no_of_users = $response->noOfUsers;

            $variables_and_values = array(
                'mo_auth_2fa_license_type' => $license_type,
                'mo_auth_2fa_license_plan' => $license_plan,
                'mo_auth_2fa_license_no_of_users' => $no_of_users,
                'mo_auth_2fa_ivr_remaining' => isset($response->ivrRemaining) ? $response->ivrRemaining : '-',
                'mo_auth_2fa_sms_remaining' => isset($response->smsRemaining) ? $response->smsRemaining : '-',
                'mo_auth_2fa_email_remaining' => isset($response->emailRemaining) ? $response->emailRemaining : '-',
                'mo_auth_2fa_license_expiry' => isset($response->licenseExpiry) ? date('Y-M-d H:i:s', strtotime($response->licenseExpiry)) : '-',
                'mo_auth_2fa_support_expiry' => isset($response->supportExpiry) ? date('Y-M-d H:i:s', strtotime($response->supportExpiry)) : '-',
            );
            $utilities->miniOrange_set_get_configurations($variables_and_values, 'SET');

            /**
             * Enable Inline registration if license type is premium
             */
            $moEnableInlineRegistration = \Drupal::config('miniorange_2fa.settings')->get('mo_auth_enforce_inline_registration');
            if ($license_type !== 'DEMO' && $triggered_element !== 'CRON' && !isset($moEnableInlineRegistration)) {
                $utilities->miniOrange_set_get_configurations(array('mo_auth_enforce_inline_registration' => TRUE), 'SET');
            }

            $all_users = $user_api_handler->getall($no_of_users);
            if ($no_of_users == 1) {
                $utilities->miniOrange_set_get_configurations(array('mo_user_limit_exceed' => TRUE), 'SET');
            }

            if ($all_users->status == 'CURL_ERROR') {
                \Drupal::messenger()->addError(t('cURL is not enabled. Please enable cURL'));
                return;
            } elseif ($all_users->status == 'SUCCESS') {
                if (isset($all_users->fetchedCount) && $all_users->fetchedCount == 1) {
                    $fetch_first_user = $user_api_handler->getall(1);
                    if (is_object($fetch_first_user) && $fetch_first_user->status == 'SUCCESS' && isset($fetch_first_user->fetchedCount) && $fetch_first_user->fetchedCount == 1) {
                        if ($fetch_first_user->users[0]->username != $all_users->users[0]->username) {
                            $utilities->miniOrange_set_get_configurations(array('mo_user_limit_exceed' => TRUE), 'SET');
                        } else {
                            $utilities->miniOrange_set_get_configurations(array('mo_user_limit_exceed' => TRUE), 'CLEAR');
                        }
                    }
                } else {
                    $utilities->miniOrange_set_get_configurations(array('mo_user_limit_exceed' => TRUE), 'CLEAR');
                }
            }
            //drupal_flush_all_caches(); //TODO: Remove this after 3.08 release
            if ($triggered_element === 'FORM') {
                \Drupal::messenger()->addStatus(t('License fetched successfully.'));
            }
            return;
        } elseif (is_object($response)) {
            $variables_and_values = array(
                'mo_auth_2fa_license_type' => 'DEMO',
                'mo_auth_2fa_license_plan' => 'DEMO',
                'mo_auth_2fa_license_no_of_users' => 1,
            );
            $utilities->miniOrange_set_get_configurations($variables_and_values, 'SET');
            $variables_and_values = array(
                'mo_auth_2fa_ivr_remaining',
                'mo_auth_2fa_sms_remaining',
                'mo_auth_2fa_email_remaining',
                'mo_auth_2fa_license_expiry',
                'mo_auth_2fa_support_expiry',
                'mo_auth_enforce_inline_registration',
                'mo_auth_2fa_allow_reconfigure_2fa',
                'mo_auth_2fa_kba_questions',
                'mo_auth_enable_allowed_2fa_methods',
                'mo_auth_selected_2fa_methods',
                'mo_auth_enable_role_based_2fa',
                'mo_auth_role_based_2fa_roles',
                'mo_auth_enable_domain_based_2fa',
                'mo_auth_domain_based_2fa_domains',
                'mo_2fa_domain_and_role_rule',
                'mo_auth_use_only_2nd_factor',
                'mo_auth_enable_trusted_IPs',
                'mo_auth_trusted_IP_address',
                // Advanced settings variables
                'mo_auth_enable_2fa_for_password_reset',

                // opt-in and opt-out variables
                'allow_end_users_to_decide',

                'auto_fetch_phone_number',
                'phone_number_field_machine_name',
                'auto_fetch_phone_number_country_code'
            );
            $utilities->miniOrange_set_get_configurations($variables_and_values, 'CLEAR');
        }

        MoAuthUtilities::mo_add_loggers_for_failures(isset($response->message) ? $response->message : '', 'error');

        if ($triggered_element === 'FORM') {
            \Drupal::messenger()->addError(t('No license found under your account, Please reach out at drupalsupport@xecurify.com'));
        }
    }
}
