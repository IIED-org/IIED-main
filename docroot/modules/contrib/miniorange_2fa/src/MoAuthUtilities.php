<?php

namespace Drupal\miniorange_2fa;
/**
 * @file
 * This file is part of miniOrange 2FA module.
 *
 * The miniOrange 2FA module is free software:
 *     you can redistribute it and/or modify it
 *     under the terms of the GNU General Public
 *     License as published by the Free Software
 *     Foundation, either version 3 of the
 *     License, or
 *(at your option) any later version.
 *
 * miniOrange 2FA module is distributed in the
 *     hope that it will be useful, but WITHOUT
 *     ANY WARRANTY; without even the implied
 *     warranty of MERCHANTABILITY or FITNESS FOR
 *     A PARTICULAR PURPOSE.  See the GNU General
 *     Public License for more details.
 *
 * You should have received a copy of the GNU
 *     General Public License along with
 *     miniOrange 2FA module.  If not, see
 *     <http://www.gnu.org/licenses/>.
 */


use Exception;
use Drupal\Core\Url;
use Drupal\user\Entity\User;
use Drupal\user\Entity\Role;
use Drupal\Component\Utility\Xss;
use http\Exception\RuntimeException;
use Drupal\field\Entity\FieldConfig;
use Drupal\Core\Form\FormStateInterface;
use GuzzleHttp\Exception\RequestException;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\miniorange_2fa\Form\MoAuthCustomerSetup;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Database\Query\SelectInterface;

class MoAuthUtilities
{

    public static function invoke2fa_OR_inlineRegistration($username, $tmpDestination = '')
    {
        /**
         * Check to invoke inline registration for reset password flow
         */
        $skip_password_required = empty($tmpDestination);

        $variables_and_values1 = array(
            'mo_auth_enforce_inline_registration',
            'mo_auth_2fa_license_type',
            'mo_2fa_domain_and_role_rule',
            'mo_auth_use_only_2nd_factor',
            'mo_auth_enable_backdoor',
            'mo_auth_enable_domain_based_2fa',
            'mo_auth_redirect_user_after_login',
            'mo_auth_enable_role_based_2fa',
            'mo_auth_customer_api_key',
            'mo_auth_rba',
        );
        $mo_db_values = MoAuthUtilities::miniOrange_set_get_configurations($variables_and_values1, 'GET');

        $user = user_load_by_name($username);

        if ($user === false) {
            \Drupal::messenger()->addError(t('Invalid credentials'));
            return;
        }

        if ($user->getEmail() == null) {
            $utilities = new MoAuthUtilities();
            $utilities->redirectUserToLoginPage('Email address not found under user profile. Please contact your administrator');
        }
        $user_id = $user->id();
        $roles = $user->getRoles();

        $session = self::getSession();
        $session->set('mo_auth', array('status' => '1ST_FACTOR_AUTHENTICATED', 'uid' => $user_id, 'challenged' => 0, 'user_email' => $user->getEmail(), 'moResetPass' => $tmpDestination));

        /**
         * Login without 2FA if backdoor url is enabled and user is admin.
         */

        $mo_auth_backdoor_enabled = $mo_db_values['mo_auth_enable_backdoor'];
        $backdoor_url_query = $mo_db_values['mo_auth_customer_api_key'];
        $query_parameters = \Drupal::request()->query->get('skip_2fa');
        $is_backdoor_login = $mo_auth_backdoor_enabled && ($query_parameters === $backdoor_url_query);
        if ($is_backdoor_login && ($user->hasRole('administrator'))) {
            $user = User::load($user_id);
            user_login_finalize($user);
            return;
        }

        /**
         * Reset the destination if flow is coming form the Password reset link
         */
        if (is_array($tmpDestination) && $tmpDestination[0] === 'moResetPass') {
            $tmpDestination = '';
        }

        $custom_attribute = MoAuthUtilities::get_users_custom_attribute($user_id);

        $tfaEnabled = TRUE;
        $authType = NULL;
        if (count($custom_attribute) > 0) {
            $user_email = $custom_attribute[0]->miniorange_registered_email;
            $authType = $custom_attribute[0]->activated_auth_methods;
            $tfaEnabled = $custom_attribute[0]->enabled == 1;
        }

        /**
         * Check for RBA if:
         * 2FA is already configured for the user
         * RBA is enabled
         * login using only 2nd factor is disabled
         */
        if(!empty($user_email) && $mo_db_values['mo_auth_rba'] && !$mo_db_values['mo_auth_use_only_2nd_factor']){
            if(self::checkRBA($user_id)){
                user_login_finalize($user);
                $session->save();
                if (isset($_COOKIE['Drupal_visitor_destination'])) {
                    global $base_url;
                    $url = $base_url . '/' . $_COOKIE['Drupal_visitor_destination'];
                    user_cookie_delete('destination');
                } else {
                    $url = isset($mo_db_values['mo_auth_redirect_user_after_login']) && !empty($mo_db_values['mo_auth_redirect_user_after_login']) ? $mo_db_values['mo_auth_redirect_user_after_login'] : Url::fromRoute('miniorange_2fa.user.mo_mfa_form', ['user' => $user_id])->toString();
                }
                $response = new RedirectResponse($url);
                $response->send();
            }
        }

        $customer = new MiniorangeCustomerProfile();
        $loginSettings = $mo_db_values['mo_auth_enforce_inline_registration'];
        $license_type = ($mo_db_values['mo_auth_2fa_license_type'] == '') ? 'DEMO' : $mo_db_values['mo_auth_2fa_license_type'];

        if (empty($user_email) && $mo_db_values['mo_auth_use_only_2nd_factor'] && !isset($_POST['pass']) && $skip_password_required) {
            \Drupal::configFactory()->getEditable('miniorange_2fa.settings')->set('mo_auth_2fa_use_pass', TRUE)->save();
            return;
        }

        /**
         * Role based and Domain Based 2FA check
         */
        // check for interaction only iff both are enabled
        $userInRoles = MoAuthUtilities::check_roles_to_invoke_2fa($roles);
        $userInDomain = MoAuthUtilities::check_domain_to_invoke_2fa($user->getEmail());

        /**
         * The condition handles the case when 2fa is not setup by the user but satisfies the role among allowed roles of 2fa
         */
        $TFARequired = '';
        if (count($custom_attribute) > 0 || ($userInDomain && $userInRoles)) {
            $TFARequired = $userInDomain && $userInRoles;
            if ($mo_db_values['mo_auth_enable_domain_based_2fa'] == TRUE && $mo_db_values['mo_auth_enable_role_based_2fa'] == TRUE) {
                $TFARequired = $mo_db_values['mo_2fa_domain_and_role_rule'] === 'OR' ? $userInRoles || $userInDomain : $userInRoles && $userInDomain;
            }
        }
        $TFARequired = $mo_db_values['mo_auth_use_only_2nd_factor'] === TRUE || $TFARequired;

        $moMfaSession = $session->get("mo_auth", null);

        if ((!$is_backdoor_login || !isset($query_parameters)) || !($user->hasRole('administrator') || $user->hasRole('admin'))) {
            if ($TFARequired && $tfaEnabled) {
                $challengeSuccess = FALSE;
                $url = Url::fromRoute('user.login')->toString();
                if (!empty($user_email)) {
                    if ($license_type == 'PREMIUM' || $license_type == 'DRUPAL_2FA_PLUGIN' || $license_type == 'DRUPAL8_2FA_MODULE') {
                        if (!is_null($moMfaSession) && $moMfaSession['status'] === '1ST_FACTOR_AUTHENTICATED' && $moMfaSession['challenged'] === 0) {
                            $challengeSuccess = self::mo_auth_challenge_user($user_email, $authType);
                        }
                        if ($challengeSuccess === TRUE) {
                            $url = Url::fromRoute('miniorange_2fa.authenticate_user', ['user' => $user_id])->toString();
                        }
                    } elseif (in_array('administrator', $roles) || in_array('admin', $roles) && $user_email == $customer->getRegisteredEmail()) {
                        if ($moMfaSession['status'] === '1ST_FACTOR_AUTHENTICATED' && $moMfaSession['challenged'] === 0) {
                            $challengeSuccess = self::mo_auth_challenge_user($user_email, $authType);
                        }
                        if ($challengeSuccess === TRUE) {
                            $url = Url::fromRoute('miniorange_2fa.authenticate_user', ['user' => $user_id])->toString();
                        }
                    } else {
                        $user = User::load($user_id);
                        user_login_finalize($user);
                        return;
                    }
                } elseif (($license_type == 'PREMIUM' || $license_type == 'DRUPAL_2FA_PLUGIN' || $license_type == 'DRUPAL8_2FA_MODULE') && $loginSettings) {
                    $moMfaSession['challenge'] = 1;
                    $session->set('mo_auth', $moMfaSession);
                    $url = Url::fromRoute('miniorange_2fa.select_method', ['user' => $user_id])->toString();
                    $options = self::get_2fa_methods_for_inline_registration(TRUE);

                    if( \Drupal::config('miniorange_2fa.settings')->get('mo_auth_enable_role_based_2fa')) {
                        $options = self::get_2fa_methods_for_role_based_2fa($options, $user_id);
                    }
                    if(count($options) == 1) {
                        $key = array_keys($options);
                        if(!self::is_required_phone_number($key[0])){
                            self::process_selected_method($key[0]);
                        }
                    }
                } else {
                    $user = User::load($user_id);
                    user_login_finalize($user);
                    return;
                }

                $session->save();

                $response = new RedirectResponse($url);
                $response->send();
                exit;
            } else {
                $_GET['destination'] = $tmpDestination;
            }
        }

        /**
         * Do not remove this code.
         */
        // $user = User::load( $user_id );
        // user_login_finalize( $user );
        // if ( $userInRoles ) {
        //     $url = Url::fromRoute('user.login')->toString();
        //     $response = new RedirectResponse($url);
        //     $response->send();
        //     exit;
        // }
    }

