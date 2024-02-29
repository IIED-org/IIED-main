<?php

namespace Drupal\miniorange_2fa\Form;

use Drupal\Core\Url;
use Drupal\user\Entity\User;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\miniorange_2fa\MoAuthUtilities;
use Drupal\miniorange_2fa\AuthenticationType;
use Drupal\Component\Render\FormattableMarkup;

class UserMfaSetup extends FormBase
{
    public function getFormId()
    {
        return "mo_mfa_form";
    }

    public function buildForm(array $form, FormStateInterface $form_state)
    {

        $userId = \Drupal::routeMatch()->getRawParameter('user') ?? 1;

        $form['#title'] = $this->t('2FA Configurations -  %username', ['%username' => User::load($userId)->getAccountName()]);
        $form['markup_library'] = array(
          '#attached' => array(
            'library' => array(
              "miniorange_2fa/miniorange_2fa.country_flag_dropdown",
              "core/drupal.dialog.ajax",
            )
          ),
        );

        $variables_and_values = array(
            'mo_auth_enable_two_factor',
            'mo_auth_2fa_kba_questions',
            'mo_auth_2fa_allow_reconfigure_2fa',
            'mo_auth_firstuser_id'
        );
        $mo_db_values = MoAuthUtilities::miniOrange_set_get_configurations($variables_and_values, 'GET');

        $custom_attribute = MoAuthUtilities::get_users_custom_attribute($userId);
        $configuredMethods = MoAuthUtilities::mo_auth_get_configured_methods($custom_attribute);

        $options = [];

        $form['mo_2fa_method'] = array(
          '#type' => 'fieldset',
          '#attributes' => array('style' => 'padding:2% 2%;'),
        );

        if (isset($custom_attribute[0]->activated_auth_methods)) {
            $authMethod = $custom_attribute[0]->activated_auth_methods;

            $header = array(t('ATTRIBUTE NAME'), t('VALUE'), t('ACTION'));

            if ($mo_db_values['mo_auth_2fa_allow_reconfigure_2fa'] == 'Allowed') {
                $user = User::load($userId);
                $roles = $user->getRoles();
                if (isset($mo_db_values['mo_auth_firstuser_id']) ? $mo_db_values['mo_auth_firstuser_id'] != $userId : !in_array('administrator', $roles) && !in_array('admin', $roles)) {
                    $moTitle = 'Configure';
                    if (!empty($authMethod)) {
                        $moTitle = 'Reconfigure';
                    }
                    $form['mo_2fa_method']['mo_configure_reconfigure_button'] = array(
                        '#type' => 'link',
                        '#title' => $this->t($moTitle),
                        '#url' => Url::fromRoute('miniorange_2fa.re_configure'),
                        '#attributes' => [
                            'class' => [
                                'use-ajax',
                                'button button--small',
                            ],
                        ],
                    );
                }
            }
            $options[0] = array('Configured 2FA method', $authMethod, isset($form['mo_2fa_method']['mo_configure_reconfigure_button']) ? \Drupal::service('renderer')->render($form['mo_2fa_method']['mo_configure_reconfigure_button']) : '');


            if ($authMethod == AuthenticationType::$SMS['code'] || $authMethod == AuthenticationType::$SMS_AND_EMAIL['code'] || $authMethod == AuthenticationType::$OTP_OVER_PHONE['code']) {
                $phone_attribute = 'Configured phone number';
                $phone_value = $custom_attribute[0]->phone_number;
                $form['mo_2fa_method']['mo_configure_phone_update_button'] = array(
                    '#type' => 'link',
                    '#title' => $this->t('Update'),
                    '#url' => Url::fromRoute('miniorange_2fa.update_phone'),
                    '#attributes' => [
                        'class' => [
                            'use-ajax',
                            'button button--small',
                        ],
                    ],
                );
                $options[1] = array($phone_attribute, $phone_value, \Drupal::service('renderer')->render($form['mo_2fa_method']['mo_configure_phone_update_button']));
            }

            /**
             * Configure Backup 2FA method section
             */

            if ($mo_db_values['mo_auth_2fa_kba_questions'] == 'Allowed' && !empty($authMethod)) {
                $moTitle = 'Configure';
                if (in_array(AuthenticationType::$KBA['code'], $configuredMethods)) {
                    $moTitle = 'Re-Configure';
                }
                if ($authMethod != AuthenticationType::$KBA['code']) {
                    $kba_attribute = t('Configure backup 2FA method security questions (KBA)');
                    $kba_value = '';
                    $form['mo_2fa_method']['mo_configure_backup_2fa_button'] = array(
                        '#type' => 'link',
                        '#title' => $this->t($moTitle),
                        '#url' => Url::fromRoute('miniorange_2fa.configure_kba'),
                        '#attributes' => [
                          'class' => ['use-ajax', 'button', 'button--small'],
                          'data-dialog-type' => 'modal',
                          'data-dialog-options' => json_encode(['width' => '50%']),
                        ],
                    );
                    $options[2] = array($kba_attribute, $kba_value, \Drupal::service('renderer')->render($form['mo_2fa_method']['mo_configure_backup_2fa_button']));
                }
            }

            if (in_array($authMethod, MoAuthUtilities::mo_TOTP_2fa_mentods()) && isset($custom_attribute[0]->qr_code_string) && !empty($custom_attribute[0]->qr_code_string)) {
                $totp_attribute = t('Scan QR to Configure 2FA on multiple devides');
                $totp_value = '';
                $qrCode = $custom_attribute[0]->qr_code_string;
                $image = new FormattableMarkup('<img src="data:image/jpg;base64, ' . $qrCode . '"/>', [':src' => $qrCode]);

                $form['mo_2fa_method']['mo_scan_qr_code_google_authenticator'] = array(
                    '#markup' => $image = isset($image) ? $image : '',
                );
                $options[3] = array($totp_attribute, $totp_value, \Drupal::service('renderer')->render($form['mo_2fa_method']['mo_scan_qr_code_google_authenticator']));
            }

            $form['mo_2fa_method']['fieldset']['configurations'] = array(
                '#type' => 'table',
                '#header' => $header,
                '#rows' => $options,
                '#attributes' => array('style' => 'width:100%'),
            );

            if (MoAuthUtilities::isUserCanSee2FASettings()) {

                $form['mo_2fa_method']['fieldset']['inline'] = array(
                    '#prefix' => '<div class="container-inline form-item">',
                    '#suffix' => '</div>',
                );
                $form['mo_2fa_method']['fieldset']['inline']['mo_mfa_enable'] = array(
                    '#type' => 'checkbox',
                    '#title' => t('Enable MFA for your account'),
                    '#default_value' => isset($custom_attribute[0]->enabled) ? $custom_attribute[0]->enabled : FALSE,
                    '#prefix' => '<hr>'
                );

                $form['mo_2fa_method']['fieldset']['inline']['mo_mfa_form_save'] = array(
                    '#type' => 'submit',
                    '#value' => t('Save'),
                    '#button_type' => 'primary',
                );
            }
        }
        return $form;
    }

    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $formValues = $form_state->getValues();
        if ($formValues["mo_mfa_enable"] === 0) {
            if (!MoAuthUtilities::isSkipNotAllowed()) {
                \Drupal::messenger()->addError(t("You are not allowed to disable 2FA. Please contact your site administrator"));
                return;
            }
        }
        MoAuthUtilities::updateMfaSettingsForUser(\Drupal::currentUser()->id(), $formValues["mo_mfa_enable"] == 1 ? 1 : 0);
        \Drupal::messenger()->addStatus(t('2FA settings are updated for your account'));
    }
}
