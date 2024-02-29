<?php

/**
 * Default controller for the miniorange_2fa
 * module.
 */

namespace Drupal\miniorange_2fa\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Url;
use Drupal\Core\Form\formBuilder;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Session\AccountInterface;
use Drupal\miniorange_2fa\AuthenticationType;
use Drupal\miniorange_2fa\MiniorangeUser;
use Drupal\miniorange_2fa\MoAuthUtilities;
use Drupal\miniorange_2fa\UsersAPIHandler;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\Response;
use Drupal\miniorange_2fa\AuthenticationAPIHandler;
use Drupal\miniorange_2fa\MiniorangeCustomerProfile;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;

class miniorange_2faController extends ControllerBase
{
    protected $formBuilder;

    public function __construct(FormBuilder $formBuilder)
    {
        $this->formBuilder = $formBuilder;
    }

    public static function create(ContainerInterface $container)
    {
        return new static(
            $container->get("form_builder")
        );
    }

    /**
     * @return Response
     */
    public function openModalForm()
    {
        $response = new AjaxResponse();
        $modal_form = $this->formBuilder->getForm('\Drupal\miniorange_2fa\Form\MoAuthRemoveAccount');
        $response->addCommand(new OpenModalDialogCommand('Remove Account', $modal_form, ['width' => '800']));
        return $response;
    }

    public function openDemoRequestForm()
    {
        $response = new AjaxResponse();
        $modal_form = $this->formBuilder->getForm('\Drupal\miniorange_2fa\Form\MoAuthRequestDemo');
        $response->addCommand(new OpenModalDialogCommand('Request 7 days trial license', $modal_form, ['width' => '60%']));
        return $response;
    }

    public function openContactUsForm()
    {
        $response = new AjaxResponse();
        $modal_form = $this->formBuilder->getForm('\Drupal\miniorange_2fa\Form\MoAuthSupport');
        $response->addCommand(new OpenModalDialogCommand('Contact Us', $modal_form, ['width' => '40%']));
        return $response;
    }

    public function openBackupMethodForm()
    {
        $response = new AjaxResponse();
        $modal_form = $this->formBuilder->getForm('\Drupal\miniorange_2fa\Form\MoAuthConfigureBackupMethod');
        $response->addCommand(new OpenModalDialogCommand('Configure Backup 2FA method (KBA)', $modal_form, ['width' => '40%']));
        return $response;
    }

    public function openReConfigureForm()
    {
        $response = new AjaxResponse();
        $modal_form = $this->formBuilder->getForm('\Drupal\miniorange_2fa\Form\MoAuthReConfigure');
        $response->addCommand(new OpenModalDialogCommand('Re-Configure 2FA', $modal_form, ['width' => '50%']));
        return $response;
    }

    public function openUpdateForm()
    {
        $response = new AjaxResponse();
        $modal_form = $this->formBuilder->getForm('\Drupal\miniorange_2fa\Form\MoAuthPhoneUpdate');
        $response->addCommand(new OpenModalDialogCommand('Update Phone Number', $modal_form, ['width' => '40%']));
        return $response;
    }

