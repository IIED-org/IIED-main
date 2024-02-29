<?php

namespace Drupal\miniorange_2fa;

class MiniorangeUser
{

    private $customerId;
    private $username;
    private $phone;
    private $name;
    private $authType;
    private $email;

    public function __construct($customerId, $username, $phone, $name, $authType, $email = "")
    {
        $this->customerId = $customerId;
        $this->username = $username;
        $this->phone = $phone;
        $this->name = $name;
        $this->authType = $authType;
        $this->email = $email;
    }

    public function update()
    {
        $apiKey = self::getApiKey();
        if (is_null($this->customerId) || is_null($apiKey)) {
            return FALSE;
        }
        $usersAPIHandler = new UsersAPIHandler($this->customerId, $apiKey);
        $response = $usersAPIHandler->update($this);
    }

    static function getApiKey()
    {
        $variables_and_values = array(
            'mo_auth_customer_api_key',
        );
        $mo_db_values = MoAuthUtilities::miniOrange_set_get_configurations($variables_and_values, 'GET');

        if ($mo_db_values['mo_auth_customer_api_key'] == '') {
            return NULL;
        }
        return $mo_db_values['mo_auth_customer_api_key'];
    }

    public function create()
    {
        $apiKey = self::getApiKey();
        if (empty($this->customerId) || empty($apiKey)) {
            return FALSE;
        }
        $usersAPIHandler = new UsersAPIHandler($this->customerId, $apiKey);
        $response = $usersAPIHandler->create($this);
    }

    public function search()
    {
        $apiKey = self::getApiKey();
        if (empty($this->customerId) || empty($apiKey)) {
            return FALSE;
        }
        $usersAPIHandler = new UsersAPIHandler($this->customerId, $apiKey);
        $response = $usersAPIHandler->search($this);
    }

    public function get()
    {
        $apiKey = self::getApiKey();
        if (empty($this->customerId) || empty($apiKey)) {
            return FALSE;
        }
        $usersAPIHandler = new UsersAPIHandler($this->customerId, $apiKey);
        $response = $usersAPIHandler->get($this);
    }

    public function getCustomerID()
    {
        return $this->customerId;
    }

    public function setCustomerID($customerId)
    {
        $this->customerId = $customerId;
    }

    public function getAccountName()
    {
        return $this->username;
    }

    public function setUsername($username)
    {
        $this->username = $username;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function setEmail($email)
    {
        $this->email = $email;
    }

    public function getPhone()
    {
        return $this->phone;
    }

    public function setPhone($phone)
    {
        $this->phone = $phone;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getAuthType()
    {
        return $this->authType;
    }

    public function setAuthType($authType)
    {
        $this->authType = $authType;
    }
}