    /**
     * HANDALE ALL THE DATABASE VARIABLE CALLS
     * LIKE SET|GET|CLEAR
     * -----------------------------------------------------------------------
     * @variable_array:
     * FORMAT OF ARRAY FOR DIEFRENT @param
     * SET array( vaviable_name1(key) => value,
     *     vaviable_name2(key) => value ) GET and
     *     CLEAR array( vaviable_name1(value),
     *     vaviable_name2(value) )  note: key
     *     doesnt matter here
     * -----------------------------------------------------------------------
     * @mo_method:  SET | GET | CLEAR
     * -----------------------------------------------------------------------
     * @return array | void
     */
    public static function miniOrange_set_get_configurations($variable_array, $mo_method)
    {
        if ($mo_method === 'GET') {
            $variables_and_values = array();
            $miniOrange_config = \Drupal::config('miniorange_2fa.settings');
            foreach ($variable_array as $variable => $value) {
                $variables_and_values[$value] = $miniOrange_config->get($value);
            }
            return $variables_and_values;
        }
        $configFactory = \Drupal::configFactory()->getEditable('miniorange_2fa.settings');
        if ($mo_method === 'SET') {
            foreach ($variable_array as $variable => $value) {
                $configFactory->set($variable, $value)->save();
            }
            return;
        }
        foreach ($variable_array as $variable => $value) {
            $configFactory->clear($value)->save();
        }
    }

    static function getSession()
    {
        $session = \Drupal::service('session_manager');
        if (!$session->isStarted()) {
            $session->start();
        }

        $request = \Drupal::request();
        return $request->getSession();
    }

    public static function get_users_custom_attribute($user_id)
    {
        $connection = \Drupal::database();
        $query = $connection->query("SELECT * FROM {UserAuthenticationType} where uid = $user_id");
        $result = $query->fetchAll();
        return $result;
    }

    public static function check_roles_to_invoke_2fa($roles)
    {
        $variables_and_values = array(
            'mo_auth_enable_role_based_2fa',
            'mo_auth_use_only_2nd_factor',
            'mo_auth_role_based_2fa_roles',
        );
        $mo_db_values = MoAuthUtilities::miniOrange_set_get_configurations($variables_and_values, 'GET');

        if ($mo_db_values['mo_auth_enable_role_based_2fa'] !== TRUE || $mo_db_values['mo_auth_use_only_2nd_factor'] === TRUE) {
            return TRUE;
        }
        $return_value = FALSE;
        $selected_roles = ( array )json_decode($mo_db_values['mo_auth_role_based_2fa_roles']);
        foreach ($selected_roles as $sysName => $displayName) {
            if (in_array($sysName, $roles, TRUE)) {
                $return_value = TRUE;
                break;
            }
        }
        return $return_value;
    }

    public static function check_domain_to_invoke_2fa($moUserEmail)
    {
        /* Need all the commneted code in this function */

        $variables_and_values = array(
            'mo_auth_enable_domain_based_2fa',
            'mo_auth_domain_based_2fa_domains',
            //'mo_auth_2fa_domain_exception_emails',
            //'mo_2fa_domains_are_white_or_black',
        );
        $mo_db_values = MoAuthUtilities::miniOrange_set_get_configurations($variables_and_values, 'GET');
        if ($mo_db_values['mo_auth_enable_domain_based_2fa'] != TRUE) {
            return TRUE;
        }
        $return_value = FALSE;
        $selected_domains = explode(';', $mo_db_values['mo_auth_domain_based_2fa_domains']);
        $moUserDomain = substr(strrchr($moUserEmail, "@"), 1);
        if (in_array($moUserDomain, $selected_domains)) {
            $return_value = TRUE;
        }

        /*if( $return_value == TRUE ) {
            $exceptionEmails = $mo_db_values['mo_auth_2fa_domain_exception_emails'];
            $exceptionEmailsArray = explode(";", $exceptionEmails );
            foreach ( $exceptionEmailsArray as $key => $value ) {
                if( strcasecmp( $value, $moUserEmail ) == 0 ) {
                    $return_value = FALSE;
                    break;
                }
            }
        }
        $whiteOrBlack = $mo_db_values['mo_2fa_domains_are_white_or_black'] == 'white' ? FALSE : TRUE;
        return $return_value == $whiteOrBlack ;*/

        return $return_value;
    }

    public static function mo_auth_challenge_user($userEmail, $authType)
    {
        $session = self::getSession();
        $moMfaSession = $session->get("mo_auth", null);
        $utilities = new MoAuthUtilities();

        $authenticatorMethod = 'GOOGLE AUTHENTICATOR';
        $isTOTPMethod = in_array($authType, self::mo_TOTP_2fa_mentods());
        if ($isTOTPMethod) {
            $authenticatorMethod = $authType;
            $authType = 'GOOGLE AUTHENTICATOR';
        }

        $custom_attribute = MoAuthUtilities::get_users_custom_attribute($moMfaSession['uid']);

        $phone = NULL;
        if ($authType == AuthenticationType::$SMS['code'] || $authType == AuthenticationType::$SMS_AND_EMAIL['code'] || $authType == AuthenticationType::$OTP_OVER_PHONE['code']) {
            $phone = $custom_attribute[0]->phone_number;
        }

        $customer = new MiniorangeCustomerProfile();
        $miniorange_user = new MiniorangeUser($customer->getCustomerID(), $userEmail, $phone, NULL, $authType);
        $auth_api_handler = new AuthenticationAPIHandler($customer->getCustomerID(), $customer->getAPIKey());
        $response = $auth_api_handler->challenge($miniorange_user);

        if (is_object($response) && $response->status == 'SUCCESS') {
            /**
             * Invoke the hook to alter login details.
             */
            if ($isTOTPMethod) {
                $response->message = 'Please enter the 6 digit code generated on your ' . ucwords(strtolower($authenticatorMethod)) . ' app.';
            }
            $response = self::invokeAlterLoginFlowDetailsHook($response, $authenticatorMethod);
            $moMfaSession['mo_challenge_response'] = $response;
            $moMfaSession['challenged'] = 1;
            \Drupal::messenger()->addStatus(t($response->message));
            $session->set('mo_auth', $moMfaSession);
            $session->save();
            return TRUE;
        } elseif (is_object($response) && $response->status == 'FAILED' && $response->message == 'Invalid username or email.') {
            $user = User::load($moMfaSession['uid']);
            MoAuthUtilities::delete_user_from_UserAuthentication_table($user);
            self::mo_add_loggers_for_failures($response->message, 'error');
            $utilities->redirectUserToLoginPage('An error occurred while processing your request. Please try again.');
        } else {
            $session->remove('mo_auth');
            self::mo_add_loggers_for_failures(is_object($response) ? $response->message : '-', 'error');
            $utilities->redirectUserToLoginPage('An error occurred while processing your request. Please try again after sometime.');
        }
    }

    /**
     * @return array of all TOTP based 2FA
     *     methods (Configurable via QR code).
     */
    public static function mo_TOTP_2fa_mentods()
    {
        $options = array(
            AuthenticationType::$MICROSOFT_AUTHENTICATOR['code'],
            AuthenticationType::$OKTA_VERIFY['code'],
            AuthenticationType::$GOOGLE_AUTHENTICATOR['code'],
            AuthenticationType::$AUTHY_AUTHENTICATOR['code'],
            AuthenticationType::$LASTPASS_AUTHENTICATOR['code'],
            AuthenticationType::$DUO_AUTHENTICATOR['code'],
            AuthenticationType::$_2FAS_AUTHENTICATOR['code'],
            AuthenticationType::$ZOHO_ONEAUTH['code'],
            AuthenticationType::$RSA_SECURID['code'],
        );
        return $options;
    }

    public static function invokeAlterLoginFlowDetailsHook($response, $authenticatorMethod)
    {
        if (isset($response) && $response->status == 'SUCCESS') {
            $responseArr = [
                'authType' => $response->authType,
                'status' => $response->status,
                'message' => $response->message,
                'phoneNumber' => $response->phoneDelivery->contact,
                'emailAddress' => $response->emailDelivery->contact,
                'description' => '',
                'allowedAttempts' => 3,
                'authenticator' => $authenticatorMethod,
            ];
            $responseArrVal = \Drupal::moduleHandler()->invokeAll('invoke_alter_login_flow_details', [$responseArr]); //custom hook
        }

        $response->message = isset($responseArrVal['message']) && $responseArrVal['message'] != '' && $responseArrVal['message'] != NULL ? $responseArrVal['message'] : $response->message;
        $response->description = isset($responseArrVal['description']) && $responseArrVal['description'] != '' && $responseArrVal['description'] != NULL ? $responseArrVal['description'] : '';
        $response->allowedAttempts = isset($responseArrVal['allowedAttempts']) && $responseArrVal['allowedAttempts'] > 0 && $responseArrVal['allowedAttempts'] != NULL ? $responseArrVal['allowedAttempts'] : 3;

        return $response;
    }

    /**
     * @param $message = Which you want to add in
     *     the logger report.
     * @param $typeOfLogger = error, notice,
     *     info, emergency, warning, alert,
     *     critical, debug.
     */
    public static function mo_add_loggers_for_failures($message, $typeOfLogger = 'info')
    {
        \Drupal::logger('miniorange_2fa')->$typeOfLogger($message);
    }