    /**
     * Check whether user has entered valid
     * credentials and if available on Xecurify
     * dashboard.
     */
    public function headless_2fa_authenticate()
    {
        $utilities = new MoAuthUtilities();
        $variables_and_values = array(
            'mo_auth_enable_headless_two_factor',
            'mo_auth_headless_2fa_method',
            'mo_auth_customer_api_key',
        );
        $mo_db_values = $utilities->miniOrange_set_get_configurations($variables_and_values, 'GET');

        /**
         * Check if headless 2FA enabled
         */
        if ($mo_db_values['mo_auth_enable_headless_two_factor'] !== TRUE) {
            $utilities->mo_add_loggers_for_failures('Headless 2FA settings not enabled. Please enable the same under Headless 2FA Setup tab of the module.', 'error');
            $json['status'] = 'ERROR';
            $json['message'] = 'Something went wrong, please contact your administrator.';
            $json = json_encode($json);
            header("HTTP/1.1 404 Not Found");
            header('Content-Type: application/json;charset=utf-8');
            echo $json;
            exit;
        }

        $getCredentials = file_get_contents('php://input');
        $jsonCredentials = json_decode($getCredentials, TRUE);
        $username = $jsonCredentials['username'];
        $password = $jsonCredentials['password'];
        $API_KEY = $jsonCredentials['apiKey'];

        /**
         * If API_KEY is included in the received credentials
         */
        if (isset($jsonCredentials['apiKey']) && $API_KEY !== $mo_db_values['mo_auth_customer_api_key']) {
            $utilities->mo_add_loggers_for_failures('API authentication failed. Please send the correct apiKey: ' . $mo_db_values['mo_auth_customer_api_key'], 'error');
            $json['username'] = $username;
            $json['status'] = 'ERROR';
            $json['message'] = 'Authentication failed';
            $json = json_encode($json);
            header("HTTP/1.1 400 Not Verified");
            header('Content-Type: application/json;charset=utf-8');
            echo $json;
            exit;
        }

        if (!(\Drupal::service('user.auth')->authenticate($username, $password))) {
            $utilities->mo_add_loggers_for_failures($username . ' - Invalid username/password', 'error');
            $json['username'] = $username;
            $json['status'] = 'ERROR';
            $json['message'] = 'Invalid username/password';
            $json = json_encode($json);
            header("HTTP/1.1 401 Unauthorized");
            header('Content-Type: application/json;charset=utf-8');
            echo $json;
            exit;
        }

        $user = user_load_by_name($username);
        $email = $user->getEmail();
        $phone = $utilities->getUserPhoneNumber($user->id());
        $method = $mo_db_values['mo_auth_headless_2fa_method'];

        $customer = new MiniorangeCustomerProfile();
        $miniorange_user = new MiniorangeUser($customer->getCustomerID(), $email, $phone, NULL, $method, $email);
        $user_api_handler = new UsersAPIHandler($customer->getCustomerID(), $customer->getAPIKey());
        $response = $user_api_handler->search($miniorange_user);

        if ($response->status === 'USER_FOUND') {
            $auth_api_handler = new AuthenticationAPIHandler($customer->getCustomerID(), $customer->getAPIKey());
            $response = $auth_api_handler->challenge($miniorange_user);

            if (is_object($response) && $response->status === 'SUCCESS') {
                $json['username'] = $username;
                $json['status'] = 'SUCCESS';
                $json['message'] = $response->message;
                $json['transactionID'] = $response->txId;
                $json['authType'] = $response->authType;
                $json = json_encode($json);
                header("HTTP/1.1 200 Ok");
                header('Content-Type: application/json;charset=utf-8');
                echo $json;
                exit;
            } else {
                $utilities->mo_add_loggers_for_failures($response->message, 'error');
                $json['username'] = $username;
                $json['status'] = 'ERROR';
                $json['message'] = 'Something went wrong, please contact your administrator.';
                $json = json_encode($json);
                header("HTTP/1.1 500 Internal Server Error");
                header('Content-Type: application/json;charset=utf-8');
                echo $json;
                exit;
            }
        }

        if ($response->status === 'USER_NOT_FOUND') {
            /**
             * Create (end)user on Xecurify servers under admin account
             */
            $create_response = $user_api_handler->create($miniorange_user);
            if (isset($create_response) && isset($create_response->status) && isset($create_response->message) && $create_response->status == 'ERROR' && $create_response->message == t('Your user creation limit has been completed. Please upgrade your license to add more users.')) {
                $utilities->mo_add_loggers_for_failures('Your user creation limit has been completed. Please upgrade your license to add more users', 'error');
                $json['username'] = $username;
                $json['status'] = 'ERROR';
                $json['message'] = 'Something went wrong, please contact your administrator.';
                $json = json_encode($json);
                header("HTTP/1.1 500 Internal Server Error");
                header('Content-Type: application/json;charset=utf-8');
                echo $json;
                exit;
            }

            /**
             * Update User Auth method on Xecurify
             */
            $user_update_response = $user_api_handler->update($miniorange_user);
            if (is_object($user_update_response) && $user_update_response->status != 'SUCCESS') {
                $utilities->mo_add_loggers_for_failures($user_update_response->message, 'error');
                $json['username'] = $username;
                $json['status'] = 'ERROR';
                $json['message'] = 'Something went wrong, please contact your administrator.';
                $json = json_encode($json);
                header("HTTP/1.1 500 Internal Server Error");
                header('Content-Type: application/json;charset=utf-8');
                echo $json;
                exit;
            }

            $auth_api_handler = new AuthenticationAPIHandler($customer->getCustomerID(), $customer->getAPIKey());
            $response = $auth_api_handler->challenge($miniorange_user);
            if (is_object($response) && $response->status === 'SUCCESS') {
                $json['username'] = $username;
                $json['status'] = 'SUCCESS';
                $json['message'] = $response->message;
                $json['transactionID'] = $response->txId;
                $json['authType'] = $response->authType;
                $json = json_encode($json);
                header("HTTP/1.1 200 Ok");
                header('Content-Type: application/json;charset=utf-8');
                echo $json;
                exit;
            } else {
                $utilities->mo_add_loggers_for_failures($response->message, 'error');
                $json['username'] = $username;
                $json['status'] = 'ERROR';
                $json['message'] = 'Something went wrong, please contact your administrator.';
                $json = json_encode($json);
                header("HTTP/1.1 500 Internal Server Error");
                header('Content-Type: application/json;charset=utf-8');
                echo $json;
                exit;
            }
        }
    }

