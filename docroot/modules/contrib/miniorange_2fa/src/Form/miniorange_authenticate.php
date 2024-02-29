<?php

namespace Drupal\miniorange_2fa\Form;

use Drupal\Core\Url;
use Drupal\user\Entity\User;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\miniorange_2fa\MiniorangeUser;
use Drupal\miniorange_2fa\MoAuthUtilities;
use Drupal\miniorange_2fa\MoAuthConstants;
use Drupal\miniorange_2fa\AuthenticationType;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\miniorange_2fa\AuthenticationAPIHandler;
use Drupal\miniorange_2fa\MiniorangeCustomerProfile;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * @file
 *  This is used to authenticate user during
 *     login.
 */
class miniorange_authenticate extends FormBase
{
    public function getFormId()
    {
        return 'mo_auth_miniorange_authenticate';
    }

    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $utilities = new MoAuthUtilities();
        $session = $utilities->getSession();
        $moMfaSession = $session->get("mo_auth", null);

        if (is_null($moMfaSession) || !isset($moMfaSession['uid']) || !isset($moMfaSession['status']) || $moMfaSession['status'] !== '1ST_FACTOR_AUTHENTICATED' || !is_object($moMfaSession['mo_challenge_response'])) {
            $session->remove('mo_auth');
            $utilities->mo_add_loggers_for_failures('Error at ' . __FILE__ . ' Function: ' . __FUNCTION__ . ' Line number: ' . __LINE__, 'error');
            $message = 'Something went wrong. Please try again.';
            $utilities->redirectUserToLoginPage($message);
        }

        if ($moMfaSession['mo_challenge_response']->allowedAttempts == 0) {
            $session->remove('mo_auth');
            $message = 'Allowed login limit reached. Please try again';
            $utilities->redirectUserToLoginPage($message);
        }

        $url_parts = $utilities->mo_auth_get_url_parts();
        end($url_parts);
        $user_id = prev($url_parts);
        if ($moMfaSession['uid'] != $user_id) {
            return $form;
        }

        $custom_attribute = $utilities->get_users_custom_attribute($user_id);
        $activatedAuthMethods = $custom_attribute[0]->activated_auth_methods;

        self::moGenerateForm($form, $moMfaSession['mo_challenge_response'], $activatedAuthMethods);

        $variables_and_values = array(
            'mo_auth_rba',
            'mo_auth_use_only_2nd_factor',
            'rba_allowed_devices',
        );
        $mo_db_values = $utilities->miniOrange_set_get_configurations($variables_and_values, 'GET');

        /**
         * Add remember my device form element if RBA is enabled && use only second factor is disabled && count of remembered device is less than the allowed devices
         */
        ;
        $current_rba_device_count = count(MoAuthUtilities::fetchSavedRBADevices($user_id));
        $allowed_rba_device_count = $mo_db_values['rba_allowed_devices'];

        if ($mo_db_values['mo_auth_rba'] && !$mo_db_values['mo_auth_use_only_2nd_factor'] && $allowed_rba_device_count > $current_rba_device_count) {
            $form['mo_auth_remember_device'] = array(
                '#type' => 'checkbox',
                '#title' => t('Remember this device.'),
            );
        }

        if ($moMfaSession['mo_challenge_response']->authType != AuthenticationType::$KBA['code'] && $utilities->mo_auth_is_kba_configured($user_id)) {
            $form['actions']['forgot'] = array(
                '#type' => 'submit',
                '#id' => 'BackUpKBA',
                '#value' => t('Login with KBA'),
                '#submit' => array('::moAuthLoginUsingBackupMethod'),
                '#limit_validation_errors' => array(), //skip the required field validation
            );
        }

