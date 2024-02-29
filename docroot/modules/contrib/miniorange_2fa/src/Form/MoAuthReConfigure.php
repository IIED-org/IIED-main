<?php

namespace Drupal\miniorange_2fa\Form;

use Drupal\Core\Url;
use Drupal\user\Entity\User;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Form\FormStateInterface;
use Drupal\miniorange_2fa\MoAuthUtilities;

class MoAuthReConfigure extends FormBase
{
    public function getFormId()
    {
        return 'miniorange_2fa_reconfigure';
    }

    public function buildForm(array $form, FormStateInterface $form_state, $options = NULL)
    {

        $utilities = new MoAuthUtilities();
        $variables_and_values = array(
            'mo_auth_2fa_allow_reconfigure_2fa'
        );
        $mo_db_values = $utilities->miniOrange_set_get_configurations($variables_and_values, 'GET');

        if ($mo_db_values['mo_auth_2fa_allow_reconfigure_2fa'] !== 'Allowed') {
            $form['mo_auth_kba_error_label'] = array(
                '#type' => 'label',
                '#title' => t('<h1>Access denied</h1>You are not authorized to access this page'),
            );
            $form['#attached']['library'][] = 'core/drupal.dialog.ajax';
            return $form;
        }

        $form['miniorange_2fa_content'] = array(
            '#markup' => 'You will be logged out from the current session. You can re-configure the 2FA upon next login.'
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

    public function submitModalFormAjax(array $form, FormStateInterface $form_state)
    {
        $response = new AjaxResponse();
        // If there are any form errors, AJAX replace the form.
        if ($form_state->hasAnyErrors()) {
            $response->addCommand(new ReplaceCommand('#modal_example_form', $form));
        } else {
            $utilities = new MoAuthUtilities();
            $user = User::load(\Drupal::currentUser()->id());
            $utilities->delete_user_from_UserAuthentication_table($user);
            /**
             * Kill the user session and redirect to login page.
             */
            user_logout();
            \Drupal::messenger()->addStatus(t('Your second factor authentication has been reset successfully!'));
            $response->addCommand(new RedirectCommand(Url::fromRoute('user.login')->toString()));
        }
        return $response;
    }

    public function validateForm(array &$form, FormStateInterface $form_state)
    {
    }

    public function submitForm(array &$form, FormStateInterface $form_state)
    {
    }
}