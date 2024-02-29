<?php

namespace Drupal\miniorange_2fa\Form;

use Drupal\Core\Url;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Form\FormStateInterface;
use Drupal\miniorange_2fa\MoAuthUtilities;

class MoAuthPhoneUpdate extends FormBase
{
    public function getFormId() {
        return 'miniorange_2fa_phone_udate';
    }

    public function buildForm(array $form, FormStateInterface $form_state, $options = NULL) {

        $utilities = new MoAuthUtilities();
        $custom_attribute = $utilities::get_users_custom_attribute(\Drupal::currentUser()->id());

        $form['markup_library'] = array(
            '#attached' => array(
                'library' => array(
                    "miniorange_2fa/miniorange_2fa.admin",
                    "miniorange_2fa/miniorange_2fa.license",
                    "miniorange_2fa/miniorange_2fa.country_flag_dropdown",
                )
            ),
        );

        $form['miniorange_2fa_update_phone'] = array(
            '#markup' => t('You can update your phone number here. The updated phone number will be used for 2FA upon next login.')
        );

        $form['miniorange_2fa_update_phone']['phone_number'] = array(
            '#type' => 'tel',
            '#title' => t('Enter Updated Phone number'),
            '#id' => 'query_phone',
            '#pattern'       => '^\+?[0-9\s]+$',
            '#attributes' => array(
                'class' => array('query_phone',),
                'autocomplete' =>'on',
            ),
            '#default_value' => $custom_attribute[0]->phone_number,
        );

        $form['miniorange_2fa_update_phone']['phone_full'] = array(
            '#type' => 'hidden',
            '#default_value' => $custom_attribute[0]->phone_number,
        );

        $form['actions'] = array('#type' => 'actions');
        $form['actions']['send'] = [
            '#type' => 'submit',
            '#value' => $this->t('Update'),
            '#attributes' => [
                'class' => [
                    'use-ajax',
                    'button--primary'
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
            $response->addCommand(new ReplaceCommand('#modal_support_form', $form));
        }else {
            $form_state->setRebuild();
            $user_id = \Drupal::currentUser()->id();
            $utilities = new MoAuthUtilities();

            $form_values = $form_state->getValues();
            $updated_phone = trim($form_values['phone_full']);

            $available = $utilities::check_for_userID($user_id);
            $database = \Drupal::database();
            if ($available == TRUE) {
                $database->update('UserAuthenticationType')->fields(['phone_number' => $updated_phone])->condition('uid', $user_id, '=')->execute();
            } else {
                \Drupal::messenger()->addError(t("Error while updating the phone number"));
                return;
            }

            $message = t('Phone Number updated successfully.');
            \Drupal::messenger()->addStatus($message);
            $url = Url::fromRoute('miniorange_2fa.user.mo_mfa_form', ['user' => $user_id] )->toString();
            $response->addCommand( new RedirectCommand( $url ) );
            return $response;
        }
    }

    public function validateForm(array &$form, FormStateInterface $form_state) { }

    public function submitForm(array &$form, FormStateInterface $form_state) { }
}