    /**
     * Check whether user has entered valid OTP.
     * if yes generate session.
     */
    public function headless_2fa_login()
    {

        $utilities = new MoAuthUtilities();
        $variables_and_values = array(
            'mo_auth_enable_headless_two_factor',
            'mo_auth_customer_api_key',
        );
        $mo_db_values = $utilities->miniOrange_set_get_configurations($variables_and_values, 'GET');

        /**
         * Check if headless 2FA enabled
         */
        if ($mo_db_values['mo_auth_enable_headless_two_factor'] !== TRUE) {
            $utilities->mo_add_loggers_for_failures('Headless 2FA settings not enabled. Please enable the same under Headless 2FA Setup tab of the module.', 'error');
            $json['status'] = 'ERROR';
            $json['message'] = 'Something went wrong, please contact your administrator.';
            $json = json_encode($json);
            header("HTTP/1.1 404 Not Found");
            header('Content-Type: application/json;charset=utf-8');
            echo $json;
            exit;
        }

        $getCredentials = file_get_contents('php://input');
        $jsonCredentials = json_decode($getCredentials, TRUE);

        $username = $jsonCredentials['username'];
        $txId = $jsonCredentials['transactionID'];
        $otp = $jsonCredentials['otp'];
        $authType = $jsonCredentials['authType'];
        $API_KEY = $jsonCredentials['apiKey'];

        $user = user_load_by_name($username);
        $email = $user->getEmail();

        /**
         * If API_KEY is included in the received credentials
         */
        if (isset($jsonCredentials['apiKey']) && $API_KEY !== $mo_db_values['mo_auth_customer_api_key']) {
            $utilities->mo_add_loggers_for_failures('API authentication failed. Please send the correct apiKey: ' . $mo_db_values['mo_auth_customer_api_key'], 'error');
            $json['username'] = $username;
            $json['status'] = 'ERROR';
            $json['message'] = 'Authentication failed';
            $json['apiKey'] = $API_KEY;
            $json['originalkey'] = $mo_db_values['mo_auth_customer_api_key'];
            $json = json_encode($json);
            header("HTTP/1.1 400 Not Verified");
            header('Content-Type: application/json;charset=utf-8');
            echo $json;
            exit;
        }

        $customer = new MiniorangeCustomerProfile();
        $miniorange_user = new MiniorangeUser($customer->getCustomerID(), $email, NULL, NULL, $authType);
        $auth_api_handler = new AuthenticationAPIHandler($customer->getCustomerID(), $customer->getAPIKey());
        $response = $auth_api_handler->validate($miniorange_user, $txId, $otp, NULL);

        $UserProfile = $user->toArray();
        unset($UserProfile['pass']);

        if ($response->status === 'SUCCESS') {
            user_login_finalize($user);
            $json['username'] = $username;
            $json['status'] = $response->status;
            $json['message'] = $response->message;
            $json['userprofile'] = $UserProfile;
            $json = json_encode($json);
            header("HTTP/1.1 200 Ok");
            header('Content-Type: application/json;charset=utf-8');
            echo $json;
            exit;

        } elseif ($response->status === 'FAILED') {
            MoAuthUtilities::mo_add_loggers_for_failures($response->message, 'error');
            $json['username'] = $username;
            $json['transactionID'] = $response->txId;
            $json['status'] = $response->status;
            $json['message'] = $response->message;
            $json['authType'] = $response->authType;
            $json = json_encode($json);
            header("HTTP/1.1 403 Forbidden");
            header('Content-Type: application/json;charset=utf-8');
            echo $json;
            exit;

        } else {
            MoAuthUtilities::mo_add_loggers_for_failures($response->message, 'error');
            $json['username'] = $username;
            $json['status'] = 'ERROR';
            $json['message'] = 'Something went wrong, please contact your administrator.';
            $json = json_encode($json);
            header("HTTP/1.1 500 Internal Server Error");
            header('Content-Type: application/json;charset=utf-8');
            echo $json;
            exit;
        }
    }

