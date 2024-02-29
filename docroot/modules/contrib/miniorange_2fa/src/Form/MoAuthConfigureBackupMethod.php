<?php
/**
 * @file
 * Contains support form for miniOrange 2FA Login
 *     Module.
 */

namespace Drupal\miniorange_2fa\Form;

use Drupal\Core\Url;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\miniorange_2fa\MiniorangeUser;
use Drupal\miniorange_2fa\MoAuthConstants;
use Drupal\miniorange_2fa\MoAuthUtilities;
use Drupal\miniorange_2fa\AuthenticationType;
use Drupal\miniorange_2fa\AuthenticationAPIHandler;
use Drupal\miniorange_2fa\MiniorangeCustomerProfile;
use Symfony\Component\HttpFoundation\RedirectResponse;


/**
 *  Showing Support form info.
 */
class MoAuthConfigureBackupMethod extends FormBase
{
    CONST PATTERN_ONE = MoAuthConstants::ALPHANUMERIC_PATTERN;
    CONST PATTERN_TWO = MoAuthConstants::ALPHANUMERIC_LENGTH_PATTERN;

    public function getFormId()
    {
        return 'miniorange_configure_backup_method';
    }

    public function buildForm(array $form, FormStateInterface $form_state)
    {

        $variables_and_values = array(
            'mo_auth_2fa_kba_questions'
        );
        $mo_db_values = MoAuthUtilities::miniOrange_set_get_configurations($variables_and_values, 'GET');

        if ($mo_db_values['mo_auth_2fa_kba_questions'] !== 'Allowed') {
            $form['mo_auth_kba_error_label'] = array(
                '#type' => 'label',
                '#title' => t('<h1>Access denied</h1>You are not authorized to access this page'),
            );
            $form['#attached']['library'][] = 'core/drupal.dialog.ajax';
            return $form;
        }

        $utilities = new MoAuthUtilities();
        $questionSetOne = $utilities->mo_get_kba_questions('ONE');
        $questionSetTwo = $utilities->mo_get_kba_questions('TWO');

        $form['#attached']['library'][] = 'miniorange_2fa/miniorange_2fa.custom_kba_validation';

        $form['mo_auth_kba_label'] = array(
            '#type' => 'label',
            '#title' => t('Please choose your security questions (KBA) and answer those:'),
        );

        $form['mo_auth_question1'] = array(
            '#type' => 'select',
            '#title' => t('Question 1'),
            '#options' => $questionSetOne,
            '#attributes' => array('style' => 'width:90%;'),
            '#required' => TRUE,
        );
        $form['mo_auth_answer1'] = array(
            '#type' => 'textfield',
            '#attributes' => array(
                'placeholder' => t('Enter your answer'),
                'style' => 'width:90%;',
                'id' => 'kba-answer-1',
                'class' => ['custom-kba-validation'],
                'pattern'  => self::PATTERN_TWO,
                'title' => $this->t(MoAuthConstants::VALIDATION_MESSAGE),
            ),
            '#element_validate' => array('::validate_answer'),
            '#required' => TRUE,
        );
        $form['mo_auth_question2'] = array(
            '#type' => 'select',
            '#title' => t('Question 2'),
            '#options' => $questionSetTwo,
            '#attributes' => array('style' => 'width:90%;'),
            '#required' => TRUE,
        );

        $form['mo_auth_answer2'] = array(
            '#type' => 'textfield',
            '#attributes' => array(
                'placeholder' => t('Enter your answer'),
                'style' => 'width:90%;',
                'id' => 'kba-answer-2',
                'class' => ['custom-kba-validation'],
                'pattern'  => self::PATTERN_TWO,
                'title' => $this->t(MoAuthConstants::VALIDATION_MESSAGE),
            ),
            '#element_validate' => array('::validate_answer'),
            '#required' => TRUE,
        );

        $form['mo_auth_question3'] = array(
            '#type' => 'textfield',
            '#title' => t('Question 3'),
            '#attributes' => array(
                'placeholder' => t('Enter your custom question here'),
                'style' => 'width:90%;',
                'pattern'  => '^[\w\s?]{3,}$',
                'title' => t('Only alphanumeric characters (with question mark) are allowed and include at least three characters.'),
            ),
            '#element_validate' => array('::validate_question'),
            '#required' => TRUE,
        );

        $form['mo_auth_answer3'] = array(
            '#type' => 'textfield',
            '#attributes' => array(
                'placeholder' => t('Enter your answer'),
                'style' => 'width:90%;',
                'id' => 'kba-answer-3',
                'class' => ['custom-kba-validation'],
                'pattern'  => self::PATTERN_TWO,
                'title' => $this->t(MoAuthConstants::VALIDATION_MESSAGE),
            ),
            '#element_validate' => array('::validate_answer'),
            '#required' => TRUE,
        );

        $form['actions'] = array('#type' => 'actions');

        $form['actions']['send'] = [
            '#type' => 'submit',
            '#value' => $this->t('Submit'),
        ];

        $form['#attached']['library'][] = 'core/drupal.dialog.ajax';
        return $form;
    }

