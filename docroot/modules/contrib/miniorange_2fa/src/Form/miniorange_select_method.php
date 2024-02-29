<?php

namespace Drupal\miniorange_2fa\Form;

use Drupal\Core\Ajax\MessageCommand;
use Drupal\Core\Url;
use Drupal\user\Entity\User;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\miniorange_2fa\MiniorangeUser;
use Drupal\miniorange_2fa\MoAuthUtilities;
use Drupal\miniorange_2fa\UsersAPIHandler;
use Drupal\miniorange_2fa\AuthenticationType;
use Drupal\miniorange_2fa\AuthenticationAPIHandler;
use Drupal\miniorange_2fa\MiniorangeCustomerProfile;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * @file
 *  This is used to authenticate user during
 *     login.
 */
class miniorange_select_method extends FormBase
{
    public function getFormId()
    {
        return 'mo_auth_select_method';
    }

    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $utilities = new MoAuthUtilities();
        $session = $utilities->getSession();
        $moMfaSession = $session->get("mo_auth", null);

        if (is_null($moMfaSession) || !isset($moMfaSession['uid']) || !isset($moMfaSession['status']) || $moMfaSession['status'] !== '1ST_FACTOR_AUTHENTICATED' || $moMfaSession['challenge'] !== 1) {
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

        $form['markup_library'] = array(
            '#attached' => [
                'library' => [
                    "miniorange_2fa/miniorange_2fa.admin",
                    "miniorange_2fa/miniorange_2fa.license",
                    "miniorange_2fa/miniorange_2fa.country_flag_dropdown",
                ]
            ],
        );

        $options = $utilities->get_2fa_methods_for_inline_registration(TRUE);

        if( \Drupal::config('miniorange_2fa.settings')->get('mo_auth_enable_role_based_2fa')) {
          $options = $utilities::get_2fa_methods_for_role_based_2fa($options, $moMfaSession['uid']);
        }

        if(count($options)  == 1)  {
          $form['#title'] = t('Setup second factor authentication');
          $key = array_keys($options);
          $form['mo_auth_heading'] = [
            '#markup' => '<h3>Configure ' . $options[$key[0]] . '</h3>',
          ];

          $form['mo_auth_method'] = [
            '#type' => 'textfield',
            '#default_value' => array_keys($options)[0],
            '#states' => [
              'visible' => [
                ':input[name="mo_auth_phone_number"]' => ['value' => 'other'],
              ],
            ],
          ];
        } else {
          $form['mo_auth_method'] = [
            '#type' => 'select',
            '#title' => t('Choose your 2FA method'),
            '#default_value' => array_keys($options)[0],
            '#options' => $options,
            '#required' => TRUE,
          ];
        }

      $phoneNumber = $utilities->getUserPhoneNumber($moMfaSession['uid']);

        $form['mo_auth_phone_number'] = array(
            '#type' => 'tel',
            '#title' => t('Phone Number'),
            '#pattern'       => '^\+?[0-9\s]+$',
            '#attributes'    => [
              'id'           => 'query_phone',
              'autocomplete' =>'on',
            ],
            '#states' => array(
                'visible' => array(
                    ':input[name = "mo_auth_method"]' => [
                        ['value' => 'SMS'],
                        ['value' => 'SMS AND EMAIL'],
                        ['value' => 'PHONE VERIFICATION'],
                    ]
                ),
            ),
        );

      $form['phone_full']= array(
        '#type' => 'hidden',
        '#default_value' => $phoneNumber,
      );

        if (!is_null($phoneNumber)) {
            $form['mo_auth_phone_number']['#default_value'] = $phoneNumber;
        }

        $form['actions']['#type'] = 'actions';
        $form['actions']['login'] = [
            '#type' => 'submit',
            '#button_type' => 'primary',
            '#value' => t('Next'),
        ];

        $form['actions']['cancel'] = array(
            '#type' => 'submit',
            '#value' => t('Cancel'),
            '#button_type' => 'danger',
            '#submit' => array('::moCancelInlineRegistration'),
            '#limit_validation_errors' => array(), //skip the required field validation
        );

        if ($utilities->isSkipNotAllowed()) {
            $form['actions']['skip_mfa'] = array(
                '#type' => 'submit',
                '#value' => t('Skip'),
                '#submit' => array('::handle_skip_mfa'),
                '#limit_validation_errors' => array(), //skip the required field validation
            );
        }

        return $form;
    }

    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $utilities = new MoAuthUtilities();
        $form_values = $form_state->getValues();
        $authMethod = $form_values['mo_auth_method'];
        $utilities->process_selected_method($authMethod,$form_values);
    }

    public function moCancelInlineRegistration($message = NULL)
    {
        $session = MoAuthUtilities::getSession();
        $session->remove('mo_auth');
        $url = Url::fromRoute('user.login')->toString();
        $response = new RedirectResponse($url);
        $response->send();
        exit;
    }

    function handle_skip_mfa()
    {
        $utilities = new MoAuthUtilities();
        $session = $utilities->getSession();
        $moMfaSession = $session->get('mo_auth');
        $redirectUrl = Url::fromRoute('user.login')->toString();

        if (isset($moMfaSession['uid'])) {
            $userID = $moMfaSession['uid'];
            $utilities->updateMfaSettingsForUser($userID, 0);

            $account = User::load($userID);
            user_login_finalize($account);

            $this->messenger()->addStatus($this->t("You have successfully disabled 2FA for your account. You can enable it anytime from here."));
            $redirectUrl = Url::fromRoute('miniorange_2fa.user.mo_mfa_form', ['user' => $userID])->toString();
        }
        $response = new RedirectResponse($redirectUrl);
        $response->send();
        exit;
    }
}
