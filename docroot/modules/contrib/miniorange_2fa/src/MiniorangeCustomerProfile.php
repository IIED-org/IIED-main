<?php
/**
 * @file
 * Contains constants class.
 */

namespace Drupal\miniorange_2fa;
/**
 * @file
 * This class represents User Profile.
 */
class MiniorangeCustomerProfile
{
    private $customer_id;
    private $registered_email;
    private $api_key;
    private $token_key;
    private $app_secret;
    private $registered_phone;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $variables_and_values = array(
            'mo_auth_customer_id',
            'mo_auth_customer_admin_email',
            'mo_auth_customer_api_key',
            'mo_auth_customer_admin_token',
            'mo_auth_customer_app_secret',
            'mo_auth_customer_admin_phone'
        );
        $mo_db_values = MoAuthUtilities::miniOrange_set_get_configurations($variables_and_values, 'GET');

        $this->customer_id = $mo_db_values['mo_auth_customer_id'];
        $this->registered_email = $mo_db_values['mo_auth_customer_admin_email'];
        $this->api_key = $mo_db_values['mo_auth_customer_api_key'];
        $this->token_key = $mo_db_values['mo_auth_customer_admin_token'];
        $this->app_secret = $mo_db_values['mo_auth_customer_app_secret'];
        $this->registered_phone = $mo_db_values['mo_auth_customer_admin_phone'];
    }

    public function getCustomerID()
    {
        return $this->customer_id;
    }

    public function getAPIKey()
    {
        return $this->api_key;
    }

    public function getTokenKey()
    {
        return $this->token_key;
    }

    public function getAppSecret()
    {
        return $this->app_secret;
    }

    public function getRegisteredEmail()
    {
        return $this->registered_email;
    }

    public function getRegisteredPhone()
    {
        return $this->registered_phone;
    }
}