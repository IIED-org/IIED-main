<?php

namespace Drupal\miniorange_2fa;

class UsersAPIHandler
{
    private $customerId;
    private $apiKey;

    public function __construct($customerId, $apiKey)
    {
        $this->apiKey = $apiKey;
        $this->customerId = $customerId;
    }

    public function update(MiniorangeUser $user)
    {
        $fields = array(
            'customerKey' => $user->getCustomerId(),
            'username' => $user->getAccountName(),
            'phone' => $user->getPhone(),
            'authType' => $user->getAuthType(),
            'transactionName' => MoAuthConstants::$PLUGIN_NAME
        );
        $json = json_encode($fields);

        $url = MoAuthConstants::getBaseUrl() . MoAuthConstants::$USERS_UPDATE_API;

        return MoAuthUtilities::callService($this->customerId, $this->apiKey, $url, $json);
    }

    public function updateEmail(MiniorangeUser $user)
    {
        $fields = array(
            'customerKey' => $user->getCustomerId(),
            'username' => $user->getAccountName(),
            'email' => $user->getEmail(),
            'transactionName' => MoAuthConstants::$PLUGIN_NAME
        );
        $json = json_encode($fields);

        $url = MoAuthConstants::getBaseUrl() . MoAuthConstants::$USERS_UPDATE_API;

        return MoAuthUtilities::callService($this->customerId, $this->apiKey, $url, $json);
    }

    public function updateUserStatus(MiniorangeUser $user, $status)
    {
        $fields = array(
            'customerKey' => $user->getCustomerId(),
            'username' => $user->getAccountName(),
            'transactionName' => MoAuthConstants::$PLUGIN_NAME
        );
        $json = json_encode($fields);

        $url = MoAuthConstants::getBaseUrl() . MoAuthConstants::$USERS_ENABLE_API;
        if ($status === FALSE) {
            $url = MoAuthConstants::getBaseUrl() . MoAuthConstants::$USERS_DISABLE_API;
        }

        return MoAuthUtilities::callService($this->customerId, $this->apiKey, $url, $json);
    }


    public function get(MiniorangeUser $user)
    {
        $fields = array(
            'customerKey' => $user->getCustomerId(),
            'username' => $user->getAccountName()
        );
        $json = json_encode($fields);

        $url = MoAuthConstants::getBaseUrl() . MoAuthConstants::$USERS_GET_API;

        return MoAuthUtilities::callService($this->customerId, $this->apiKey, $url, $json);
    }

    /**
     * This function is used to check no. of
     * users in customer account is full or not.
     * Executes when check license is clicked in
     * Register/Login tab.
     */
    public function getall($tot_users)
    {
        $fields = array(
            'customerKey' => $this->customerId,
            'batchNo' => $tot_users,
            'batchSize' => 1
        );
        $json = json_encode($fields);

        $url = MoAuthConstants::getBaseUrl() . MoAuthConstants::$AUTH_GET_ALL_USER_API;

        return MoAuthUtilities::callService($this->customerId, $this->apiKey, $url, $json);
    }

    public function create(MiniorangeUser $user)
    {
        $fields = array(
            'customerKey' => $this->customerId,
            'username' => $user->getAccountName(),
            'firstName' => $user->getName()
        );
        $json = json_encode($fields);

        $url = MoAuthConstants::getBaseUrl() . MoAuthConstants::$USERS_CREATE_API;

        return MoAuthUtilities::callService($this->customerId, $this->apiKey, $url, $json);
    }

    public function delete(MiniorangeUser $user)
    {
        $fields = array(
            'customerKey' => $this->customerId,
            'username' => $user->getAccountName(),
        );
        $json = json_encode($fields);

        $url = MoAuthConstants::getBaseUrl() . MoAuthConstants::$USERS_DELETE_API;

        return MoAuthUtilities::callService($this->customerId, $this->apiKey, $url, $json);
    }

    public function search(MiniorangeUser $user)
    {
        $fields = array(
            'customerKey' => $user->getCustomerId(),
            'username' => $user->getAccountName()
        );
        $json = json_encode($fields);

        $url = MoAuthConstants::getBaseUrl() . MoAuthConstants::$USERS_SEARCH_API;

        return MoAuthUtilities::callService($this->customerId, $this->apiKey, $url, $json);
    }

    public function fetchLicense()
    {
        $fields = array(
            'customerId' => $this->customerId,
            'applicationName' => MoAuthConstants::$APPLICATION_NAME,
        );
        $json = json_encode($fields);

        $url = MoAuthConstants::getBaseUrl() . MoAuthConstants::$CUSTOMER_CHECK_LICENSE;

        return MoAuthUtilities::callService($this->customerId, $this->apiKey, $url, $json, false);
    }
}