    function updateConfiguredMethods($userID)
    {
        $utilities = new MoAuthUtilities();
        $customAttribute = $utilities->get_users_custom_attribute($userID);
        $configured_methods = $utilities->mo_auth_get_configured_methods($customAttribute);
        array_push($configured_methods, AuthenticationType::$KBA['code']);
        $configMethods = implode(', ', $configured_methods);
        if (count($customAttribute) > 0) {
            $database = \Drupal::database();
            $database->update('UserAuthenticationType')->fields(['configured_auth_methods' => $configMethods])->condition('uid', $userID, '=')->execute();
            return TRUE;
        }
        return FALSE;
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

      $userID = \Drupal::currentUser()->id();
      $custom_attribute = MoAuthUtilities::get_users_custom_attribute($userID);
      $email = $custom_attribute[0]->miniorange_registered_email;

      $kba = array(
        array(
          "question" => trim($form_values['mo_auth_question1']),
          "answer" => trim($form_values['mo_auth_answer1'])
        ),
        array(
          "question" => trim($form_values['mo_auth_question2']),
          "answer" => trim($form_values['mo_auth_answer2'])
        ),
        array(
          "question" => trim($form_values['mo_auth_question3']),
          "answer" => trim($form_values['mo_auth_answer3'])
        )
      );

      $customer = new MiniorangeCustomerProfile();
      $auth_api_handler = new AuthenticationAPIHandler($customer->getCustomerID(), $customer->getAPIKey());

      $miniorange_user = new MiniorangeUser($customer->getCustomerID(), $email, NULL, NULL, AuthenticationType::$KBA['code']);
      $apiResponse = $auth_api_handler->register($miniorange_user, AuthenticationType::$KBA['code'], NULL, NULL, $kba);
      if (is_object($apiResponse) && $apiResponse->status == 'SUCCESS') {
        $moStatus = self::updateConfiguredMethods($userID);
        if ($moStatus) {
          $utilities->mo_add_loggers_for_failures('Backup 2FA method has been configured successfully for - ' . $email, 'info');
          \Drupal::messenger()->addStatus(t('Backup 2FA method has been configured successfully.'));
        } else {
          $utilities->mo_add_loggers_for_failures('An error occurred while configuring Backup 2FA method for - ' . $email, 'error');
          \Drupal::messenger()->addError(t('An error occurred while processing your request. Please try again after sometime.'));
        }
      } else {
        $utilities->mo_add_loggers_for_failures($apiResponse->message, 'error');
        \Drupal::messenger()->addError(t('An error occurred while processing your request. Please try again after sometime.'));
      }

      $url = Url::fromRoute('miniorange_2fa.user.mo_mfa_form', ['user' => $userID])->toString();
      $response = new RedirectResponse($url);
      $response->send();
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
