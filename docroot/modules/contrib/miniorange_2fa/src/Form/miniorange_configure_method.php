<?php

namespace Drupal\miniorange_2fa\Form;

use Drupal\Core\Url;
use Drupal\user\Entity\User;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\miniorange_2fa\MiniorangeUser;
use Drupal\miniorange_2fa\UsersAPIHandler;
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
class miniorange_configure_method extends FormBase
{
    CONST PATTERN_ONE = MoAuthConstants::ALPHANUMERIC_PATTERN;
    CONST PATTERN_TWO = MoAuthConstants::ALPHANUMERIC_LENGTH_PATTERN;

    public function getFormId()
    {
        return 'mo_auth_configure_method';
    }

    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $utilities = new MoAuthUtilities();
        $session = $utilities->getSession();
        $moMfaSession = $session->get("mo_auth", null);

        if (is_null($moMfaSession) || !isset($moMfaSession['uid']) || !isset($moMfaSession['status']) || $moMfaSession['status'] !== '1ST_FACTOR_AUTHENTICATED' || !isset($moMfaSession['authentication_method'])) {
            $session->remove('mo_auth');
            $utilities->mo_add_loggers_for_failures('Error at ' . __FILE__ . ' Function: ' . __FUNCTION__ . ' Line number: ' . __LINE__, 'error');
            $message = 'Something went wrong. Please try again.';
            $utilities->redirectUserToLoginPage($message);
        }

        $url_parts = $utilities->mo_auth_get_url_parts();
        end($url_parts);
        $user_id = prev($url_parts);
        if ($moMfaSession['uid'] != $user_id) {
            $session->remove('mo_auth');
            $utilities->mo_add_loggers_for_failures('URL change detected', 'error');
            $message = 'Something went wrong. Please try again.';
            $utilities->redirectUserToLoginPage($message);
        }

        self::moGenerateInlineForm($form, $moMfaSession['mo_challenge_response'], $moMfaSession['authentication_method']);

        //TODO: hide this button if mo authenticator
        $form['actions']['#type'] = 'actions';
        $form['actions']['login'] = [
            '#type' => 'submit',
            '#button_type' => 'primary',
            '#value' => t('Submit'),
        ];

        $form['actions']['cancel'] = array(
            '#type' => 'submit',
            '#value' => t('Cancel'),
            '#button_type' => 'danger',
            '#submit' => array('::moCancelInlineRegistration'),
            '#limit_validation_errors' => array(), //skip the required field validation
        );