    /**
     * @param AccountInterface $user
     * @return RedirectResponse
     */
    function moResetTwoFactor(AccountInterface $user)
    {
        $variables_and_values = array(
            'mo_auth_firstuser_id',
        );

        $userID       = $user->id();
        $username     = $user->getAccountName();
        $current_user = \Drupal::currentUser();
        $current_user = $current_user->getAccountName();
        $mo_db_values = MoAuthUtilities::miniOrange_set_get_configurations($variables_and_values, 'GET');

        if (isset($mo_db_values['mo_auth_firstuser_id']) && $mo_db_values['mo_auth_firstuser_id'] == $userID) {
            $message = t("@current_user tried to reset the 2FA of @username", array('@current_user' => $current_user, '@username' => $username  ));
            \Drupal::logger('miniorange_2fa')->warning($message);
            \Drupal::messenger()->addError(t("You can not reset 2FA for (<strong>" . $username . "</strong>) account. Because this account has been used to activate/setup the module."));
        }
        else {
            $message = t("@current_user reset the 2FA of @username", array('@current_user' => $current_user, '@username' => $username  ));
            \Drupal::logger('miniorange_2fa')->info($message);
            MoAuthUtilities::delete_user_from_UserAuthentication_table($user);
            \Drupal::messenger()->addStatus(t("You have reset the 2FA for <strong>%username</strong> successfully.", array('%username' => $username)));
        }

        $url = \Drupal::request()->headers->get('referer');
        return new RedirectResponse($url);
    }

    /**
     * @param AccountInterface $user
     * @return RedirectResponse
     */
    function moUpdateTwoFactorStatus(AccountInterface $user)
    {
        MoAuthUtilities::update_user_status_from_UserAuthentication_table($user);
        $url = \Drupal::request()->headers->get('referer');
        return new RedirectResponse($url);
    }

    function accessRoute() {
      $uid = \Drupal::routeMatch()->getRawParameter('user');
      if ($uid != \Drupal::currentUser()->id()) {
        return AccessResult::forbidden();
      }
      return AccessResult::allowed();
    }

    function activate2FAMethod($method_name) {
      $utilities = new MoAuthUtilities();
      $user_obj  = User::load(\Drupal::currentUser()->id());
      $user_id   = $user_obj->id();
      $custom_attribute   = $utilities::get_users_custom_attribute($user_id);
      $configured_methods = $utilities::mo_auth_get_configured_methods($custom_attribute);
      $authType           = $method_name;

      $url        = Url::fromRoute('miniorange_2fa.setup_twofactor')->toString();
      $database   = \Drupal::database();
      $user_email = $custom_attribute[0]->miniorange_registered_email;
      $database->update('UserAuthenticationType')->fields(['activated_auth_methods' => $authType])->condition('miniorange_registered_email', $user_email, '=')->execute();

      if (in_array($authType, $configured_methods)) {
        $customer         = new MiniorangeCustomerProfile();
        $miniorange_user  = new MiniorangeUser($customer->getCustomerID(), $user_email, NULL, NULL, $authType);
        $user_api_handler = new UsersAPIHandler($customer->getCustomerID(), $customer->getAPIKey());
        $response         = $user_api_handler->update($miniorange_user);

        if (is_object($response) && $response->status == 'SUCCESS') {
          \Drupal::messenger()->addStatus(t('%method_name is set as active method successfully.', ['%method_name' => AuthenticationType::getAuthType($authType)['name']]));
          return new RedirectResponse($url);
        }
        \Drupal::messenger()->addError(t('An error occurred while updating the authentication type. Please try again.'));
        return new RedirectResponse($url);
      }

      \Drupal::messenger()->addError(t('Please configure this authentication method first to enable it.'));
      return new RedirectResponse($url);
    }

    function emptyGuideLink($method_code) {
      $method_detail = AuthenticationType::getAuthType($method_code);
      $response = new AjaxResponse();
      $modal_form = [
        '#type' => 'item',
        '#markup' => t('This video is currently unavailable. If you need some help configuring the module for this method, please view the <a target="_blank" href=":guide_link">setup guide</a> or reach out to us at <a href=":mail">@mail</a>' ,
          [
            ':guide_link' => $method_detail['doc-link'],
            ':mail' => 'mailto:drupalsupport@xecurify.com',
            '@mail' => 'drupalsupport@xecurify.com',
          ]
        ),
      ];
      $response->addCommand(new OpenModalDialogCommand('Coming Soon', $modal_form, ['width' => '40%']));
      return $response;
  }
}
