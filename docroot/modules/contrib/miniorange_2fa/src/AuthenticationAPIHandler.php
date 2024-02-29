<?php

namespace Drupal\miniorange_2fa;


class AuthenticationAPIHandler
{
    private $customerId;
    private $apiKey;

    public function __construct($customerId, $apiKey)
    {
        $this->apiKey = $apiKey;
        $this->customerId = $customerId;
    }

    public function challenge(MiniorangeUser $user)
    {
        $fields = array(
            'customerKey' => $user->getCustomerId(),
            'username' => $user->getAccountName(),
            'email' => $user->getEmail(),
            'phone' => $user->getPhone(),
            'authType' => $user->getAuthType(),
            'transactionName' => MoAuthConstants::$TRANSACTION_NAME
        );
        $json = json_encode($fields);
        $url = MoAuthConstants::getBaseUrl() . MoAuthConstants::$AUTH_CHALLENGE_API;
        return MoAuthUtilities::callService($this->customerId, $this->apiKey, $url, $json);
    }

    public function validate(MiniorangeUser $user, $txId, $passcode, $answers = NULL)
    {
        $fields = array(
            'customerKey' => $user->getCustomerId(),
            'username' => $user->getAccountName(),
            'txId' => $txId,
            'token' => str_replace(" ", "", $passcode),
            'authType' => $user->getAuthType(),
            'answers' => $answers
        );

        $json = json_encode($fields);
        $url = MoAuthConstants::getBaseUrl() . MoAuthConstants::$AUTH_VALIDATE_API;
        return MoAuthUtilities::callService($this->customerId, $this->apiKey, $url, $json);
    }

    public function getAuthStatus($txId)
    {
        $fields = array(
            'txId' => $txId
        );
        $json = json_encode($fields);
        $url = MoAuthConstants::getBaseUrl() . MoAuthConstants::$AUTH_STATUS_API;
        return MoAuthUtilities::callService($this->customerId, $this->apiKey, $url, $json);
    }

    public function getGoogleAuthSecret(MiniorangeUser $user)
    {
        $fields = array(
            'customerKey' => $user->getCustomerId(),
            'username' => $user->getAccountName(),
            'authenticatorName' => \Drupal::config('miniorange_2fa.settings')->get('mo_auth_google_auth_app_name') == '' ? 'miniOrangeAuth' : \Drupal::config('miniorange_2fa.settings')->get('mo_auth_google_auth_app_name'),
        );
        $json = json_encode($fields);
        $url = MoAuthConstants::getBaseUrl() . MoAuthConstants::$AUTH_GET_GOOGLE_AUTH_API;
        return MoAuthUtilities::callService($this->customerId, $this->apiKey, $url, $json);
    }

    public function register(MiniorangeUser $user, $registrationType, $secret, $token, $quesAnsList)
    {
        $authenticatorType = NULL;
        if ($registrationType == AuthenticationType::$HARDWARE_TOKEN['code']) {
            $authenticatorType = $registrationType;
            $registrationType = 'YUBIKEY_TOKEN';
        }
        $fields = array(
            'customerKey' => $user->getCustomerId(),
            'username' => $user->getAccountName(),
            'registrationType' => $registrationType,
            'secret' => $secret,
            'otpToken' => !empty($token) ? str_replace(" ", "", $token) : '',
            'authenticatorType' => $authenticatorType,
            'questionAnswerList' => $quesAnsList
        );
        $json = json_encode($fields);
        $url = MoAuthConstants::getBaseUrl() . MoAuthConstants::$AUTH_REGISTER_API;
        return MoAuthUtilities::callService($this->customerId, $this->apiKey, $url, $json);
    }

    public function getRegistrationStatus($txId)
    {
        $fields = array(
            'txId' => $txId
        );
        $json = json_encode($fields);

        $url = MoAuthConstants::getBaseUrl() . MoAuthConstants::$AUTH_REGISTRATION_STATUS_API;
        return MoAuthUtilities::callService($this->customerId, $this->apiKey, $url, $json);
    }
}
