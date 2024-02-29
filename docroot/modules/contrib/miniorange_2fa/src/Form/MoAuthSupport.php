<?php
/**
 * @file
 * Contains support form for miniOrange 2FA Login
 *     Module.
 */

namespace Drupal\miniorange_2fa\Form;

use Drupal\Core\Url;
use Drupal\user\Entity\User;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Form\FormStateInterface;
use Drupal\miniorange_2fa\MoAuthUtilities;
use Drupal\miniorange_2fa\MiniorangeCustomerProfile;

/**
 *  Showing Support form info.
 */
class MoAuthSupport extends FormBase
{
    public function getFormId()
    {
        return 'miniorange_2fa_support';
    }

    public function buildForm(array $form, FormStateInterface $form_state)
    {

        $user_email = '';

        if (MoAuthUtilities::isCustomerRegistered()) {
            $customer = new MiniorangeCustomerProfile();
            $user_email = $customer->getRegisteredEmail();
        }

        $form['#prefix'] = '<div id="modal_support_form">';
        $form['#suffix'] = '</div>';
        $form['status_messages'] = [
            '#type' => 'status_messages',
            '#weight' => -10,
        ];

        $form['markup_1'] = array(
            '#markup' => t('<p>Need any help? We can help you with configuring miniOrange 2FA module on your site. Just reach out to us and we will get back to you soon.</p>'),
        );

        $form['miniorange_2fa_support_email_address'] = array(
            '#type' => 'email',
            '#title' => t('Email'),
            '#default_value' => $user_email,
            '#required' => TRUE,
            '#attributes' => array('placeholder' => t('Enter your email'), 'style' => 'width:99%;margin-bottom:1%;'),
        );

        $form['miniorange_2fa_support_query'] = array(
            '#type' => 'textarea',
            '#title' => t('Query'),
            '#required' => TRUE,
            '#attributes' => array('placeholder' => t('Describe your query here!'), 'style' => 'width:99%'),
        );

        $form['actions'] = array('#type' => 'actions');
        $form['actions']['send'] = [
            '#type' => 'submit',
            '#value' => $this->t('Submit'),
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

    public function submitModalFormAjax(array $form, FormStateInterface $form_state)
    {
        $response = new AjaxResponse();
        // If there are any form errors, AJAX replace the form.
        if ($form_state->hasAnyErrors()) {
            $response->addCommand(new ReplaceCommand('#modal_support_form', $form));
        } else {

            $email = $form['miniorange_2fa_support_email_address']['#value'];
            $phone = '';
            $query = $form['miniorange_2fa_support_query']['#value'];

            $support_response = MoAuthUtilities::send_support_query($email, $phone, $query);

            if ($support_response) {
                \Drupal::messenger()->addStatus(t('Thanks for getting in touch! We will get back to you shortly.'));
            } else {
                \Drupal::messenger()->addError(t('Error submitting the support query. Please send us your query at <a href="mailto:drupalsupport@xecurify.com">drupalsupport@xecurify.com</a>.'));
            }

            if (isset($_SERVER['HTTP_REFERER']) && !empty($_SERVER['HTTP_REFERER'])) {
                global $base_url;
                $current_path = explode($base_url, $_SERVER['HTTP_REFERER']);
                $url_object = \Drupal::service('path.validator')->getUrlIfValid($current_path[1]);
                $route_name = $url_object->getRouteName();
                $response->addCommand(new RedirectCommand(Url::fromRoute($route_name)->toString()));
            } else {
                $response->addCommand(new RedirectCommand(Url::fromRoute('miniorange_2fa.customer_setup')->toString()));
            }
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