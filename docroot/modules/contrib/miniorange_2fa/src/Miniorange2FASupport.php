<?php
/**
 * @file
 * Contains miniOrange Support class.
 */

namespace Drupal\miniorange_2fa;
/**
 * This class represents support information for
 * customer.
 */
class Miniorange2FASupport
{
    public $email;
    public $phone;
    public $query;

    /**
     * Constructor.
     */
    public function __construct($email, $phone, $query)
    {
        $this->email = $email;
        $this->phone = $phone;
        $this->query = $query;
    }

    /**
     * Send support query.
     */
    public function sendSupportQuery()
    {
        if (!MoAuthUtilities::isCurlInstalled()) {
            return (object)(array(
                "status" => 'CURL_ERROR',
                "message" => 'PHP cURL extension is not installed or disabled.'
            ));
        }

        $modules_info = \Drupal::service('extension.list.module')->getExtensionInfo('miniorange_2fa');
        $modules_version = $modules_info['version'];
        \Drupal::logger('miniorange_2fa')->info($modules_version);
        $version = $modules_version . ' | PHP ' . phpversion();
        $backdoor_url_status = \Drupal::config('miniorange_2fa.settings')->get('mo_auth_enable_backdoor');
        $backdoor_url_status = $backdoor_url_status ? 'Enabled' : 'Disabled';
        $cron_status = MoAuthUtilities::getCronInformation();

        $this->query = '[Drupal ' . MoAuthUtilities::mo_get_drupal_core_version() . ' 2FA Module | ' . $version . '] ' . $this->query.'<br><br>Backdoor Login: <b>'.$backdoor_url_status.'</b><br><br>Cron Status: <b>'.$cron_status.'</b>';

        $fields = array(
            'company' => $_SERVER['SERVER_NAME'],
            'email' => $this->email,
            'phone' => $this->phone,
            'ccEmail' => 'drupalsupport@xecurify.com',
            'query' => $this->query
        );
        $field_string = json_encode($fields);

        $url = MoAuthConstants::getBaseUrl() . MoAuthConstants::$SUPPORT_QUERY;
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($ch, CURLOPT_ENCODING, "");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_AUTOREFERER, TRUE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);

        curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'charset: UTF-8',
            'Authorization: Basic'
        ));
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $field_string);
        $content = curl_exec($ch);
        if (curl_errno($ch)) {
            \Drupal::logger('miniorange_2fa')->error("cURL Error at <strong>sendSupportQuery</strong> function of <strong>mo_auth_support.php</strong> file: " . curl_error($ch));
            return FALSE;
        }
        curl_close($ch);
        return TRUE;
    }

    /**
     * Send Trial Request.
     */
    public function sendTrialRequest()
    {
        if (!MoAuthUtilities::isCurlInstalled()) {
            return (object)(array(
                "status" => 'CURL_ERROR',
                "message" => 'PHP cURL extension is not installed or disabled.'
            ));
        }

        $modules_info = \Drupal::service('extension.list.module')->getExtensionInfo('miniorange_2fa');
        $modules_version = $modules_info['version'];

        $this->query = '[Drupal ' . MoAuthUtilities::mo_get_drupal_core_version() . ' 2FA Module ' . ' | ' . $modules_version . '] ' . $this->query;

        $fields = array(
            'company' => $_SERVER['SERVER_NAME'],
            'email' => $this->email,
            'phone' => $this->phone,
            'ccEmail' => 'drupalsupport@xecurify.com',
            'query' => $this->query
        );
        $field_string = json_encode($fields);

        $url = MoAuthConstants::getBaseUrl() . MoAuthConstants::$SUPPORT_QUERY;
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($ch, CURLOPT_ENCODING, "");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_AUTOREFERER, TRUE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);

        curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'charset: UTF-8',
            'Authorization: Basic'
        ));
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $field_string);
        $content = curl_exec($ch);
        if (curl_errno($ch)) {
            \Drupal::logger('miniorange_2fa')->error("cURL Error at <strong>sendSupportQuery</strong> function of <strong>mo_auth_support.php</strong> file: " . curl_error($ch));
            return FALSE;
        }
        curl_close($ch);
        return TRUE;
    }
}
