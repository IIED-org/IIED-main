<?php

namespace Drupal\miniorange_2fa\Form;

use Drupal\Core\Url;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Form\FormStateInterface;
use Drupal\miniorange_2fa\MoAuthUtilities;

class MoAuthRemoveAccount extends FormBase
{
    public function getFormId() {
        return 'miniorange_2fa_remove_account';
    }

    public function buildForm(array $form, FormStateInterface $form_state, $options = NULL) {
        $form['#prefix'] = '<div id="modal_example_form">';
        $form['#suffix'] = '</div>';
        $form['status_messages'] = [
            '#type' => 'status_messages',
            '#weight' => -10,
        ];

        $form['miniorange_2fa_content'] = array(
            '#markup' => 'Are you sure you want to remove the account? The configurations saved will not be lost.'
        );

        $form['actions'] = array('#type' => 'actions');
        $form['actions']['send'] = [
            '#type' => 'submit',
            '#value' => $this->t('Confirm'),
            '#attributes' => [
                'class' => [
                    'use-ajax',
                ],
            ],
            '#ajax' => [
                'callback' => [$this, 'submitModalFormAjax'],
                'event' => 'click',
            ],
        ];

        $form['#attached']['library'][] = 'core/drupal.dialog.ajax';
        return $form;
    }

    public function submitModalFormAjax(array $form, FormStateInterface $form_state) {
        $response = new AjaxResponse();
        // If there are any form errors, AJAX replace the form.
        if ( $form_state->hasAnyErrors() ) {
            $response->addCommand(new ReplaceCommand('#modal_example_form', $form));
        } else {
            $utilities = new MoAuthUtilities();
            $variables_and_values = array(
              'mo_auth_customer_admin_email',
              'mo_auth_customer_admin_phone',
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
              'mo_auth_status',
              'mo_auth_firstuser_id'
            );
            $utilities->miniOrange_set_get_configurations($variables_and_values, 'CLEAR');

            $variables_and_values_2 = array(
              'mo_auth_enable_two_factor' => FALSE,
              'mo_auth_enforce_inline_registration' => FALSE,
            );
            $utilities->miniOrange_set_get_configurations($variables_and_values_2, 'SET' );

            \Drupal::messenger()->addStatus(t('Your Account Has Been Removed Successfully!'));
            $_POST['value_check'] = 'False';
            $response->addCommand(new RedirectCommand(Url::fromRoute('miniorange_2fa.customer_setup', ['tab'=> 'login'])->toString()));
        }
        return $response;
    }

    public function validateForm(array &$form, FormStateInterface $form_state) { }

    public function submitForm(array &$form, FormStateInterface $form_state) { }

    protected function getEditableConfigNames() {
        return ['config.miniorange_2fa_remove_account'];
    }
}