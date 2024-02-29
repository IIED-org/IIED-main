<?php
/**
 * @file
 * Contains miniOrange Customer class.
 */

namespace Drupal\miniorange_2fa;

/**
 * @file
 * This class represents configuration for
 *     customer.
 */
class MiniorangeCustomerSetup
{
    public $email;
    public $phone;
    public $customerKey;
    public $transactionId;
    public $password;
    public $otpToken;
    public $defaultCustomerId;
    public $defaultCustomerApiKey;

    /**
     * Constructor.
     */
    public function __construct($email, $phone, $password, $otp_token)
    {
        $this->email = $email;
        $this->phone = $phone;
        $this->password = $password;
        $this->otpToken = $otp_token;
        $this->defaultCustomerId = MoAuthConstants::$DEFAULT_CUSTOMER_ID;
        $this->defaultCustomerApiKey = MoAuthConstants::$DEFAULT_CUSTOMER_API_KEY;
    }

    /**
     * Check if customer exists.
     */
    public function checkCustomer()
    {
        $url = MoAuthConstants::getBaseUrl() . MoAuthConstants::$CUSTOMER_CHECK_API;
        $fields = array(
            'email' => $this->email
        );
        $json = json_encode($fields);
        $response = MoAuthUtilities::callService($this->defaultCustomerId, $this->defaultCustomerApiKey, $url, $json, false);
        if (json_last_error() == JSON_ERROR_NONE && is_object($response) && strcasecmp($response->status, 'CURL_ERROR') == 0) {
            MoAuthUtilities::mo_add_loggers_for_failures($response->message, 'error');
        }
        return $response;
    }

    /**
     * Create Customer.
     */
    public function createCustomer()
    {
        $url = MoAuthConstants::getBaseUrl() . MoAuthConstants::$CUSTOMER_CREATE_API;
        $fields = array(
            'companyName' => $_SERVER['SERVER_NAME'],
            'areaOfInterest' => MoAuthConstants::$PLUGIN_NAME,
            'email' => $this->email,
            'phone' => $this->phone,
            'password' => $this->password
        );
        $json = json_encode($fields);
        $response = MoAuthUtilities::callService($this->defaultCustomerId, $this->defaultCustomerApiKey, $url, $json, false);
        if (json_last_error() == JSON_ERROR_NONE && strcasecmp($response->status, 'CURL_ERROR')) {
            MoAuthUtilities::mo_add_loggers_for_failures($response->message, $response->status === 'SUCCESS' ? 'info' : 'error');
        }
        return $response;
    }

    /**
     * Get Customer Keys.
     */
    public function getCustomerKeys()
    {
        $url = MoAuthConstants::getBaseUrl() . MoAuthConstants::$CUSTOMER_GET_API;
        $fields = array(
            'email' => $this->email,
            'password' => $this->password
        );
        $json = json_encode($fields);
        $response = MoAuthUtilities::callService($this->defaultCustomerId, $this->defaultCustomerApiKey, $url, $json);
        if (json_last_error() == JSON_ERROR_NONE && empty($response->apiKey)) {
            MoAuthUtilities::mo_add_loggers_for_failures($response->message, 'error');
        }
        return $response;
    }

    /**
     * Send OTP.
     */
    public function sendOtp()
    {
        $url = MoAuthConstants::getBaseUrl() . MoAuthConstants::$AUTH_CHALLENGE_API;
        $username = \Drupal::config('miniorange_2fa.settings')->get('mo_auth_customer_admin_email');

        $fields = array(
            'customerKey' => $this->defaultCustomerId,
            'email' => $username,
            'authType' => AuthenticationType::$EMAIL['code']
        );
        $json = json_encode($fields);
        $response = MoAuthUtilities::callService($this->defaultCustomerId, $this->defaultCustomerApiKey, $url, $json, false);
        if (json_last_error() == JSON_ERROR_NONE && strcasecmp($response->status, 'CURL_ERROR') == 0) {
            MoAuthUtilities::mo_add_loggers_for_failures($response->message, 'error');
        }
        return $response;
    }

    public function send_otp_token($uKey, $authType, $customerKey, $api_Key)
    {

        if ($authType == AuthenticationType::$SMS_AND_EMAIL['code']) {
            $fields = array(
                'customerKey' => $customerKey,
                'email' => $uKey['email'],
                'authType' => $authType,
                'transactionName' => 'Drupal 2 Factor Authentication Plugin'
            );
        } else if ($authType == 'OTP_OVER_SMS' || $authType == 'OTP_OVER_SMS_AND_EMAIL' || $authType == 'OTP_OVER_EMAIL' || $authType == 'PHONE_VERIFICATION') {

            if ($authType == 'OTP_OVER_SMS') {
                $authType = AuthenticationType::$SMS['code'];
            } elseif ($authType == 'PHONE_VERIFICATION') {
                $authType = AuthenticationType::$OTP_OVER_PHONE['code'];
            } elseif ($authType == 'OTP_OVER_SMS_AND_EMAIL') {
                $authType = AuthenticationType::$SMS_AND_EMAIL['code'];
            } elseif ($authType == 'OTP_OVER_EMAIL') {
                $authType = "OTP OVER EMAIL";
            }

            $phone = isset($uKey['phone']) ? $uKey['phone'] : '';
            $email = isset($uKey['email']) ? $uKey['email'] : '';
            if ($authType == AuthenticationType::$SMS_AND_EMAIL['code']) {
                $fields = array(
                    'customerKey' => $customerKey,
                    'phone' => $phone,
                    'email' => $email,
                    'authType' => $authType
                );
            } elseif ($authType == 'OTP OVER EMAIL') {
                $fields = array(
                    'customerKey' => $customerKey,
                    'email' => $email,
                    'authType' => $authType
                );
            } else {
                $fields = array(
                    'customerKey' => $customerKey,
                    'phone' => $phone,
                    'authType' => $authType
                );
            }
        }

        $url = MoAuthConstants::getBaseUrl() . MoAuthConstants::$AUTH_CHALLENGE_API;

        $json = json_encode($fields);

        return MoAuthUtilities::callService($customerKey, $api_Key, $url, $json);
    }

    /**
     * Validate OTP.
     */
    public function validate_otp_token($transactionId, $otpToken, $customerKey, $api_Key)
    {

        $fields = array(
            'txId' => $transactionId,
            'token' => $otpToken
        );

        $url = MoAuthConstants::getBaseUrl() . MoAuthConstants::$AUTH_VALIDATE_API;

        $json = json_encode($fields);

        return MoAuthUtilities::callService($customerKey, $api_Key, $url, $json);
    }
}