        return $form;
    }

    public function moGenerateInlineForm(array &$form, $challengeResponse, $authMethod)
    {
      $utilities = new MoAuthUtilities();
      $app_name = $utilities->get_2fa_methods_for_inline_registration(TRUE)[$authMethod];
      if (in_array($authMethod, $utilities->mo_TOTP_2fa_mentods())) {
            $form['mo_auth_totp_label'] = array(
                '#type' => 'label',
                '#prefix' => '<h3>Configure ' . $app_name . '</h3>',
                '#title' => t('Step 1: ' . $challengeResponse->message),
            );
            $qrCodeString = isset($challengeResponse->qrCodeData) ? $challengeResponse->qrCodeData : '';
            $qrCode = new FormattableMarkup('<img class="mo2f_image" src="data:image/jpg;base64, ' . $qrCodeString . '"/>', [':src' => $qrCodeString]);
            $form['actions_qrcodeData'] = array(
                '#markup' => $qrCode
            );
            $form['mo_auth_passcode_textfield'] = [
                '#type' => 'textfield',
                '#title' => t('Step 2: Enter the passcode generated on your '.$app_name.' app'),
                '#attributes' => array('placeholder' => t('Enter the passcode'), 'autofocus' => 'true', 'autocomplete' => 'off'),
                '#required' => TRUE,
                '#maxlength' => 8,
            ];
        } elseif ($authMethod == AuthenticationType::$QR_CODE['code'] || $authMethod == AuthenticationType::$SOFT_TOKEN['code'] || $authMethod == AuthenticationType::$PUSH_NOTIFICATIONS['code']) {
            $form['markup_library'] = array(
                '#attached' => [
                    'library' => [
                        "miniorange_2fa/miniorange_2fa.license",
                    ]
                ],
            );
            $form['mo_auth_mo_authenticator_label'] = array(
                '#type' => 'label',
                '#title' => t('Scan the QR Code from miniOrange Authenticator app'),
            );
            $qrCodeString = isset($challengeResponse->qrCode) ? $challengeResponse->qrCode : '';
            $qrCode = new FormattableMarkup('<img class="mo2f_image" src="data:image/jpg;base64, ' . $qrCodeString . '"/>', [':src' => $qrCodeString]);
            $form['actions_qrcode'] = array(
                '#markup' => $qrCode
            );
            $form['txId'] = array(
                '#type' => 'hidden',
                '#value' => $challengeResponse->txId,
            );
            $form['url'] = array(
                '#type' => 'hidden',
                '#value' => MoAuthConstants::getBaseUrl() . MoAuthConstants::$AUTH_REGISTRATION_STATUS_API,
            );
        } elseif ($authMethod == AuthenticationType::$SMS['code'] || $authMethod == AuthenticationType::$EMAIL['code'] || $authMethod == AuthenticationType::$SMS_AND_EMAIL['code'] || $authMethod == AuthenticationType::$OTP_OVER_PHONE['code']) {
            $form['mo_auth_passcode_textfield'] = [
                '#type' => 'textfield',
                '#title' => t('Enter the passcode(OTP) you received'),
                '#attributes' => array('placeholder' => t('Enter the passcode'), 'autofocus' => 'true', 'autocomplete' => 'off'),
                '#required' => TRUE,
                '#maxlength' => 8,
            ];
        } elseif ($authMethod == AuthenticationType::$EMAIL_VERIFICATION['code']) {
            global $base_url;
            $form['markup_library'] = array(
                '#attached' => array(
                    'library' => array(
                        "miniorange_2fa/miniorange_2fa.license",
                    )
                ),
            );
            $form['mo_auth_otp_label'] = array(
                '#type' => 'label',
                '#title' => t($challengeResponse->message),
            );
            $image_path = $utilities->fileCreateUrl($base_url . '/' . $utilities->moGetModulePath() . '/includes/images/ajax-loader-login.gif');
            $form['header']['#markup'] = '<img class="mo2f_image" src="' . $image_path . '">';
            $form['txId'] = array(
                '#type' => 'hidden',
                '#value' => $challengeResponse->txId,
            );
            $form['url'] = array(
                '#type' => 'hidden',
                '#value' => MoAuthConstants::getBaseUrl() . MoAuthConstants::$AUTH_STATUS_API,
            );
        } elseif ($authMethod == AuthenticationType::$KBA['code']) {
            $questionSetOne = $utilities->mo_get_kba_questions('ONE');
            $questionSetTwo = $utilities->mo_get_kba_questions('TWO');

            $form['#attached']['library'][] = 'miniorange_2fa/miniorange_2fa.custom_kba_validation';

            $form['mo_auth_question1'] = array(
                '#type' => 'select',
                '#title' => t('Question 1'),
                '#options' => $questionSetOne,
                '#attributes' => array('style' => 'width:60%;'),
                '#required' => TRUE,
            );

            $form['mo_auth_answer1'] = array(
                '#type' => 'textfield',
                '#attributes' => array(
                    'placeholder' => t('Enter your answer'),
                    'style' => 'width:60%;',
                    'class' => ['custom-kba-validation'],
                    'id' => 'kba-answer-1',
                    'pattern' => self::PATTERN_TWO,
                    'title' => $this->t(MoAuthConstants::VALIDATION_MESSAGE),
                ),
                '#required' => TRUE,
                '#element_validate' => array('::validate_answer'),
            );
            $form['mo_auth_question2'] = array(
                '#type' => 'select',
                '#title' => t('Question 2'),
                '#options' => $questionSetTwo,
                '#attributes' => array('style' => 'width:60%;'),
                '#required' => TRUE,
            );

            $form['mo_auth_answer2'] = array(
                '#type' => 'textfield',
                '#attributes' => array(
                    'placeholder' => t('Enter your answer'),
                    'style' => 'width:60%;',
                    'class' => ['custom-kba-validation'],
                    'id' => 'kba-answer-2',
                    'pattern' => self::PATTERN_TWO,
                    'title' => $this->t(MoAuthConstants::VALIDATION_MESSAGE),
                ),
                '#required' => TRUE,
                '#element_validate' => array('::validate_answer'),
            );

            $form['mo_auth_question3'] = array(
                '#type' => 'textfield',
                '#title' => t('Question 3'),
                '#attributes' => array(
                    'placeholder' => t('Enter your custom question here'),
                    'style' => 'width:60%;',
                    'pattern'  => '^[\w\s?]{3,}$',
                    'title' => t('Only alphanumeric characters (with question mark) are allowed and include at least three characters.'),
                ),
                '#required' => TRUE,
                '#element_validate' => array('::validate_question'),
            );

            $form['mo_auth_answer3'] = array(
                '#type' => 'textfield',
                '#attributes' => array(
                    'placeholder' => t('Enter your answer'),
                    'style' => 'width:60%;',
                    'class' => ['custom-kba-validation'],
                    'id' => 'kba-answer-3',
                    'pattern' => self::PATTERN_TWO,
                    'title' => $this->t(MoAuthConstants::VALIDATION_MESSAGE),
                ),
                '#required' => TRUE,
                '#element_validate' => array('::validate_answer'),
            );
        } elseif ($authMethod == AuthenticationType::$HARDWARE_TOKEN['code']) {
            $form['mo_auth_token_textfield'] = [
                '#type' => 'textfield',
                '#title' => t('Hardware Token One Time Passcode'),
                '#attributes' => array('placeholder' => t('Enter the token'), 'autofocus' => 'true', 'autocomplete' => 'off'),
                '#required' => TRUE,
                '#maxlength' => 60,
            ];
        } else {
            $utilities->mo_add_loggers_for_failures('Invalid second factor authentication method selected.', 'error');
            $utilities->redirectUserToLoginPage('An error occurred while processing your request. Please contact your administrator.');
        }
    }

    public function validateForm(array &$form, FormStateInterface $form_state)
    {
      $utilities = new MoAuthUtilities();
      $utilities::validateUniqueKBA($form_state);
    }

    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $form_values = $form_state->getValues();
        $utilities = new MoAuthUtilities();
        $session = $utilities->getSession();
        $moMfaSession = $session->get("mo_auth", null);
        $authMethod = $moMfaSession['authentication_method'];
        $challengeResponse = $moMfaSession['mo_challenge_response'];

        $user = User::load($moMfaSession['uid']);
        $email = $user->get('mail')->value;

        $customer = new MiniorangeCustomerProfile();
        $user_api_handler = new UsersAPIHandler($customer->getCustomerID(), $customer->getAPIKey());
        $auth_api_handler = new AuthenticationAPIHandler($customer->getCustomerID(), $customer->getAPIKey());

        if (in_array($authMethod, $utilities->mo_TOTP_2fa_mentods())) {
            $secret = $challengeResponse->secret;
            $passcode = $form_values['mo_auth_passcode_textfield'];
            $miniorange_user = new MiniorangeUser($customer->getCustomerID(), $email, NULL, NULL, AuthenticationType::$GOOGLE_AUTHENTICATOR['code']);
            $response = $auth_api_handler->register($miniorange_user, AuthenticationType::$GOOGLE_AUTHENTICATOR['code'], $secret, $passcode, NULL);
            if (is_object($response) && $response->status == 'SUCCESS') {
                self::saveAuthenticationMethod($user);
            } elseif (is_object($response) && $response->status == 'FAILED' && $response->message == 'The OTP you have entered is incorrect.') {
                \Drupal::messenger()->addError(t('The passcode(OTP) you have entered is incorrect. Please enter correct passcode.'));
                return;
            }
        } elseif ($authMethod == AuthenticationType::$QR_CODE['code'] || $authMethod == AuthenticationType::$SOFT_TOKEN['code'] || $authMethod == AuthenticationType::$PUSH_NOTIFICATIONS['code']) {
            $miniorange_user = new MiniorangeUser($customer->getCustomerID(), $email, NULL, NULL, $authMethod);
            $response = $auth_api_handler->getRegistrationStatus($challengeResponse->txId);
            if (is_object($response) && $response->status == 'SUCCESS') {
                $updateResponse = $user_api_handler->update($miniorange_user); //update users 2FA method in miniOrange dashboard
                if ($updateResponse->status == 'SUCCESS') {
                    self::saveAuthenticationMethod($user);
                } else {
                    $utilities->mo_add_loggers_for_failures($updateResponse->message, 'error');
                    $utilities->redirectUserToLoginPage('An error occurred while processing your request. Please try again after sometime.');
                }
            }
        } elseif ($authMethod == AuthenticationType::$SMS['code'] || $authMethod == AuthenticationType::$EMAIL['code'] || $authMethod == AuthenticationType::$SMS_AND_EMAIL['code'] || $authMethod == AuthenticationType::$OTP_OVER_PHONE['code']) {
            $passcode = $form_values['mo_auth_passcode_textfield'];
            $miniorange_user = new MiniorangeUser($customer->getCustomerID(), $email, NULL, NULL, $authMethod);
            $response = $auth_api_handler->validate($miniorange_user, $challengeResponse->txId, $passcode, NULL);
            if (is_object($response) && $response->status == 'SUCCESS') {
                $updateResponse = $user_api_handler->update($miniorange_user); //update users 2FA method in miniOrange dashboard
                if ($updateResponse->status == 'SUCCESS') {
                    self::saveAuthenticationMethod($user);
                } else {
                    $utilities->mo_add_loggers_for_failures($updateResponse->message, 'error');
                    $utilities->redirectUserToLoginPage('An error occurred while processing your request. Please try again after sometime.');
                }
            } elseif (is_object($response) && $response->status == 'FAILED' && $response->message == 'Invalid OTP provided. Please try again.') {
                \Drupal::messenger()->addError(t('The passcode(OTP) you have entered is incorrect. Please enter correct passcode.'));
                return;
            }
        } elseif ($authMethod == AuthenticationType::$EMAIL_VERIFICATION['code']) {
            $miniorange_user = new MiniorangeUser($customer->getCustomerID(), $email, NULL, NULL, $authMethod);
            $response = $auth_api_handler->getAuthStatus($challengeResponse->txId);
            if (is_object($response) && $response->status == 'SUCCESS') {
                $updateResponse = $user_api_handler->update($miniorange_user); //update users 2FA method in miniOrange dashboard
                if ($updateResponse->status == 'SUCCESS') {
                    self::saveAuthenticationMethod($user);
                } else {
                    $utilities->mo_add_loggers_for_failures($updateResponse->message, 'error');
                    $utilities->redirectUserToLoginPage('An error occurred while processing your request. Please try again after sometime.');
                }
            } elseif (is_object($response) && $response->status == 'DENIED') {
                $message = 'Your transaction has been denied.';
                $utilities->redirectUserToLoginPage($message);
            }
        } elseif ($authMethod == AuthenticationType::$KBA['code']) {
            $kba = array(
                array(
                    "question" => $form_values['mo_auth_question1'],
                    "answer" => $form_values['mo_auth_answer1']
                ),
                array(
                    "question" => $form_values['mo_auth_question2'],
                    "answer" => $form_values['mo_auth_answer2']
                ),
                array(
                    "question" => $form_values['mo_auth_question3'],
                    "answer" => $form_values['mo_auth_answer3']
                )
            );
            $miniorange_user = new MiniorangeUser($customer->getCustomerID(), $email, NULL, NULL, $authMethod);
            $response = $auth_api_handler->register($miniorange_user, AuthenticationType::$KBA['code'], NULL, NULL, $kba);
            if (is_object($response) && $response->status == 'SUCCESS') {
                $updateResponse = $user_api_handler->update($miniorange_user); //update users 2FA method in miniOrange dashboard
                if ($updateResponse->status == 'SUCCESS') {
                    self::saveAuthenticationMethod($user);
                } else {
                    $utilities->mo_add_loggers_for_failures($updateResponse->message, 'error');
                    $utilities->redirectUserToLoginPage('An error occurred while processing your request. Please try again after sometime.');
                }
            }
        } elseif ($authMethod == AuthenticationType::$HARDWARE_TOKEN['code']) {
            $hardware_token = $form_values['mo_auth_token_textfield'];
            $miniorange_user = new MiniorangeUser($customer->getCustomerID(), $email, NULL, NULL, $authMethod);
            $response = $auth_api_handler->register($miniorange_user, AuthenticationType::$HARDWARE_TOKEN['code'], NULL, $hardware_token, NULL);
            if (is_object($response) && $response->status == 'SUCCESS') {
                $updateResponse = $user_api_handler->update($miniorange_user); //update users 2FA method in miniOrange dashboard
                if ($updateResponse->status == 'SUCCESS') {
                    self::saveAuthenticationMethod($user);
                } else {
                    $utilities->mo_add_loggers_for_failures($updateResponse->message, 'error');
                    $utilities->redirectUserToLoginPage('An error occurred while processing your request. Please try again after sometime.');
                }
            }
        } else {
            $utilities->mo_add_loggers_for_failures('Invalid second factor authentication method selected.', 'error');
            $utilities->redirectUserToLoginPage('An error occurred while processing your request. Please contact your administrator.');
        }
        $utilities->redirectUserToLoginPage('An error occurred while processing your request. Please contact your administrator.');
    }

    public function saveAuthenticationMethod($user)
    {
        $utilities = new MoAuthUtilities();
        $session = $utilities->getSession();
        $moMfaSession = $session->get("mo_auth", null);
        $authMethod = $moMfaSession['authentication_method'];
        $challengeResponse = $moMfaSession['mo_challenge_response'];

        $userID = $user->get('uid')->value;
        $userEmail = $user->get('mail')->value;
        $userName = $user->get('name')->value;

        $moAuthenticatorQRcode = NULL;
        $phoneNumber = NULL;
        if (in_array($authMethod, $utilities->mo_TOTP_2fa_mentods())) {
            $moAuthenticatorQRcode = $challengeResponse->qrCodeData;
        } elseif ($authMethod == AuthenticationType::$SMS['code'] || $authMethod == AuthenticationType::$SMS_AND_EMAIL['code'] || $authMethod == AuthenticationType::$OTP_OVER_PHONE['code']) {
            $phoneNumber = $moMfaSession['phone_number'];
        }

        $database = \Drupal::database();
        $fields = array(
            'uid' => $userID,
            'configured_auth_methods' => AuthenticationType::$EMAIL['code'] . ', ' . $authMethod,
            'miniorange_registered_email' => $userEmail,
            'activated_auth_methods' => $authMethod,
            'enabled' => TRUE,
            'qr_code_string' => $moAuthenticatorQRcode,
            'phone_number' => $phoneNumber,
        );
        $custom_attribute = $utilities->get_users_custom_attribute($userID);

        if (count($custom_attribute) > 0) {
            $database->update('UserAuthenticationType')->fields($fields)->condition('uid', $userID, '=')->execute();
        } else {
            try {
                $database->insert('UserAuthenticationType')->fields($fields)->execute();
            } catch (\Exception $e) {
                $utilities->mo_add_loggers_for_failures($e->getMessage(), 'error');
                $utilities->redirectUserToLoginPage('An error occurred while processing your request. Please contact your administrator.');
            }
        }

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

        /**
         * Generate session and redirect
         */
        user_login_finalize($user);

        $utilities->mo_add_loggers_for_failures('Second factor authentication has been configured successfully for ' . $userName . ' - ' . $userEmail, 'info');
        /**
         * Redirect user after login
         */
        $variables_and_values = array(
            'mo_auth_redirect_user_after_login',
        );

        $mo_db_values = $utilities->miniOrange_set_get_configurations($variables_and_values, 'GET');
        if (isset($_COOKIE['Drupal_visitor_destination'])) {
            global $base_url;
            $url = $base_url . '/' . $_COOKIE['Drupal_visitor_destination'];
            user_cookie_delete('destination');
        } else {
            $url = isset($mo_db_values['mo_auth_redirect_user_after_login']) && !empty($mo_db_values['mo_auth_redirect_user_after_login']) ? $mo_db_values['mo_auth_redirect_user_after_login'] : Url::fromRoute('miniorange_2fa.user.mo_mfa_form', ['user' => $userID])->toString();
        }        $response = new RedirectResponse($url);
        $response->send();
        exit;
    }

    public function moCancelInlineRegistration($message = NULL)
    {
        $session = MoAuthUtilities::getSession();
        $moMfaSession = $session->get("mo_auth", null);
        $account = User::load($moMfaSession['uid']);
        //Remove user from MO dashboard created in previous step (Select method submit)
        MoAuthUtilities::delete_user_from_UserAuthentication_table($account);
        $session->remove('mo_auth');
        $url = Url::fromRoute('user.login')->toString();
        $response = new RedirectResponse($url);
        $response->send();
        exit;
    }

    public function validate_answer(&$element, FormStateInterface &$form_state) {
      $value = trim($element['#value']);
      $kba_answer_length = MoAuthConstants::KBA_ANSWER_LENGTH;

      if (strlen($value) < $kba_answer_length) {
        if(!(preg_match(self::PATTERN_ONE, $value))) {
          $form_state->setError($element, t('Only alphanumeric characters are allowed.'));
        } else {
          $form_state->setError($element, t('Answers must contain at least @length characters.', ['@length' => $kba_answer_length]));
        }
      } elseif (!(preg_match(self::PATTERN_ONE, $value))) {
        $form_state->setError($element, t('Only alphanumeric characters are allowed.'));
      }
    }

    public function validate_question(&$element, FormStateInterface &$form_state) {
      $value = trim($element['#value']);
      if (strlen($value) < 3 || !(preg_match('/^[\w\s?]{3,}$/', $value))) {
        $form_state->setError($element, t('Only alphanumeric characters (with question mark) are allowed and include at least three characters.'));
      }
    }
}