    /**
     * SEND SUPPORT QUERY | NEW FEATURE REQUEST |
     * DEMO REQUEST
     * @param $email
     * @param $phone
     * @param $query
     */
    public static function send_support_query($email, $phone, $query)
    {
        $support = new Miniorange2FASupport($email, $phone, $query);
        $support_response = $support->sendSupportQuery();

        if (is_object($support_response) && $support_response->status == 'CURL_ERROR') {
            \Drupal::messenger()->addError(t('cURL is not enabled. Please enable cURL'));
        }
        return $support_response;
    }

    public static function get_2fa_methods_for_inline_registration($methods_selected)
    {
        if ($methods_selected === TRUE && \Drupal::config('miniorange_2fa.settings')->get('mo_auth_enable_allowed_2fa_methods')) {
            $selected_2fa_methods = json_decode(\Drupal::config('miniorange_2fa.settings')->get('mo_auth_selected_2fa_methods'), TRUE);
            if (!empty($selected_2fa_methods)) {
                return $selected_2fa_methods;
            }
        }

        $options = array(
            AuthenticationType::$EMAIL['code'] => AuthenticationType::$EMAIL['name'],
            AuthenticationType::$GOOGLE_AUTHENTICATOR['code'] => AuthenticationType::$GOOGLE_AUTHENTICATOR['name'],
            AuthenticationType::$SMS['code'] => AuthenticationType::$SMS['name'],
            AuthenticationType::$SMS_AND_EMAIL['code'] => AuthenticationType::$SMS_AND_EMAIL['name'],
            AuthenticationType::$MICROSOFT_AUTHENTICATOR['code'] => AuthenticationType::$MICROSOFT_AUTHENTICATOR['name'],
            AuthenticationType::$OKTA_VERIFY['code'] => AuthenticationType::$OKTA_VERIFY['name'],
            AuthenticationType::$DUO_AUTHENTICATOR['code'] => AuthenticationType::$DUO_AUTHENTICATOR['name'],
            AuthenticationType::$AUTHY_AUTHENTICATOR['code'] => AuthenticationType::$AUTHY_AUTHENTICATOR['name'],
            AuthenticationType::$LASTPASS_AUTHENTICATOR['code'] => AuthenticationType::$LASTPASS_AUTHENTICATOR['name'],
            AuthenticationType::$_2FAS_AUTHENTICATOR['code'] => AuthenticationType::$_2FAS_AUTHENTICATOR['name'],
            AuthenticationType::$ZOHO_ONEAUTH['code'] => AuthenticationType::$ZOHO_ONEAUTH['name'],
            AuthenticationType::$RSA_SECURID['code'] => AuthenticationType::$RSA_SECURID['name'],
            AuthenticationType::$OTP_OVER_PHONE['code'] => AuthenticationType::$OTP_OVER_PHONE['name'],
            AuthenticationType::$KBA['code'] => AuthenticationType::$KBA['name'],
            AuthenticationType::$QR_CODE['code'] => AuthenticationType::$QR_CODE['name'],
            AuthenticationType::$PUSH_NOTIFICATIONS['code'] => AuthenticationType::$PUSH_NOTIFICATIONS['name'],
            AuthenticationType::$SOFT_TOKEN['code'] => AuthenticationType::$SOFT_TOKEN['name'],
            AuthenticationType::$EMAIL_VERIFICATION['code'] => AuthenticationType::$EMAIL_VERIFICATION['name'],
            AuthenticationType::$HARDWARE_TOKEN['code'] => AuthenticationType::$HARDWARE_TOKEN['name'],
            /** DO NOT REMOVE OR UNCOMMENT UNTIL THESE FEATURES IMPLEMENTED */
            //AuthenticationType::$OTP_OVER_WHATSAPP['code']     => AuthenticationType::$OTP_OVER_WHATSAPP['name'],
        );
        return $options;
    }

    /**
     * @param string $question_set - which
     *     question set needs to be return
     * @param string $type = return type
     * @return array - Question set
     */
    public static function mo_get_kba_questions($question_set = 'ONE', $type = 'ARRAY')
    {
        $variables_and_values = array(
            'mo_auth_enable_custom_kba_questions',
            'mo_auth_custom_kba_set_1',
            'mo_auth_custom_kba_set_2',
        );
        $mo_db_values = self::miniOrange_set_get_configurations($variables_and_values, 'GET');

        if (($mo_db_values['mo_auth_enable_custom_kba_questions'] === FALSE || $mo_db_values['mo_auth_enable_custom_kba_questions'] == NULL) || $mo_db_values['mo_auth_custom_kba_set_1'] == '' && $mo_db_values['mo_auth_custom_kba_set_2'] == '') {
            $question_set_one_string = 'What is your first company name?;What was your childhood nickname?;In what city did you meet your spouse/significant other?;What is the name of your favorite childhood friend?;What school did you attend for sixth grade?';
            $question_set_two_string = 'In what city or town was your first job?;What is your favorite sport?;Who is your favorite sports player?;What is your grandmothers maiden name?;What was your first vehicles registration number?';
        } else {
            $question_set_one_string = $mo_db_values['mo_auth_custom_kba_set_1'];
            $question_set_two_string = $mo_db_values['mo_auth_custom_kba_set_2'];
        }

        if ($question_set === 'ONE' || $question_set == 1) {
            /** If type == STRING then send unprocessed string to show in the textarea ( login Settings Tab ) **/
            return $type === 'STRING' ? $question_set_one_string : self::get_kba_array($question_set_one_string);
        }
        /** If type == STRING then send unprocessed string to show in the textarea ( login Settings Tab ) **/
        return $type === 'STRING' ? $question_set_two_string : self::get_kba_array($question_set_two_string);
    }

    /**
     * @param $kba_question_string = to process
     *     and return question array
     * @return array = question array
     */
    public static function get_kba_array($kba_question_string)
    {
        $kba_question = explode(';', $kba_question_string);
        $question_array = array();
        foreach ($kba_question as $key => $value) {
            $question_array[$value] = $value;
        }
        return $question_array;
    }

    /**
     * Return module tab URL
     * @param $tab_name
     * @return string = URL
     */
    public static function get_mo_tab_url($tab_name)
    {
        global $base_url;
        if ($tab_name === 'LOGIN') {
            return $base_url . '/admin/config/people/miniorange_2fa/login_settings';
        } elseif ($tab_name === 'CUSTOMER_SETUP') {
            return $base_url . '/admin/config/people/miniorange_2fa/customer_setup';
        } elseif ($tab_name === 'LOGS') {
            return $base_url . '/admin/reports/dblog';
        }
    }

    /**
     * When user cancel the test/configuration
     * process redirect him to setup 2fa page
     */
    public static function mo_handle_form_cancel()
    {
        global $base_url;
        $url = $base_url . '/admin/config/people/miniorange_2fa/setup_twofactor';
        $response = new TrustedRedirectResponse($url);
        $response->send();
    }

    public static function show_error_or_success_message($message, $status)
    {
        global $base_url;
        $url = $base_url . '/admin/config/people/miniorange_2fa/setup_twofactor';
        \Drupal::configFactory()->getEditable('miniorange_2fa.settings')->set('mo_auth_2fa_Success/Error message', $message)->save();
        \Drupal::configFactory()->getEditable('miniorange_2fa.settings')->set('mo_auth_2fa_Success/Error status', $status)->save();
        $response = new TrustedRedirectResponse($url);
        $response->send();
    }

    public static function isCustomerRegistered()
    {
        $variables_and_values = array(
            'mo_auth_customer_admin_email',
            'mo_auth_customer_id',
            'mo_auth_customer_api_key',
            'mo_auth_customer_token_key',
        );

        $mo_db_values = self::miniOrange_set_get_configurations($variables_and_values, 'GET');

        if ($mo_db_values['mo_auth_customer_admin_email'] == NULL || $mo_db_values['mo_auth_customer_id'] == NULL
            || $mo_db_values['mo_auth_customer_token_key'] == NULL || $mo_db_values['mo_auth_customer_api_key'] == NULL) {
            return FALSE;
        }

        return TRUE;
    }

    /**
     * Add premium tag if free module activated
     */
    public static function mo_add_premium_tag()
    {
        global $base_url;
        $url = $base_url . '/admin/config/people/miniorange_2fa/licensing';
        $mo_premium_tag = '<a href= "' . $url . '" style="color: red; font-weight: lighter;">[PREMIUM]</a>';
        if (\Drupal::config('miniorange_2fa.settings')->get('mo_auth_2fa_license_type') != 'DEMO') {
            return '';
        }
        return $mo_premium_tag;
    }