        return $form;
    }

    public function moGenerateForm(array &$form, $challengeResponse, $activatedAuthMethods)
    {
        global $base_url;
        $authType = $challengeResponse->authType;
        $moDescription = isset($challengeResponse->description) ? $challengeResponse->description : '';

        if ($authType == AuthenticationType::$SMS['code'] || $authType == AuthenticationType::$OTP_OVER_EMAIL['code'] || $authType == AuthenticationType::$SMS_AND_EMAIL['code'] || $authType == AuthenticationType::$OTP_OVER_PHONE['code'] || $authType == AuthenticationType::$GOOGLE_AUTHENTICATOR['code'] || $authType == AuthenticationType::$SOFT_TOKEN['code']) {
            $moTitle = t('Enter the passcode(OTP) you received');
            if ($authType == AuthenticationType::$GOOGLE_AUTHENTICATOR['code'] || $authType == AuthenticationType::$SOFT_TOKEN['code']) {
                $appName = 'Google';
                if ($activatedAuthMethods == AuthenticationType::$MICROSOFT_AUTHENTICATOR['code'])
                    $appName = 'Microsoft';
                elseif ($activatedAuthMethods == AuthenticationType::$OKTA_VERIFY['code'])
                    $appName = 'Okta';
                elseif ($activatedAuthMethods == AuthenticationType::$AUTHY_AUTHENTICATOR['code'])
                    $appName = 'Authy';
                elseif ($activatedAuthMethods == AuthenticationType::$LASTPASS_AUTHENTICATOR['code'])
                    $appName = 'LastPass';
                elseif ($activatedAuthMethods == AuthenticationType::$DUO_AUTHENTICATOR['code'])
                    $appName = 'Duo';
                elseif ($activatedAuthMethods == AuthenticationType::$SOFT_TOKEN['code']) //miniOrange authenticator
                    $appName = 'miniOrange';
                elseif ($activatedAuthMethods == AuthenticationType::$_2FAS_AUTHENTICATOR['code'])
                    $appName = '2FAS';
                elseif ($activatedAuthMethods == AuthenticationType::$ZOHO_ONEAUTH['code'])
                    $appName = 'Zoho';
                elseif ($activatedAuthMethods == AuthenticationType::$RSA_SECURID['code'])
                    $appName = 'SecurID';

                $moTitle = t('Enter the passcode generated on your %appName Authenticator app.', array('%appName' => $appName));
            }

            $form['mo_auth_passcode_textfield'] = [
                '#type' => 'textfield',
                '#title' => $moTitle,
                '#attributes' => array('placeholder' => t('Enter the passcode'), 'autofocus' => 'true', 'autocomplete' => 'off'),
                '#required' => TRUE,
                '#description' => t($moDescription),
                '#maxlength' => 8,
            ];
            $form['actions']['#type'] = 'actions';
            $form['actions']['login'] = [
                '#type' => 'submit',
                '#button_type' => 'primary',
                '#value' => t('Verify'),
            ];
        } elseif ($authType == AuthenticationType::$KBA['code']) {
            $questionNumber = 1;
            $questions = isset($challengeResponse->questions) ? $challengeResponse->questions : '';
            if (is_array($questions)) {
                foreach ($questions as $ques) {
                    $form['mo_auth_kba_question' . $questionNumber] = array(
                        '#type' => 'label',
                        '#title' => t($questionNumber . '. ' . $ques->question),
                    );
                    $form['mo_auth_kba_answer' . $questionNumber] = array(
                        '#type' => 'textfield',
                        '#required' => TRUE,
                        '#attributes' => array(
                            'placeholder' => t('Enter your answer'),
                            'class' => ['custom-kba-validation'],
                            'pattern'  => MoAuthConstants::ALPHANUMERIC_LENGTH_PATTERN,
                            'title' => $this->t(MoAuthConstants::VALIDATION_MESSAGE),
                        ),
                    );
                    $questionNumber++;
                }
                $form['actions']['#type'] = 'actions';
                $form['actions']['login'] = [
                    '#type' => 'submit',
                    '#button_type' => 'primary',
                    '#value' => t('Verify'),
                ];
                /**
                 * Check if Email transactions are available
                 */
                if (MoAuthUtilities::checkEmailTransaction()) {
                    $form['actions']['forgot'] = array(
                        '#type' => 'submit',
                        '#id' => 'OTPOverEmail',
                        '#value' => t('Login using OTP Over Email'),
                        '#limit_validation_errors' => array(), //skip the required field validation
                        '#submit' => array('::moAuthLoginUsingBackupMethod'),
                    );
                }
            }
        } elseif ($authType == AuthenticationType::$QR_CODE['code'] || $authType == AuthenticationType::$PUSH_NOTIFICATIONS['code'] || $authType == AuthenticationType::$EMAIL_VERIFICATION['code']) { //QR code authentication, Push Notification and Email Verification
            $form['markup_library'] = array(
                '#attached' => array(
                    'library' => array(
                        "miniorange_2fa/miniorange_2fa.license",
                    )
                ),
            );
            $form['mo_auth_qrcode_and_push_authentication_label'] = array(
                '#type' => 'label',
                '#title' => t($challengeResponse->message . ' [Use miniOrange authenticator app]'),
            );
            /**
             * Check if Push notification was challenged
             */
            if ($authType == AuthenticationType::$PUSH_NOTIFICATIONS['code'] || $authType == AuthenticationType::$EMAIL_VERIFICATION['code']) {
                $image_path = MoAuthUtilities::fileCreateUrl($base_url . '/' . MoAuthUtilities::moGetModulePath() . '/includes/images/ajax-loader-login.gif');
                $form['header']['#markup'] = '<img class="mo2f_image" src="' . $image_path . '">';
            } else {
                $qrCodeString = isset($challengeResponse->qrCode) ? $challengeResponse->qrCode : '';
                $qrCode = new FormattableMarkup('<img class="mo2f_image" src="data:image/jpg;base64, ' . $qrCodeString . '"/>', [':src' => $qrCodeString]);
                $form['actions_qrcode'] = array(
                    '#markup' => $qrCode
                );
            }
            $form['txId'] = array(
                '#type' => 'hidden',
                '#value' => $challengeResponse->txId,
            );
            $form['url'] = array(
                '#type' => 'hidden',
                '#value' => MoAuthConstants::getBaseUrl() . MoAuthConstants::$AUTH_STATUS_API,
            );
            $form['actions']['#type'] = 'actions';
            $form['actions']['login'] = [
                '#type' => 'submit',
                '#value' => t('Verify'),
                '#button_type' => 'primary',
                '#attributes' => array('style' => 'visibility:hidden !important;float:right !important;'),
            ];
        } elseif ($authType == AuthenticationType::$HARDWARE_TOKEN['code']) {
            $form['mo_auth_passcode_textfield'] = [
                '#type' => 'textfield',
                '#title' => t('Enter the code by tapping on the hardware token'),
                '#attributes' => array('autofocus' => 'true', 'autocomplete' => 'off'),
                '#description' => t($moDescription),
                '#required' => TRUE,
                '#maxlength' => 88,
            ];
            $form['actions']['#type'] = 'actions';
            $form['actions']['login'] = [
                '#type' => 'submit',
                '#button_type' => 'primary',
                '#value' => t('Verify'),
            ];
        }
    }

    public function validateForm(array &$form, FormStateInterface $form_state)
    {
    }

    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $utilities = new MoAuthUtilities();
        $session = $utilities->getSession();
        $moMfaSession = $session->get("mo_auth", null);
        $challengeResponse = $moMfaSession['mo_challenge_response'];

        $formValues = $form_state->getUserInput();
        $passcode = isset($formValues['mo_auth_passcode_textfield']) ? $formValues['mo_auth_passcode_textfield'] : '';
        $txId = $challengeResponse->txId;

        $url_parts = $utilities->mo_auth_get_url_parts();
        end($url_parts);
        $user_id = prev($url_parts);

        if (!isset($moMfaSession['uid']) || $moMfaSession['uid'] != $user_id) {
            $session->remove('mo_auth');
            $utilities->mo_add_loggers_for_failures('URL change detected', 'error');
            $message = 'Authentication failed try again. URL change detected while login.';
            $utilities->redirectUserToLoginPage($message);
        }

        $custom_attribute = $utilities->get_users_custom_attribute($user_id);
        $user_email = $custom_attribute[0]->miniorange_registered_email;
        $authTypeArr = AuthenticationType::getAuthType($challengeResponse->authType);

        $response = '';
        /**
         * Handle submit of OTP Over SMS, OTP Over EMAIL, OTP Over SMS and EMAIL, OTP Over PHONE call and all TOTP methods
         */
        if ($authTypeArr['code'] == AuthenticationType::$OTP_OVER_EMAIL['code']
            || $authTypeArr['code'] == AuthenticationType::$SMS['code']
            || $authTypeArr['code'] == AuthenticationType::$SMS_AND_EMAIL['code']
            || $authTypeArr['code'] == AuthenticationType::$OTP_OVER_PHONE['code']
            || $authTypeArr['code'] == AuthenticationType::$GOOGLE_AUTHENTICATOR['code'] //this condition checks for all TOTP methods
            || $authTypeArr['code'] == AuthenticationType::$SOFT_TOKEN['code']) { //miniOrange Authenticator app

            $response = self::moCallValidateAPI($user_email, $txId, $passcode, NULL);

            /**
             * Check if invalid OTP provided
             */
            if (is_object($response) && $response->status == 'FAILED' && $response->message === 'Invalid OTP provided. Please try again.') {
                //Limit the number of failed attempts
                $challengeResponse->allowedAttempts = $challengeResponse->allowedAttempts - 1;
                $utilities->mo_add_loggers_for_failures('Failed login attempt for ' . $user_email . '. (' . $response->message . '). Remaining Attempts: ' . $challengeResponse->allowedAttempts, 'error');
                \Drupal::messenger()->addError(t($response->message));
                return;
            }
        } elseif ($authTypeArr['code'] == AuthenticationType::$KBA['code']) {
            $moQuestionAndAnswers = array();
            $questionNumber = 1;
            $questions = isset($challengeResponse->questions) ? $challengeResponse->questions : '';
            if (is_array($questions)) {
                foreach ($questions as $ques) {
                    $moQuestion = $ques->question;
                    $moAnswer = trim($formValues['mo_auth_kba_answer' . $questionNumber]);
                    $questionArr = array(
                        "question" => $moQuestion,
                        "answer" => $moAnswer,
                    );
                    array_push($moQuestionAndAnswers, $questionArr);
                    $questionNumber++;
                }
            }
            $response = self::moCallValidateAPI($user_email, $txId, '', $moQuestionAndAnswers, NULL);
            /**
             * Check if invalid OTP provided
             */
            if (is_object($response) && $response->status == 'FAILED' && $response->message === 'The answers you have provided are incorrect.') {
                //Limit the number of failed attempts
                $challengeResponse->allowedAttempts = $challengeResponse->allowedAttempts - 1;
                $utilities->mo_add_loggers_for_failures('Failed login attempt for ' . $user_email . '. (' . $response->message . '). Remaining Attempts: ' . $challengeResponse->allowedAttempts, 'error');
                \Drupal::messenger()->addError(t($response->message . ' Please try again.'));
                return;
            }
        } elseif ($authTypeArr['code'] == AuthenticationType::$QR_CODE['code'] || $authTypeArr['code'] == AuthenticationType::$PUSH_NOTIFICATIONS['code'] || $authTypeArr['code'] == AuthenticationType::$EMAIL_VERIFICATION['code']) {
            $response = self::moCallValidateAPI($user_email, $txId, '', '', 'miniOrangeAuthenticator');
            /**
             * Check if Validation is in progess
             */
            if (is_object($response)) {
                if ($response->status == 'IN_PROGRESS') {
                    $utilities->mo_add_loggers_for_failures($response->message . ' for ' . $user_email, 'error');
                    \Drupal::messenger()->addError(t($response->message));
                    return;
                } elseif ($response->status == 'DENIED') {
                    $session->remove('mo_auth');
                    $utilities->mo_add_loggers_for_failures($response->message . ' = ' . $user_email, 'error');
                    $message = $response->message;
                    $utilities->redirectUserToLoginPage($message);
                } elseif ($response->status == 'ERROR' && $response->message == 'Your mobile validation request timed out. Please try again.') {
                    $session->remove('mo_auth');
                    $utilities->mo_add_loggers_for_failures($response->message . ' for ' . $user_email, 'error');
                    $message = $response->message;
                    $utilities->redirectUserToLoginPage($message);
                }
            }
        } elseif ($authTypeArr['code'] == AuthenticationType::$HARDWARE_TOKEN['code']) {
            $response = self::moCallValidateAPI($user_email, $txId, $passcode, NULL);

            /**
             * Check if invalid OTP provided
             */
            if (is_object($response) && $response->status == 'FAILED' && $response->message === 'Invalid OTP provided. Please try again.') {
                //Limit the number of failed attempts
                $challengeResponse->allowedAttempts = $challengeResponse->allowedAttempts - 1;
                $utilities->mo_add_loggers_for_failures('Failed login attempt for ' . $user_email . '. (' . $response->message . '). Remaining Attempts: ' . $challengeResponse->allowedAttempts, 'error');
                \Drupal::messenger()->addError(t($response->message));
                return;
            }
        }

        /**
         * Read validate response
         */
        if ($response->status === 'SUCCESS') {
            /**
             * Check if flow is from the password reset link
             */
            if (isset($moMfaSession['moResetPass']) && is_array($moMfaSession['moResetPass']) && $moMfaSession['moResetPass'][0] === 'moResetPass') {
                $session->set('mo_2fa_invoked_for_password_reset', array('is_2fa_invoked' => TRUE));
                $session->save();
                $response = new RedirectResponse($moMfaSession['moResetPass'][1]);
                $response->send();
                exit;
            }

            if(isset($formValues['mo_auth_remember_device'])){
                $device_info = MoAuthUtilities::generateRBADeviceInfo();
                $expiry_timestamp = MoAuthUtilities::generateRBAExpiryTimestamp();
                $saved_device_info = MoAuthUtilities::fetchSavedRBADevices($user_id);
                $saved_device_info[$expiry_timestamp] = $device_info;
                \Drupal::database()->update('UserAuthenticationType')->fields(['device_info'=>json_encode($saved_device_info)])->condition('uid', $user_id, '=')->execute();
            }

            $session->remove('mo_auth');
            $user = User::load($user_id);
            user_login_finalize($user);

            /**
             * Redirect user after login
             */
            $variables_and_values = array(
                'mo_auth_redirect_user_after_login',
            );

            $mo_db_values = $utilities->miniOrange_set_get_configurations($variables_and_values, 'GET');
            if(isset($_COOKIE['Drupal_visitor_destination'])){
                global $base_url;
                $url = $base_url . '/' . $_COOKIE['Drupal_visitor_destination'];
                user_cookie_delete('destination');
            }
            else {
                $url = isset($mo_db_values['mo_auth_redirect_user_after_login']) && !empty($mo_db_values['mo_auth_redirect_user_after_login']) ? $mo_db_values['mo_auth_redirect_user_after_login'] : Url::fromRoute('user.page')->toString();
            }            $response = new RedirectResponse($url);
            $response->send();
            exit;

        } elseif ($response->status === 'FAILED') {
            $session->remove('mo_auth');
            $utilities->mo_add_loggers_for_failures('Failed login attempt for ' . $user_email . '. (' . $response->message . ')', 'error');
            $message = 'Authentication failed try again';
            $utilities->redirectUserToLoginPage($message);
        } else {
            $session->remove('mo_auth');
            $utilities->mo_add_loggers_for_failures('Failed login attempt for ' . $user_email . '. (' . $response->message . ')', 'error');
            $message = 'An error occurred while processing your request. Please try again.';
            $utilities->redirectUserToLoginPage($message);
        }
    }

    public function moCallValidateAPI($userEmail, $txId, $passcode, $moQuestionAndAnswers = NULL, $validateType = NULL)
    {
        $customer = new MiniorangeCustomerProfile();
        $miniorange_user = new MiniorangeUser($customer->getCustomerID(), $userEmail, NULL, NULL, NULL);
        $auth_api_handler = new AuthenticationAPIHandler($customer->getCustomerID(), $customer->getAPIKey());
        /**
         * Handle Auto submit type methods link QR code authentication and Push Notification
         */
        if ($validateType === 'miniOrangeAuthenticator') {
            $response = $auth_api_handler->getAuthStatus($txId);
        } else {
            $response = $auth_api_handler->validate($miniorange_user, $txId, $passcode, $moQuestionAndAnswers);
        }
        return $response;
    }

    public function moAuthLoginUsingBackupMethod(array &$form, FormStateInterface $form_state)
    {
        $clickedElement = $form_state->getTriggeringElement()['#id'];
        $authType = AuthenticationType::$KBA['code'];
        if ($clickedElement === 'OTPOverEmail') {
            $authType = AuthenticationType::$OTP_OVER_EMAIL['code'];
        }

        $utilities = new MoAuthUtilities();
        $session = $utilities->getSession();
        $moMfaSession = $session->get("mo_auth", null);
        $custom_attribute = $utilities->get_users_custom_attribute($moMfaSession['uid']);
        $userEmail = $custom_attribute[0]->miniorange_registered_email;

        $challengeSuccess = $utilities->mo_auth_challenge_user($userEmail, $authType);

        if ($challengeSuccess !== TRUE) {
            $session->remove('mo_auth');
            $utilities->redirectUserToLoginPage();
        }
    }
}