    /**
     * @param $mo_saved_IP_address = IP Addresses
     *     entered by user
     * @return boolean | string if error
     * Check whether provided IP is valid or not
     */
    public static function check_for_valid_IPs($mo_saved_IP_address)
    {
        /** Separate IP address with the semicolon (;) **/
        $trusted_IP_array = explode(";", rtrim($mo_saved_IP_address, ";"));
        foreach ($trusted_IP_array as $key => $value) {
            if ($value == "::1") {
                continue;
            }
            if (stristr($value, '-')) {
                /** Check if it is a range of IP address **/
                list($lower, $upper) = explode('-', $value, 2);
                if (!filter_var($lower, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) && !filter_var($upper, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                    return "Invalid IP (<strong> " . $lower . "-" . $upper . "</strong> ) address. Please check lower range and upper range.";
                }
                $lower_range = ip2long($lower);
                $upper_range = ip2long($upper);
                if ($lower_range >= $upper_range) {
                    return "Invalid IP range (<strong> " . $lower . "-" . $upper . "</strong> ) address. Please enter range in <strong>( lower_range - upper_range )</strong> format.";
                }
            } else {
                /** Check if it is a single IP address **/
                if (!filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                    return " Invalid IP (<strong> " . $value . "</strong> ) address. Please enter valid IP address.";
                }
            }
        }
        return TRUE;
    }

    /**
     * @return bool
     */
    public static function check_trusted_IPs()
    {
        $enable_trusted_IP = \Drupal::config('miniorange_2fa.settings')->get('mo_auth_enable_trusted_IPs');
        if (!$enable_trusted_IP) {
            return FALSE;
        }
        $current_IP_address = self::get_client_ip();
        $trusted_IP = \Drupal::config('miniorange_2fa.settings')->get('mo_auth_trusted_IP_address');
        if (empty($trusted_IP)) {
            return FALSE;
        }
        $trusted_IP_array = explode(";", $trusted_IP);
        $mo_ip_found = FALSE;

        foreach ($trusted_IP_array as $key => $value) {
            if (stristr($value, '-')) {
                /** Search in range of IP address **/
                list($lower, $upper) = explode('-', $value, 2);
                $lower_range = ip2long($lower);
                $upper_range = ip2long($upper);
                $current_IP = ip2long($current_IP_address);
                if ($lower_range !== FALSE && $upper_range !== FALSE && $current_IP !== FALSE && (($current_IP >= $lower_range) && ($current_IP <= $upper_range))) {
                    $mo_ip_found = TRUE;
                    break;
                }
            } else {
                /** Compare with single IP address **/
                if ($current_IP_address == $value) {
                    $mo_ip_found = TRUE;
                    break;
                }
            }
        }
        return $mo_ip_found;
    }

    /**
     * @return array|false|string
     * Function to get the client IP address
     */
    static function get_client_ip()
    {
        if (getenv('HTTP_CLIENT_IP'))
            $ipaddress = getenv('HTTP_CLIENT_IP');
        else if (getenv('REMOTE_ADDR'))
            $ipaddress = getenv('REMOTE_ADDR');
        else if (getenv('HTTP_X_FORWARDED_FOR'))
            $ipaddress = getenv('HTTP_X_FORWARDED_FOR');
        else if (getenv('HTTP_X_FORWARDED'))
            $ipaddress = getenv('HTTP_X_FORWARDED');
        else if (getenv('HTTP_FORWARDED_FOR'))
            $ipaddress = getenv('HTTP_FORWARDED_FOR');
        else if (getenv('HTTP_FORWARDED'))
            $ipaddress = getenv('HTTP_FORWARDED');
        else
            $ipaddress = 'UNKNOWN';

        return $ipaddress;
    }

    /**
     * @return array = All the roles available in
     *     the Drupal site
     */
    public static function get_Existing_Drupal_Roles()
    {
        $roles = Role::loadMultiple();
        $roles_arr = array();
        foreach ($roles as $key => $value) {
            /** Skip Anonymous user role **/
            if ($key == 'anonymous')
                continue;
            $roles_arr[$key] = $value->label();
        }
        return $roles_arr;
    }

    public static function getHiddenEmail($email)
    {
        $split = explode("@", $email);
        if (count($split) == 2) {
            $hidden_email = substr($split[0], 0, 1) . 'xxxxxx' . substr($split[0], -1) . '@' . $split[1];
            return $hidden_email;
        }
        return $email;
    }

    public static function indentSecret($secret)
    {
        $strlen = strlen($secret);
        $indented = '';
        for ($i = 0; $i <= $strlen; $i = $i + 4) {
            $indented .= substr($secret, $i, 4) . ' ';
        }
        $indented = trim($indented);
        return $indented;
    }

    public static function callService($customer_id, $apiKey, $url, $json, $redirect_to_error_page = true)
    {
        if (!self::isCurlInstalled()) {
            if (!$redirect_to_error_page) {
                return (object)(array(
                    "status" => 'CURL_ERROR',
                    "message" => 'PHP cURL extension is not installed or disabled.'
                ));
            }
            self::showErrorMessage("cURL Error", "", "PHP cURL extension is not installed or disabled.");
        }

        $current_time_in_millis = round(microtime(TRUE) * 1000);
        $string_to_hash = $customer_id . number_format($current_time_in_millis, 0, '', '') . $apiKey;
        $hash_value = hash("sha512", $string_to_hash);

        $moHeaders = array(
            "Content-Type" => "application/json",
            "Customer-Key" => $customer_id,
            "Timestamp" => number_format($current_time_in_millis, 0, '', ''),
            "Authorization" => $hash_value
        );

        try {
            $response = \Drupal::httpClient()->post($url, [
                'body' => $json,
                'http_errors' => FALSE,
                'headers' => $moHeaders,
                'verify' => false
            ]);
        } catch (\Exception  $e) {
            self::mo_add_loggers_for_failures($e->getMessage(), 'error');
            return 0;
        }

        return json_decode($response->getBody());
    }

    public static function isCurlInstalled()
    {
        if (in_array('curl', get_loaded_extensions())) {
            return 1;
        }
        return 0;
    }


    public static function showErrorMessage($error, $message, $cause, $closeWindow = FALSE)
    {
        global $base_url;
        $actionToTakeUponWindow = $closeWindow === TRUE ? 'onClick="self.close();"' : 'href="' . $base_url . '/user/login"';
        echo '<div style="font-family:Calibri;padding:0 3%;">';
        echo '<div style="color: #a94442;background-color: #f2dede;padding: 15px;margin-bottom: 20px;text-align:center;border:1px solid #E6B3B2;font-size:18pt;"> ERROR</div>
                                  <div style="color: #a94442;font-size:14pt; margin-bottom:20px;"><p><strong>Error: </strong>' . xss::filter($error) . '</p>
                                      <p>' . xss::filter($message) . '</p>
                                      <p><strong>Possible Cause: </strong>' . xss::filter($cause) . '</p>
                                  </div>
                                  <div style="margin:3%;display:block;text-align:center;"></div>
                                  <div style="margin:3%;display:block;text-align:center;">
                                      <a style="padding:1%;width:100px;background: #0091CD none repeat scroll 0% 0%;cursor: pointer;font-size:15px;border-width: 1px;border-style: solid;border-radius: 3px;white-space: nowrap;box-sizing: border-box;border-color: #0073AA;box-shadow: 0px 1px 0px rgba(120, 200, 230, 0.6) inset;color: #FFF; text-decoration: none;"type="button"  ' . $actionToTakeUponWindow . ' >Done</a>
                                  </div>';
        exit;
    }

    public static function check_for_userID($user_id)
    {
        $connection = \Drupal::database();
        $query = $connection->select('UserAuthenticationType','user')
          ->condition('user.uid', $user_id , '=')
          ->fields('user');
        $row_count = $query->countQuery()->execute()->fetchField();

        if ($row_count> 0) {
            return TRUE;
        }
        return FALSE;
    }

    /**
     * @param $user
     * Delete user from @UserAuthenticationType
     *     table
     */
    public static function delete_user_from_UserAuthentication_table($user)
    {
        $query = \Drupal::database()->delete('UserAuthenticationType');
        $query->condition('uid', $user->id(), '=');
        $query->execute();
        self::delete_user_from_server($user);
    }

    public static function update_user_status_from_UserAuthentication_table($user)
    {
        $connection   = \Drupal::database();
        $query        = $connection->query("SELECT * FROM {UserAuthenticationType} WHERE uid='" . $user->id() . "'");
        $row          = $query->fetchAll();
        $current_user = \Drupal::currentUser();
        $current_user = $current_user->getAccountName();

        $fields_array = ($row[0]->enabled == 1) ? ['enabled' => '0'] : ['enabled' => '1'] ;
        $status       =  $row[0]->enabled == 0 ? 'enabled' : 'disabled';
        $message      =  t("2FA for @username is @status by @current_user",array('@username' => $user->getAccountName(), '@status' => $status, '@current_user' => $current_user));

        $connection->update('UserAuthenticationType')->fields($fields_array)->condition('uid',  $user->id(), '=')->execute();
        \Drupal::logger('miniorange_2fa')->info($message);
        \Drupal::messenger()->addStatus(t("You have @status the 2FA for <strong>@username</strong> successfully.", array('@username' => $user->getAccountName(), '@status' => $status)));
  }

    /**
     * Ignore this code for now
     */

    /*public static function isTFARequired( $roles, $email ) {
      $variables_and_values1 = array(
        'mo_auth_enable_domain_based_2fa',
        'mo_auth_enable_role_based_2fa',
        'mo_auth_use_only_2nd_factor'
      );
      $mo_db_values = MoAuthUtilities::miniOrange_set_get_configurations( $variables_and_values1, 'GET' );

      $userInRoles  = MoAuthUtilities::check_roles_to_invoke_2fa( $roles );
      $userInDomain = MoAuthUtilities::check_domain_to_invoke_2fa( $email);

      $TFARequired  = $userInDomain && $userInRoles;
      if( $mo_db_values['mo_auth_enable_domain_based_2fa'] == TRUE && $mo_db_values['mo_auth_enable_role_based_2fa'] == TRUE ){
        $TFARequired = $mo_db_values['mo_2fa_domain_and_role_rule'] === 'OR' ? $userInRoles || $userInDomain : $userInRoles && $userInDomain;
      }

      $TFARequired = $mo_db_values['mo_auth_use_only_2nd_factor']===TRUE || $TFARequired  ;

      return $TFARequired;
    }*/
    /**
     * @param $user
     * Delete user from miniOrange Server
     */
    public static function delete_user_from_server($user)
    {
        $customer = new MiniorangeCustomerProfile();
        $miniorange_user = new MiniorangeUser($customer->getCustomerID(), $user->getEmail(), NULL, NULL, NULL);
        $user_api_handler = new UsersAPIHandler($customer->getCustomerID(), $customer->getAPIKey());
        $response = $user_api_handler->delete($miniorange_user);

        if (is_object($response) && $response->status == 'SUCCESS') {
            self::mo_add_loggers_for_failures($response->message, 'info');
        } else {
            self::mo_add_loggers_for_failures('Error in deleting end user.', 'error');
        }
    }

    public static function update_user_email_from_UserAuthentication_table($existingEmail, $updatedEmail)
    {
        $query = \Drupal::database()->update('UserAuthenticationType');
        $query->fields(array('miniorange_registered_email' => $updatedEmail));
        $query->condition('miniorange_registered_email', $existingEmail);
        $query->execute();
        self::update_user_email_from_server($existingEmail, $updatedEmail);
    }

    public static function update_user_email_from_server($existingEmail, $updatedEmail)
    {
        $customer = new MiniorangeCustomerProfile();
        $miniorange_user = new MiniorangeUser($customer->getCustomerID(), $existingEmail, NULL, NULL, NULL, $updatedEmail);
        $user_api_handler = new UsersAPIHandler($customer->getCustomerID(), $customer->getAPIKey());
        $response = $user_api_handler->updateEmail($miniorange_user);
        if (is_object($response) && $response->status == 'SUCCESS') {
            self::mo_add_loggers_for_failures($response->message, 'info');
        } else {
            self::mo_add_loggers_for_failures('Error in changing user email.', 'error');
        }
    }

    public static function update_user_status_from_server($username, $status)
    {
        $utilities = new MoAuthUtilities();
        $variables_and_values = array(
            'mo_auth_customer_admin_email',
        );
        $mo_db_values = $utilities->miniOrange_set_get_configurations($variables_and_values, 'GET');
        $customer = new MiniorangeCustomerProfile();
        $miniorange_user = new MiniorangeUser($customer->getCustomerID(), $username, NULL, NULL, NULL, NULL);
        $user_api_handler = new UsersAPIHandler($customer->getCustomerID(), $customer->getAPIKey());
        //Handle a case of admin account of server
        $userResponse = $user_api_handler->get($miniorange_user);

        if (is_object($userResponse) && isset($userResponse->users[0]->primaryEmail)) {
            if ($userResponse->users[0]->primaryEmail === $mo_db_values['mo_auth_customer_admin_email']) {
                $utilities->mo_add_loggers_for_failures('Can not change the status of admin account' . ' = ' . $username, 'error');
            } else {
                $response = $user_api_handler->updateUserStatus($miniorange_user, $status);
                if (is_object($response) && $response->status == 'SUCCESS') {
                    $utilities->mo_add_loggers_for_failures($response->message . ' = ' . $username, 'info');
                } else {
                    $utilities->mo_add_loggers_for_failures('Something went wrong while changing the status of' . ' = ' . $username, 'warning');
                }
            }
        } else {
            $utilities->mo_add_loggers_for_failures('Something went wrong while changing the status of account' . ' = ' . $username, 'warning');
        }
    }

    public static function mo_auth_get_configured_methods($custom_attribute)
    {
        if (is_null($custom_attribute) || empty($custom_attribute))
            return array();
        $myArray = explode(',', $custom_attribute[0]->configured_auth_methods);
        $configured_methods = array_map('trim', $myArray);
        return $configured_methods;
    }

    public static function mo_auth_is_kba_configured($user_id)
    {
        $utilities = new MoAuthUtilities();
        $custom_attribute = $utilities->get_users_custom_attribute($user_id);
        $myArray = explode(',', $custom_attribute[0]->configured_auth_methods);
        $configured_methods = array_map('trim', $myArray);
        return array_search(AuthenticationType::$KBA['code'], $configured_methods);
    }

    static function updateMfaSettingsForUser($uid, $enableMfa = 1)
    {
        // Enter the user details in the userAuthenticationType Table
        $database = \Drupal::database();
        $result = self::get_users_custom_attribute($uid);

        if (count($result) > 0) {
            $database->update('UserAuthenticationType')->fields(['enabled' => $enableMfa])->condition('uid', $uid, '=')->execute();
        } else {
            $fields = array(
                'uid' => $uid,
                'configured_auth_methods' => '',
                'miniorange_registered_email' => '',
                'activated_auth_methods' => '',
                'enabled' => $enableMfa,
                'qr_code_string' => '',
                'phone_number' => '',
            );
            try {
                $database->insert('UserAuthenticationType')->fields($fields)->execute();
            } catch (Exception $e) {
                self::mo_add_loggers_for_failures($e->getMessage(), 'error');
            }
        }
    }

    static function isUserCanSee2FASettings()
    {
        $variableAndValues = self::miniOrange_set_get_configurations(['allow_end_users_to_decide'], "GET");
        $account = \Drupal::currentUser();
        $tfaEnabled = FALSE;
        // If opt-in opt out is disabled or user is not logged in then he can't see the 2FA settings
        if ($variableAndValues['allow_end_users_to_decide'] == FALSE) {
            return $tfaEnabled;
        } else {
            $custom_attributes = self::get_users_custom_attribute($account->id());
            if (count($custom_attributes) > 0) {
                $tfaEnabled = TRUE;
            }
        }
        return $tfaEnabled;
    }

    static function isSkipNotAllowed()
    {
        $variables_and_values = array(
            'allow_end_users_to_decide',
            'mo_auth_two_factor_instead_password',
        );
        $mo_db_values = self::miniOrange_set_get_configurations($variables_and_values, "GET");
        /**
         * SKIP 2fa is not allowed if only second factor option is set to TRUE
         */
        if ($mo_db_values['allow_end_users_to_decide'] && !$mo_db_values['mo_auth_two_factor_instead_password']) {
            return TRUE;
        }
        return FALSE;
    }

    static function getUserPhoneNumber($uid)
    {
        $variables_and_values = array(
            'auto_fetch_phone_number',
            'phone_number_field_machine_name',
            'auto_fetch_phone_number_country_code',
            'mo_auth_enable_headless_two_factor',
        );
        $mo_db_values = self::miniOrange_set_get_configurations($variables_and_values, "GET");
        if ($mo_db_values['auto_fetch_phone_number'] || $mo_db_values['mo_auth_enable_headless_two_factor']) {
            $fieldName = $mo_db_values['phone_number_field_machine_name'];
            $user = User::load($uid);
            $countryCode = $phone = $mo_db_values['auto_fetch_phone_number_country_code'];
            if (!is_null($user)) {
                $user = $user->toArray();
                if (isset($user[$fieldName]['0']['value']))
                    $phone = $user[$fieldName]['0']['value'];
                if (strpos($phone, "+") === FALSE)
                    $phone = strval($countryCode) . strval($phone);
            }
            return $phone;
        }
        return null;
    }

    static function loadUserByPhoneNumber($phoneNumber)
    {
        $fieldSet = array(
            'status' => '',
            'userID' => '',
            'error' => '',
        );

        $variables_and_values = array(
            'phone_number_field_machine_name',
            'mo_auth_enable_login_with_phone',
        );
        $mo_db_values = self::miniOrange_set_get_configurations($variables_and_values, "GET");
        $phoneFieldName = $mo_db_values['phone_number_field_machine_name'];
        if ($mo_db_values['mo_auth_enable_login_with_phone'] !== TRUE || !isset($phoneFieldName) || empty($phoneFieldName)) {
            $fieldSet['status'] = 'FAILED';
            $fieldSet['error'] = 'Login with phone number is not enabled on this site';
            return $fieldSet;
        }
        $tableName = 'user__' . $phoneFieldName;
        $colomnName = $phoneFieldName . '_value';

        $connection = \Drupal::database();
        $query = $connection->query("SELECT {entity_id}, {$colomnName} FROM {$tableName} where $colomnName = $phoneNumber");
        $result = $query->fetchAllkeyed();

        /** Get the count of each phone numbers available in the DB*/
        $phoneNumberCount = array_count_values($result);

        if (!isset($phoneNumberCount[$phoneNumber])) { //check whether any accounts has given number
            $fieldSet['status'] = 'FAILED';
            $fieldSet['error'] = 'Account does not exist. Please enter the phone number in an exact format as mentioned under your account. ( i.e +1xxxxxxxxxx or 1xxxxxxxxxx or xxxxxxxxx )';
        } elseif ($phoneNumberCount[$phoneNumber] === 1) { //check whether only one accounts consist given number
            $userID = array_search($phoneNumber, $result);
            $fieldSet['status'] = 'SUCCESS';
            $fieldSet['userID'] = $userID;
        } elseif ($phoneNumberCount[$phoneNumber] >= 2) { //check whether multiple accounts consist given number
            $fieldSet['status'] = 'FAILED';
            $fieldSet['error'] = 'Multiple accounts found with the phone number <strong>' . $phoneNumber . '</strong>. Please login with username.';
        }
        return $fieldSet;
    }

    public static function getUpgradeURL($upgradePlan)
    {
        $variables_and_values = array(
            'mo_auth_customer_admin_email',
        );
        $mo_db_values = self::miniOrange_set_get_configurations($variables_and_values, "GET");

        return MoAuthConstants::getBaseUrl() . '/login?username=' . $mo_db_values['mo_auth_customer_admin_email'] . '&redirectUrl=' . MoAuthConstants::getBaseUrl() . '/initializepayment&requestOrigin=' . $upgradePlan;
    }

    public static function getIsLicenseExpired($date)
    {
        $days = isset($date) ? intval((strtotime($date) - time()) / (60 * 60 * 24)) : 0;

        $returnLicenseExpiry = array();
        $returnLicenseExpiry['LicenseGoingToExpire'] = $days < 35 ? TRUE : FALSE;
        $returnLicenseExpiry['LicenseAlreadyExpired'] = $days < 0 ? TRUE : FALSE;

        return $returnLicenseExpiry;
    }

    /**
     * Return current URL parts.
     */
    public static function mo_auth_get_url_parts()
    {
        $query_param = \Drupal::service('path.current')->getPath();
        $url_parts = explode('/', $query_param);
        return $url_parts;
    }

    /**
     * @return string - Drupal core version
     */
    public static function mo_get_drupal_core_version()
    {
        return \DRUPAL::VERSION;
    }

    public static function license_expired($ExpiryDate)
    {
        $currentdate = \Drupal::time()->getRequestTime();
        if (isset($ExpiryDate) && $currentdate > strtotime($ExpiryDate)) {
            return FALSE;
        }
        return TRUE;
    }

    public static function fileCreateUrl($uri)
    {
        return \Drupal::hasService('file_url_generator') ? \Drupal::service('file_url_generator')->generateAbsoluteString($uri) : file_create_url($uri);
    }

    /**
     * @param $form
     * @param $form_state
     * @return mixed
     * Advertise network security
     */
    public static function miniOrange_advertise_network_security(&$form, &$form_state)
    {
        global $base_url;

        $form['miniorange_network_security_advertise'] = array(
            '#markup' => '<div class="mo_auth_table_layout mo_auth_container_2">',
        );
        $form['mo_idp_net_adv'] = array(
            '#markup' => '<form name="f1">
                <table id="idp_support" class="idp-table" style="border: none;">
                <h3>' . t('Looking for a Drupal Web Security module?') . '</h3>
                    <tr class="mo_ns_row">
                        <th class="mo_ns_image1"><img
                                    src="' . $base_url . '/' . self::moGetModulePath() . '/includes/images/security.jpg"
                                    alt="security icon" height=150px width=44%>
                           <br>
                        <strong>' . t('Drupal Website Security') . '</strong>
                        </th>
                    </tr>
                    <tr class="mo_ns_row">
                        <td class="mo_ns_align">
                            ' . t('Building a website is a time-consuming process that requires tremendous efforts. For smooth
                            functioning and protection from any sort of web attack appropriate security is essential and we
                            ensure to provide the best website security solutions available in the market.
                            We provide you enterprise-level security, protecting your Drupal site from hackers and malware.') . '
                        </td>
                    </tr>
                </table>
            </form>'
        );

        self::miniOrange_add_network_security_buttons($form, $form_state);

        return $form;
    }

    /**
     * Replacement of deprecated function
     * drupal_get_path()
     * @return Modules path
     */
    public static function moGetModulePath()
    {
        return \Drupal::service('extension.list.module')->getPath('miniorange_2fa');
    }

    public static function miniOrange_add_network_security_buttons(&$form, &$form_state)
    {
        $form['miniorange_radius_buttons'] = array(
            '#markup' => '<div class="mo2f_text_center"><b></b>
                          <a class=" mo_auth_button_left" href="https://www.drupal.org/project/security_login_secure" target="_blank">' . t('Download Module') . '</a>
                          <b></b><a class=" mo_auth_button_right" href=" ' . MoAuthConstants::$WBSITE_SECURITY . ' " target="_blank">' . t('Know More') . '</a></div></div>',
        );
    }

    public static function miniOrange_know_more_about_2fa(&$form, &$form_state)
    {
        $form['miniorange_know_more_about_2fa'] = array(
            '#markup' => '<div class="mo_auth_table_layout mo_auth_container_2">',
        );
        $form['mo_idp_net_adv'] = array(
            '#markup' => '<form name="f1">
                <table id="idp_support" class="idp-table" style="border: none;">
                    <tr class="mo_ns_row">
                        <td class="mo_ns_align">
                         <h5 style="text-align: center;">' . t('~~ Know more about this module ~~') . '</h5><br>
                            ' . t('Drupal Two Factor Authentication (2FA) module adds a second layer of authentication to secure your Drupal accounts.
                            It is a highly secure and easy to setup module which protects your site from hacks and unauthorized login attempts.
                            <br><br> <strong>The module supports 16+ 2FA methods. You can check out the list along with their setup guides <a href="https://plugins.miniorange.com/drupal-2fa-setup-guides" target="_blank">here</a>.</strong>
                            <br><br> <strong>You can now secure your <a href="https://plugins.miniorange.com/configure-2fa-with-headless-drupal" target="_blank">Headless/Decoupled</a> Drupal website with Robust Two Factor Authentication (TFA).</strong>
                            <br><br> <strong>You can check out the list of features of the module <a href="https://plugins.miniorange.com/drupal-two-factor-authentication-2fa#features" target="_blank">here</a>.</strong>
                            <br><br> <strong>We have 500+ happy customers using our 2FA module. You can check out the list <a href="https://plugins.miniorange.com/drupal#customer" target="_blank">here</a>. </strong>
                            <br><br> <strong>You can find the complete comparison between miniorange 2FA module and other 2FA solution on Drupal marketplace <a href="https://www.drupal.org/docs/comparison-of-contributed-modules/comparison-of-two-factor-authentication-modules" target="_blank">here</a>. </strong>
                            ') . '
                        </td>
                    </tr>
                </table>
            </form>'
        );
        return $form;
    }

    public static function checkEmailTransaction()
    {
        /**
         * Fetch license to get the remaining.
         */
        $customer = new MoAuthCustomerSetup();
        $from_state = '';
        $customer->mo_auth_fetch_customer_license('', $from_state, 'CRON');

        $variables_and_values = array(
            'mo_auth_2fa_email_remaining'
        );
        $mo_db_values = self::miniOrange_set_get_configurations($variables_and_values, 'GET');

        if ($mo_db_values['mo_auth_2fa_email_remaining'] > 0) {
            return TRUE;
        }
        return FALSE;
    }

    /**
     * @param $form
     * @param $form_state
     * @return mixed
     * Advertise 2fa case studies
     */
    public static function miniOrange_advertise_case_studies(&$form, &$form_state)
    {
        global $base_url;

        $form['miniorange_advertise_case_studies'] = array(
            '#markup' => '<div class="mo_auth_table_layout mo_auth_container_2">',
        );
        $form['mo_case_studies'] = array(
            '#markup' => '<form name="f1">
                <table id="idp_support" class="idp-table" style="border: none;">
                <h3 class="text-align-center">' . t('Have a glance at our unique case studies!') . '</h3>
                    <tr class="mo_ns_row">
                        <th class="mo_ns_image1"><img
                                    src="' . $base_url . '/' . self::moGetModulePath() . '/includes/images/case-study.png"
                                    alt="security icon" height=150px width=44%>
                           <br>
                        </th>
                    </tr>
                    <tr class="mo_ns_row">
                        <td class="mo_ns_align">
                            ' . t('Two factor authentication has been of great value to the security of websites. In addition to securing Drupal sites by providing more than 16 2FA methods, miniorange has successfully catered to complex use case requirements of its customers by providing precise customizations and consistent support.') . ' <br><br>' . t('Check out some of the case studies as follows:') . '<br><br>' . '
                            <li><a href="' . MoAuthConstants::headless_drupal_2fa . '">2FA for Headless Drupal site</a></li>
                            <li><a href="' . MoAuthConstants::SSO_and_2fa . '">2FA on top of Single Sign On for the Drupal site</a></li>
                            <li><a href="' . MoAuthConstants::hardware_token_2fa . '">Hardware Token as second factor of authentication</a></li>
                            <li><a href="' . MoAuthConstants::passwordless_login . '">2FA on Passwordless Login flow of the Drupal site</a></li>
                            <li><a href="' . MoAuthConstants::drupal_case_studies . '">See More</a></p></li>
                            <br><p>' . t('Feel free to reach out to us at <a href="mailto:drupalsupport@xecurify.com">drupalsupport@xecurify.com</a> or <a href="mailto:info@xecurify.com">info@xecurify.com</a> if any of the feature in Login Settings tab fail to satisfy your use case requirement and if you need assistance with your unique use case. We will be happy to assist you through setting up 2FA on your Drupal site.') . '</p>

                        </td>
                    </tr>
                </table>
            </form>'
        );


        return $form;
    }

    public function redirectUserToLoginPage($message = NULL)
    {

        if ($message != NULL) {
            \Drupal::messenger()->addError(t($message), TRUE);
        }

        $url = Url::fromRoute('user.login')->toString();

        $response = new RedirectResponse( $url );
        $request  = \Drupal::request();
        $request->getSession()->save();
        $response->prepare($request);
        \Drupal::service('kernel')->terminate($request, $response);
        $response->send();
        exit();
    }

    private static function checkRBA($user_id)
    {
        $current_device_info = self::generateRBADeviceInfo();
        $saved_device_info = self::fetchSavedRBADevices($user_id);

        $current_timestamp = \Drupal::time()->getCurrentTime();

        if(in_array($current_device_info, array_values($saved_device_info))){
            $rba_expiry_timestamp = array_search($current_device_info,$saved_device_info);
            if ($current_timestamp < $rba_expiry_timestamp){
                return true;
            }
            else{
                unset($saved_device_info[$rba_expiry_timestamp]);
                \Drupal::database()->update('UserAuthenticationType')->fields(['device_info' => json_encode($saved_device_info)])->condition('uid', $user_id, '=')->execute();
            }
        }
    }

    public function redirectUserToSetupTwoFactor($message = NULL)
    {
        if ($message != NULL) {
            \Drupal::messenger()->addError(t($message), TRUE);
        }

        $url = Url::fromRoute('miniorange_2fa.setup_twofactor')->toString();
        $response = new RedirectResponse($url);
        $response->send();
        exit;
    }

    public static function customUserFields()
    {
        $custom_fields = array('select' => '- Select Field Name -');
        $usr = User::load(\Drupal::currentUser()->id());
        $usrVal = $usr->toArray();
        foreach ($usrVal as $key => $value) {
            if (strpos($key, 'field_') === 0) {
              $label = $key;
              try {
                $field = FieldConfig::loadByName('user', 'user', $key);
                $label = $field->label();
              } catch (\Exception $e) {
                \Drupal::logger('miniorange_2fa')->error($e);
              }
              $custom_fields[$key] = $label;
            }
        }
        return $custom_fields;
    }

   /**
    * This function shows only allowed 2FA methods to respective roles during inline registration
    * @param $methods_selected
    * All allowed methods without role based restriction
    * @param $uid
    * User ID to get all roles
    * @return array|mixed
    * Array of 2FA methods to show during inline registration.
    */
    public static function get_2fa_methods_for_role_based_2fa($methods_selected, $uid) {
        $methods_to_show = [];
        $user = User::load($uid);
        $roles = $user->getRoles();
        $selected_roles = \Drupal::config('miniorange_2fa.settings')->get('mo_auth_role_based_2fa_roles');
        $selected_roles = json_decode($selected_roles, true);
        foreach ($selected_roles as $key => $value) {
            if(in_array($key, $roles)) {
                if($value == 'ALL SELECTED METHODS') {
                  return $methods_selected;
                }
                if(array_key_exists($value, $methods_selected)) {
                    $methods_to_show[$value] = $methods_selected[$value];
                }
            }
        }
        return $methods_to_show;
    }

    /**
     * This function is used to process on selected method during inline registration.
     *  (previously this function was in the miniorange_select_method.php file)
     * @param $authMethod
     * Selected 2FA method
     * @param $form_values
     * Additional array of form array to get phone number and additional parameters.
     * @return void
     * Redirected to page for configuration of selected method.
     */
    public static function process_selected_method($authMethod, $form_values=[]) {
        $customer         = new MiniorangeCustomerProfile();
        $auth_api_handler = new AuthenticationAPIHandler($customer->getCustomerID(), $customer->getAPIKey());
        $session          = self::getSession();
        $moMfaSession     = $session->get("mo_auth", null);
        $account          = User::load($moMfaSession['uid']);
        $email            = $account->get('mail')->value;

        self::moCreateUser($email);

        if (in_array($authMethod, self::mo_TOTP_2fa_mentods())) {
            $miniorange_user = new MiniorangeUser($customer->getCustomerID(), $email, NULL, NULL, NULL);
            $response = $auth_api_handler->getGoogleAuthSecret($miniorange_user);
            if (is_object($response) && $response->status == 'SUCCESS') {
                $moMfaSession['mo_challenge_response'] = $response;
                $moMfaSession['authentication_method'] = $authMethod;
                $session->set('mo_auth', $moMfaSession);
                $session->save();
            } else {
                self::mo_add_loggers_for_failures(is_object($response)?$response->message:'An unexpected error occured.', 'error');
                self::redirectUserToLoginPage('An error occurred while processing your request. Please try again after sometime.');
            }
        } elseif ($authMethod == AuthenticationType::$QR_CODE['code'] || $authMethod == AuthenticationType::$SOFT_TOKEN['code'] || $authMethod == AuthenticationType::$PUSH_NOTIFICATIONS['code']) {
            $miniorange_user = new MiniorangeUser($customer->getCustomerID(), $email, NULL, NULL, NULL);
            $response = $auth_api_handler->register($miniorange_user, AuthenticationType::$QR_CODE['code'], NULL, NULL, NULL);
            if (is_object($response) && $response->status == 'IN_PROGRESS') {
                $moMfaSession['mo_challenge_response'] = $response;
                $moMfaSession['authentication_method'] = $authMethod;
                $session->set('mo_auth', $moMfaSession);
                $session->save();
            } else {
                self::mo_add_loggers_for_failures(is_object($response)?$response->message:'An unexpected error occured.', 'error');
                self::redirectUserToLoginPage('An error occurred while processing your request. Please try again after sometime.');
            }
        } elseif ($authMethod == AuthenticationType::$SMS['code'] || $authMethod == AuthenticationType::$EMAIL['code'] || $authMethod == AuthenticationType::$SMS_AND_EMAIL['code'] || $authMethod == AuthenticationType::$OTP_OVER_PHONE['code'] || $authMethod == AuthenticationType::$EMAIL_VERIFICATION['code']) {
            $phoneNumber = trim($form_values['phone_full']);
            if ($authMethod == AuthenticationType::$SMS_AND_EMAIL['code']) {
                $miniorange_user = new MiniorangeUser($customer->getCustomerID(), NULL, $phoneNumber, NULL, $authMethod, $email);
            } elseif ($authMethod == AuthenticationType::$EMAIL['code'] || $authMethod == AuthenticationType::$EMAIL_VERIFICATION['code']) {
                $miniorange_user = new MiniorangeUser($customer->getCustomerID(), NULL, NULL, NULL, $authMethod, $email);
            } elseif ($authMethod == AuthenticationType::$SMS['code'] || $authMethod == AuthenticationType::$OTP_OVER_PHONE['code']) {
                $miniorange_user = new MiniorangeUser($customer->getCustomerID(), NULL, $phoneNumber, NULL, $authMethod, NULL);
            }

            $response = $auth_api_handler->challenge($miniorange_user);

              if (is_object($response) && $response->status == 'SUCCESS') {
                  \Drupal::messenger()->addStatus(t($response->message));
                  $moMfaSession['mo_challenge_response'] = $response;
                  $moMfaSession['authentication_method'] = $authMethod;
                  $moMfaSession['phone_number'] = $phoneNumber;
                  $session->set('mo_auth', $moMfaSession);
                  $session->save();
              } else {
                  $user_api_handler = new UsersAPIHandler($customer->getCustomerID(), $customer->getAPIKey());
                  $response = $user_api_handler->fetchLicense();

                  if (is_object($response) && ($response->smsRemaining == 0 || $response->emailRemaining == 0 || $response->ivrRemaining == 0)) {
                      self::mo_add_loggers_for_failures(t('The number of OTP transactions have exhausted. Please recharge your account with SMS/Email/IVR transactions.'), 'error');
                  }

                  self::mo_add_loggers_for_failures(is_object($response)?$response->message:'An unexpected error occured.', 'error');
                  self::redirectUserToLoginPage('An error occurred while processing your request. Please try again after sometime.');
              }
        } elseif ($authMethod == AuthenticationType::$KBA['code']) {
            $message = 'Please choose your security questions (KBA) and answer those:';
            \Drupal::messenger()->addStatus(t($message));
            $moMfaSession['authentication_method'] = $authMethod;
            $moMfaSession['mo_challenge_response'] = '';
            $session->set('mo_auth', $moMfaSession);
            $session->save();
        } elseif ($authMethod == AuthenticationType::$HARDWARE_TOKEN['code']) {
            $message = 'Insert your Hardware Token and touch the Hardware Token button to enter OTP';
            \Drupal::messenger()->addStatus(t($message));
            $moMfaSession['authentication_method'] = $authMethod;
            $moMfaSession['mo_challenge_response'] = '';
            $session->set('mo_auth', $moMfaSession);
            $session->save();
        }

        $url = Url::fromRoute('miniorange_2fa.configure_method', ['user' => $moMfaSession['uid']])->toString();
        $response = new RedirectResponse($url);
        $response->send();
        exit;
    }

    /**
     * This function is used to create user at dashboard
     *  (previously this function in the miniorange_select_method.php file)
     * @param $email
     * Email to create a user
     * @return void
     */
    public static function moCreateUser($email)
    {
        $utilities = new MoAuthUtilities();
        $customer = new MiniorangeCustomerProfile();
        $miniorange_user = new MiniorangeUser($customer->getCustomerID(), $email, NULL, NULL, AuthenticationType::$EMAIL['code']);
        $user_api_handler = new UsersAPIHandler($customer->getCustomerID(), $customer->getAPIKey());
        $response = $user_api_handler->search($miniorange_user);

        if (is_object($response) && $response->status == 'USER_NOT_FOUND') { //$response->status == 'USER_FOUND'
            //Create user in miniOrange dashboard
            $createResponse = $user_api_handler->create($miniorange_user);
            if (is_object($createResponse) && isset($createResponse->status) && $createResponse->status == 'SUCCESS') {
                $variables_and_values = array('mo_user_limit_exceed');
                $utilities->miniOrange_set_get_configurations($variables_and_values, 'CLEAR');
                //Update User Auth method to OTP Over EMAIL
                $updateResponse = $user_api_handler->update($miniorange_user);
                if (is_object($updateResponse) && $updateResponse->status != 'SUCCESS') {
                    $utilities->mo_add_loggers_for_failures($updateResponse->message, 'error');
                    $utilities->redirectUserToLoginPage('An error occurred while updating 2FA. Please contact your administrator.');
                }
            } elseif (is_object($createResponse) && isset($createResponse->status) && $createResponse->status == 'ERROR' && $createResponse->message == t('Your user creation limit has been completed. Please upgrade your license to add more users.')) {
                //Check if user creation limit is exceeded
                $variables_and_values = array('mo_user_limit_exceed' => TRUE);
                $utilities->miniOrange_set_get_configurations($variables_and_values, 'SET');
                $utilities->mo_add_loggers_for_failures($createResponse->message, 'error');
                $utilities->redirectUserToLoginPage('An error occurred while configuring 2FA. Please contact your administrator.');
           } else {
                $utilities->mo_add_loggers_for_failures($createResponse->message, 'error');
                $utilities->redirectUserToLoginPage('An error occurred while processing your request. Please contact your administrator.');
            }
        }
    }

    /** Function to check that given method requires phone number or not */
    public static function is_required_phone_number($method) {
        return in_array($method, ['WHATSAPP', 'PHONE VERIFICATION', 'SMS', 'SMS AND EMAIL']);
    }

    /** Below are 3 functions get2FAMethodType(), generateMethodeTypeRows(), generateCheckbox() which are used
     * to generate table rows according to 2FA method type i.e. TOTO, OTP, Other
     */

    /**
     * @param $mo_get_2fa_methods
     * All 2FA methods
     *
     * @return array
     * Array contains nested array of TOTP, OTP, and Other 2Fa methods.
     */
    public static function get2FAMethodType($mo_get_2fa_methods) {
        $totp  = [];
        $otp   = [];
        $other = [];
        $mo_2fa_method_type = [];

        foreach ($mo_get_2fa_methods as $sysName => $displayName) {
            $method_array = AuthenticationType::getAuthType($sysName);
            switch ($method_array['type']) {
              case 'TOTP':
                $totp[$method_array['code']] = $method_array['name'];
                break;
              case 'OTP':
                $otp[$method_array['code']] = $method_array['name'];
                break;
              case 'OTHER':
                $other[$method_array['code']] = $method_array['name'];
                break;
            }
        }

        $mo_2fa_method_type['totp'] = $totp;
        $mo_2fa_method_type['otp'] = $otp;
        $mo_2fa_method_type['other'] = $other;

        return $mo_2fa_method_type;
    }

    /**
     * This is the main function which generate rows of table according to 2FA method types.
     * @param $mo_2fa_method_type
     * Array contains nested array of TOTP, OTP, and Other 2Fa methods.
     * @param $selected_2fa_methods
     * Array of previously allowed 2FA methods
     * @param \Drupal\Core\Form\FormStateInterface $form_state
     * Current form_state
     *
     * @return array
     * Array of table rows
     */
    public static function generateMethodeTypeRows($mo_2fa_method_type, $selected_2fa_methods,  FormStateInterface $form_state) {
        $totp_methods  =  $mo_2fa_method_type['totp'];
        $otp_methods   =  $mo_2fa_method_type['otp'];
        $other_methods =  $mo_2fa_method_type['other'];
        $count         = max(count($totp_methods), count($otp_methods), count($other_methods));

        $table_row_array   = [];
        $method_name_array = [];

        for ($index = 0; $index < $count; $index++) {
            $table_row_array[$index] = [
                'totp'  => isset($totp_methods[key($totp_methods)])    ? self::generateCheckbox($totp_methods, $selected_2fa_methods)  : ['#markup' => ''],
                'otp'   => isset($otp_methods[key($otp_methods)])      ? self::generateCheckbox($otp_methods, $selected_2fa_methods)   : ['#markup' => ''],
                'other' => isset($other_methods[key($other_methods)])  ? self::generateCheckbox($other_methods, $selected_2fa_methods) : ['#markup' => ''],
            ];

            $method_name_array[$index] = [
                'totp'  => key($totp_methods)  ?? '',
                'otp'   => key($otp_methods)   ?? '',
                'other' => key($other_methods) ?? '',
            ];

            next($totp_methods);
            next($otp_methods);
            next($other_methods);
        }

          $form_state->set('table-array',$method_name_array);
          return $table_row_array;
    }

    /**
     * Small function which generate checkbox form element for generateRows function
     * @param $method
     * @param $selected_2fa_methods
     *
     * @return array
     */
    public static function generateCheckbox($method, $selected_2fa_methods) {
        return [
            '#title' => $method[key($method)],
            '#type' => 'checkbox',
            '#default_value' => is_array($selected_2fa_methods) ? array_key_exists(key($method), $selected_2fa_methods) ? TRUE : FALSE : TRUE,
            '#states' => ['disabled' => [':input[name = "mo_auth_enable_2fa_methods_for_inline"]' => ['checked' => FALSE],],],
        ];
    }

    /**
     * Process allowed 2FA methods in inline
     * registration flow
     * @param $form_values
     * @return string
     */
    public static function getSelected2faMethods(FormStateInterface $form_state, $table_name)
    {
        $mo_get_2fa_methods     = MoAuthUtilities::get_2fa_methods_for_inline_registration(FALSE);
        $mo_allowed_2fa_methods = array();
        $method_names           = $form_state->get('table-array');
        $check_box_values       = $form_state->getValue($table_name);

        for ($row = 0; $row < count($method_names); $row++) {
            if(isset($check_box_values[$row]['totp']) && $check_box_values[$row]['totp']==1) {
                $mo_allowed_2fa_methods[$method_names[$row]['totp']] = $mo_get_2fa_methods[$method_names[$row]['totp']] ;
            }

            if(isset($check_box_values[$row]['otp']) && $check_box_values[$row]['otp']==1) {
                $mo_allowed_2fa_methods[$method_names[$row]['otp']] = $mo_get_2fa_methods[$method_names[$row]['otp']];
            }

            if(isset($check_box_values[$row]['other']) && $check_box_values[$row]['other']==1) {
                $mo_allowed_2fa_methods[$method_names[$row]['other']] = $mo_get_2fa_methods[$method_names[$row]['other']];
            }
        }

        return !empty($mo_allowed_2fa_methods) ? json_encode($mo_allowed_2fa_methods) : '';
    }

    /**
     * Function to generate RBA device information
     */
    public static function generateRBADeviceInfo()
    {
        $device_info = md5($_SERVER['HTTP_USER_AGENT'] . $_SERVER['REMOTE_ADDR']);
        return $device_info;
    }

    /**
     * Function to generate RBA expiry timestamp
     */
    public static function generateRBAExpiryTimestamp()
    {
        $current_time = \Drupal::time()->getCurrentTime();
        $variables_and_values = array(
            'mo_auth_rba_duration',
        );
        $mo_db_values = self::miniOrange_set_get_configurations($variables_and_values, 'GET');
        $rba_timestamp = $current_time + (60*60*24 * $mo_db_values['mo_auth_rba_duration']);
        return $rba_timestamp;
    }

    /**
     * Function to fetch saved RBA devices
     */
    public static function fetchSavedRBADevices($user_id) {
        $saved_rba_devices = \Drupal::database()->select('UserAuthenticationType','user')->condition('user.uid',$user_id , '=')
            ->fields('user', ['device_info'])->execute()
            ->fetchAll();
        $return_value = array();
        if($saved_rba_devices[0]->device_info != NULL){
            $return_value = json_decode($saved_rba_devices[0]->device_info, TRUE);
        }
        return $return_value;
    }

    public static function getCronInformation() {
      $cron_run_interval = \Drupal::config('automated_cron.settings')->get('interval');
      $cron_run_interval = $cron_run_interval !=0 ? \Drupal::service('date.formatter')->formatInterval($cron_run_interval) : 'Never';
      $last_cron_run     = \Drupal::state()->get('system.cron_last');
      $last_cron_run     = \Drupal::service('date.formatter')->formatTimeDiffSince($last_cron_run);
      return $cron_run_interval . t(' (Last run: @time ago.)', ['@time' => (string)$last_cron_run]);
    }

    public static function validateUniqueKBA(FormStateInterface $form_state) {
      $pattern = MoAuthConstants::ALPHANUMERIC_PATTERN;
      $message           = t('Answers must be unique.');
      $is_valid_length   = [TRUE, TRUE, TRUE];
      $is_valid_pattern  = [TRUE, TRUE, TRUE];
      $kba_answer_length = MoAuthConstants::KBA_ANSWER_LENGTH;

      for ($count = 1; $count <= 3; $count++) {
        $answer[$count] = trim($form_state->getValue('mo_auth_answer' . $count));
        if(strlen($answer[$count]) < $kba_answer_length && !preg_match('/^[\w\s]+$/', $answer[$count])) {
          unset($is_valid_length[$count - 1]);
          unset($is_valid_pattern[$count - 1]);
        } elseif (strlen($answer[$count]) < $kba_answer_length) {
          unset($is_valid_length[$count - 1]);
        } elseif (!preg_match($pattern, $answer[$count])) {
          unset($is_valid_pattern[$count - 1]);
        }
      }

      if((count($is_valid_length) >= 2) && (count($is_valid_pattern) >=2)) {
        if ($answer[1] == $answer[2] && $answer[1] == $answer[3] && $answer[2] == $answer[3]) {
          $form_state->setErrorByName('mo_auth_answer1', $message);
          $form_state->setErrorByName('mo_auth_answer2', $message);
          $form_state->setErrorByName('mo_auth_answer3', $message);
        } else if ($answer[1] == $answer[2]) {
          $form_state->setErrorByName('mo_auth_answer2', $message);
        } else if ($answer[2] == $answer[3]) {
          $form_state->setErrorByName('mo_auth_answer3', $message);
        } else if ($answer[1] == $answer[3]) {
          $form_state->setErrorByName('mo_auth_answer3', $message);
        }
      }
}


}

