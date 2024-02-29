<?php
/**
 * @file
 * Contains Authentication type class.
 */

namespace Drupal\miniorange_2fa;
/**
 * @file
 * This class represents authentication type.
 */
class AuthenticationType
{

    const laptops_phones = 'Laptops Phones';
    const feature_phones = 'Feature Phones';
    const smartphones = 'Smartphones';
    const laptops = 'Laptops';
    const landline = 'Landline';
    const hardware_token = 'hardware-token';


    public static $SMS_AND_EMAIL = array(
        'id' => 'otp-over-sms-and-email',
        'name' => 'OTP Over SMS and EMAIL',
        'code' => 'SMS AND EMAIL',
        'type' => 'OTP',
        'description' => 'You will receive the same OTP (One Time Passcode) via SMS and email. You have to enter the OTP to authenticate yourself. ',
        'supported-for' => array('Laptops', 'Feature Phones', 'Smartphones'),
        'challenge' => TRUE,
        'oob' => FALSE,
        'doc-link' => 'https://www.drupal.org/docs/extending-drupal/contributed-modules/contributed-modules/setup-guides-to-configure-various-2fa-mfa-tfa-methods/one-time-passcode-otp-methods/setup-otp-over-sms-and-email-as-2fa-tfa-method',
        'video-link' => 'https://www.youtube.com/watch?v=8o6WwzHiMvU&list=PL2vweZ-PcNpeOYZk3tPy6MoDV8ELT0odI&index=4',
    );

    public static $SMS = array(
        'id' => 'otp-over-sms',
        'name' => 'OTP Over SMS',
        'code' => 'SMS',
        'type' => 'OTP',
        'description' => 'You will receive an OTP (One Time Passcode) via SMS on your phone. You have to enter the OTP to authenticate yourself.',
        'supported-for' => array('Feature Phones', 'Smartphones'),
        'challenge' => TRUE,
        'oob' => FALSE,
        'doc-link' => 'https://www.drupal.org/docs/extending-drupal/contributed-modules/contributed-modules/setup-guides-to-configure-various-2fa-mfa-tfa-methods/one-time-passcode-otp-methods/setup-otp-over-sms-as-2fa-tfa-method',
        'video-link' => 'https://www.youtube.com/watch?v=8o6WwzHiMvU&list=PL2vweZ-PcNpeOYZk3tPy6MoDV8ELT0odI&index=3',
    );

    public static $OTP_OVER_WHATSAPP = array(
        'id' => 'otp-over-whatsapp',
        'name' => 'OTP Over WhatsApp',
        'code' => 'WHATSAPP',
        'type' => 'OTP',
        'description' => 'You will receive a one time passcode via WhatsApp message on your phone. You have to enter the otp to login. Supported in Smartphones, Laptops.',
        'supported-for' => array('Smartphones'),
        'challenge' => TRUE,
        'oob' => FALSE,
        'doc-link' => 'https://www.drupal.org/docs/extending-drupal/contributed-modules/contributed-modules/setup-guides-to-configure-various-2fa-mfa-tfa-methods/one-time-passcode-otp-methods/setup-otp-over-whatsapp-as-2fa-tfa-method',
        'video-link' => '',
    );

    public static $OTP_OVER_PHONE = array(
        'id' => 'otp-over-phone',
        'name' => 'OTP Over Phone',
        'code' => 'PHONE VERIFICATION',
        'type' => 'OTP',
        'description' => 'You will receive an OTP (One Time Passcode) via phone call. You have to enter the OTP to authenticate yourself.',
        'supported-for' => array('Landline', 'Feature Phones', 'Smartphones'),
        'challenge' => TRUE,
        'oob' => FALSE,
        'doc-link' => 'https://www.drupal.org/docs/extending-drupal/contributed-modules/contributed-modules/setup-guides-to-configure-various-2fa-mfa-tfa-methods/one-time-passcode-otp-methods/setup-otp-over-phone-as-2fa-tfa-method',
        'video-link' => 'https://www.youtube.com/watch?v=d-2x_5njBCk&list=PL2vweZ-PcNpeOYZk3tPy6MoDV8ELT0odI&index=12',
    );

    public static $OTP_OVER_EMAIL = array(
        'id' => 'otp-over-email',
        'name' => 'OTP Over Email',
        'code' => 'EMAIL',
        'type' => 'OTP',
        'description' => 'You will receive an OTP (One Time Passcode) on the registered email address. You have to enter the OTP to authenticate yourself. ',
        'supported-for' => array('Laptops', 'Smartphones'),
        'challenge' => TRUE,
        'oob' => FALSE,
        'doc-link' => 'https://www.drupal.org/docs/extending-drupal/contributed-modules/contributed-modules/setup-guides-to-configure-various-2fa-mfa-tfa-methods/one-time-passcode-otp-methods/setup-otp-over-email-as-2fa-tfa-method',
        'video-link' => 'https://www.youtube.com/watch?v=H8RW-UWi_zY&list=PL2vweZ-PcNpeOYZk3tPy6MoDV8ELT0odI&index=5',
    );

    public static $EMAIL = array(
        'id' => 'email',
        'name' => 'OTP Over Email',
        'code' => 'EMAIL',
        'type' => 'OTP',
        'description' => 'You will receive a one time passcode via Email. You have to enter the otp on your screen to login. Supported in Smartphones, Feature Phones.',
        'supported-for' => array('Laptops', 'Smartphones'),
        'challenge' => TRUE,
        'oob' => FALSE,
        'doc-link' => 'https://www.drupal.org/docs/extending-drupal/contributed-modules/contributed-modules/setup-guides-to-configure-various-2fa-mfa-tfa-methods/one-time-passcode-otp-methods/setup-otp-over-email-as-2fa-tfa-method',
        'video-link' => 'https://www.youtube.com/watch?v=H8RW-UWi_zY&list=PL2vweZ-PcNpeOYZk3tPy6MoDV8ELT0odI&index=5',
    );

    public static $EMAIL_VERIFICATION = array(
        'id' => 'email-verification',
        'name' => 'Email Verification',
        'code' => 'OUT OF BAND EMAIL',
        'type' => 'OTHER',
        'description' => 'You will receive an email with links to either <em>Accept</em> or <em>Deny</em> the login attempt.',
        'supported-for' => array('Laptops', 'Smartphones'),
        'challenge' => TRUE,
        'oob' => TRUE,
        'doc-link' => 'https://www.drupal.org/docs/extending-drupal/contributed-modules/contributed-modules/setup-guides-to-configure-various-2fa-mfa-tfa-methods/other-tfa-methods/setup-email-verification-as-2fa-tfa-method',
        'video-link' => 'https://www.youtube.com/watch?v=Uj6SiYeII1c&list=PL2vweZ-PcNpeOYZk3tPy6MoDV8ELT0odI&index=10',
    );

    public static $GOOGLE_AUTHENTICATOR = array(
        'id' => 'google-authenticator',
        'name' => 'Google Authenticator',
        'code' => 'GOOGLE AUTHENTICATOR',
        'type' => 'TOTP',
        'description' => 'You have to scan the QR code from <em>Google Authenticator</em> App and enter the code generated by the Authenticator app to authenticate yourself.',
        'supported-for' => array('Smartphones'),
        'challenge' => FALSE,
        'oob' => FALSE,
        'android-link' => 'https://play.google.com/store/apps/details?id=com.google.android.apps.authenticator2',
        'ios-link' => 'https://apps.apple.com/us/app/google-authenticator/id388497605',
        'doc-link' => 'https://www.drupal.org/docs/extending-drupal/contributed-modules/contributed-modules/setup-guides-to-configure-various-2fa-mfa-tfa-methods/totp-tfa-methods-authenticator-apps/setup-google-authenticator-as-2fa-tfa-method',
        'video-link' => 'https://www.youtube.com/watch?v=IjcmXpZUwgk&list=PL2vweZ-PcNpeOYZk3tPy6MoDV8ELT0odI&index=1',
    );

    public static $MICROSOFT_AUTHENTICATOR = array(
        'id' => 'microsoft-authenticator',
        'name' => 'Microsoft Authenticator',
        'code' => 'MICROSOFT AUTHENTICATOR',
        'type' => 'TOTP',
        'description' => 'You have to scan the QR code from <em>Microsoft Authenticator</em> App and enter the code generated by the Authenticator app to authenticate yourself.',
        'supported-for' => array('Smartphones'),
        'challenge' => FALSE,
        'oob' => FALSE,
        'android-link' => 'https://play.google.com/store/apps/details?id=com.azure.authenticator',
        'ios-link' => 'https://apps.apple.com/us/app/microsoft-authenticator/id983156458',
        'doc-link' => 'https://www.drupal.org/docs/extending-drupal/contributed-modules/contributed-modules/setup-guides-to-configure-various-2fa-mfa-tfa-methods/totp-tfa-methods-authenticator-apps/setup-microsoft-authenticator-as-2fa-tfa-method',
        'video-link' => 'https://www.youtube.com/watch?v=ioUeyd___gk&list=PL2vweZ-PcNpeOYZk3tPy6MoDV8ELT0odI&index=2',
    );

    public static $OKTA_VERIFY = array(
        'id' => 'okta-verify',
        'name' => 'Okta Verify',
        'code' => 'OKTA VERIFY',
        'type' => 'TOTP',
        'description' => 'You have to scan the QR code from <em>Okta Verify</em> App and enter the code generated by the Authenticator app to authenticate yourself.',
        'supported-for' => array('Smartphones'),
        'challenge' => FALSE,
        'oob' => FALSE,
        'android-link' => 'https://play.google.com/store/apps/details?id=com.okta.android.auth',
        'ios-link' => 'https://apps.apple.com/us/app/okta-verify/id490179405',
        'doc-link' => 'https://www.drupal.org/docs/extending-drupal/contributed-modules/contributed-modules/setup-guides-to-configure-various-2fa-mfa-tfa-methods/totp-tfa-methods-authenticator-apps/setup-okta-verify-authenticator-as-2fa-tfa',
        'video-link' => 'https://www.youtube.com/watch?v=8LBhUTBBYIw&list=PL2vweZ-PcNpeOYZk3tPy6MoDV8ELT0odI&index=13',
    );

    public static $AUTHY_AUTHENTICATOR = array(
        'id' => 'authy-authenticator',
        'name' => 'Authy Authenticator',
        'code' => 'AUTHY AUTHENTICATOR',
        'type' => 'TOTP',
        'description' => 'You have to scan the QR code from <em>Authy Authenticator</em> App and enter the code generated by the Authenticator app to authenticate yourself.',
        'supported-for' => array('Smartphones'),
        'challenge' => FALSE,
        'oob' => FALSE,
        'android-link' => 'https://play.google.com/store/apps/details?id=com.authy.authy',
        'ios-link' => 'https://apps.apple.com/us/app/twilio-authy/id494168017',
        'doc-link' => 'https://www.drupal.org/docs/extending-drupal/contributed-modules/contributed-modules/setup-guides-to-configure-various-2fa-mfa-tfa-methods/totp-tfa-methods-authenticator-apps/setup-authy-authenticator-as-2fa-tfa-method',
        'video-link' => 'https://www.youtube.com/watch?v=xqpEC0CxKY0&list=PL2vweZ-PcNpeOYZk3tPy6MoDV8ELT0odI&index=6',
    );

    public static $LASTPASS_AUTHENTICATOR = array(
        'id' => 'lastpass-authenticator',
        'name' => 'LastPass Authenticator',
        'code' => 'LASTPASS AUTHENTICATOR',
        'type' => 'TOTP',
        'description' => 'You have to scan the QR code from <em>LastPass Authenticator</em> App and enter the code generated by the Authenticator app to authenticate yourself.',
        'supported-for' => array('Smartphones'),
        'challenge' => FALSE,
        'oob' => FALSE,
        'android-link' => 'https://play.google.com/store/apps/details?id=com.lastpass.authenticator',
        'ios-link' => 'https://apps.apple.com/us/app/lastpass-authenticator/id1079110004',
        'doc-link' => 'https://www.drupal.org/docs/extending-drupal/contributed-modules/contributed-modules/setup-guides-to-configure-various-2fa-mfa-tfa-methods/totp-tfa-methods-authenticator-apps/setup-lastpass-authenticator-as-2fa-tfa-method',
        'video-link' => 'https://www.youtube.com/watch?v=xzEed7qlUGI&list=PL2vweZ-PcNpeOYZk3tPy6MoDV8ELT0odI&index=10',
    );

    public static $DUO_AUTHENTICATOR = array(
        'id' => 'duo-authenticator',
        'name' => 'Duo Authenticator',
        'code' => 'DUO AUTHENTICATOR',
        'type' => 'TOTP',
        'description' => 'You have to scan the QR code from <em>Duo Authenticator</em> App and enter the code generated by the Authenticator app to authenticate yourself.',
        'supported-for' => array('Smartphones'),
        'challenge' => FALSE,
        'oob' => FALSE,
        'android-link' => 'https://play.google.com/store/apps/details?id=com.duosecurity.duomobile',
        'ios-link' => 'https://apps.apple.com/us/app/duo-mobile/id422663827',
        'doc-link' => 'https://www.drupal.org/docs/extending-drupal/contributed-modules/contributed-modules/setup-guides-to-configure-various-2fa-mfa-tfa-methods/totp-tfa-methods-authenticator-apps/setup-duo-authenticator-as-2fa-tfa-method',
        'video-link' => 'https://www.youtube.com/watch?v=mzFIl_EpSKo&list=PL2vweZ-PcNpeOYZk3tPy6MoDV8ELT0odI&index=4',
    );

    public static $_2FAS_AUTHENTICATOR = array(
        'id' => '2fas-authenticator',
        'name' => '2FAS Authenticator',
        'code' => '2FAS AUTHENTICATOR',
        'type' => 'TOTP',
        'description' => 'You have to scan the QR code from <em>2FAS Authenticator</em> App and enter the code generated by the Authenticator app to authenticate yourself.',
        'supported-for' => array('Smartphones'),
        'challenge' => FALSE,
        'oob' => FALSE,
        'android-link' => 'https://play.google.com/store/apps/details?id=com.twofasapp',
        'ios-link' => 'https://apps.apple.com/us/app/2fa-authenticator-2fas/id1217793794',
        'doc-link' => 'https://www.drupal.org/docs/extending-drupal/contributed-modules/contributed-modules/setup-guides-to-configure-various-2fa-mfa-tfa-methods/totp-tfa-methods-authenticator-apps/setup-2fas-authenticator-as-2fa-tfa-method',
        'video-link' => 'https://www.youtube.com/watch?v=KElnTNRHImQ&list=PL2vweZ-PcNpeOYZk3tPy6MoDV8ELT0odI&index=16',
    );

    public static $ZOHO_ONEAUTH = array(
        'id' => 'zoho-oneauth',
        'name' => 'Zoho OneAuth',
        'code' => 'ZOHO ONEAUTH',
        'type' => 'TOTP',
        'description' => 'You have to scan the QR code from <em>Zoho OneAuth</em> App and enter the code generated by the Authenticator app to authenticate yourself.',
        'supported-for' => array('Smartphones'),
        'challenge' => FALSE,
        'oob' => FALSE,
        'android-link' => 'https://play.google.com/store/apps/details?id=com.zoho.accounts.oneauth',
        'ios-link' => 'https://apps.apple.com/us/app/zoho-oneauth-authenticator/id1142928979',
        'doc-link' => 'https://www.drupal.org/docs/extending-drupal/contributed-modules/contributed-modules/setup-guides-to-configure-various-2fa-mfa-tfa-methods/totp-tfa-methods-authenticator-apps/setup-zoho-oneauth-authenticator-as-2fa-tfa',
        'video-link' => '',
    );

    public static $RSA_SECURID = array(
        'id' => 'rsa-securid',
        'name' => 'RSA SecurID',
        'code' => 'RSA SECURID',
        'type' => 'TOTP',
        'description' => 'You have to scan the QR code from <em>RSA SecurID</em> App and enter the code generated by the Authenticator app to authenticate yourself.',
        'supported-for' => array('Smartphones'),
        'challenge' => FALSE,
        'oob' => FALSE,
        'android-link' => 'https://play.google.com/store/apps/details?id=com.rsa.via',
        'ios-link' => 'https://apps.apple.com/us/app/rsa-securid-authenticate/id986524970',
        'doc-link' => 'https://www.drupal.org/docs/extending-drupal/contributed-modules/contributed-modules/setup-guides-to-configure-various-2fa-mfa-tfa-methods/totp-tfa-methods-authenticator-apps/setup-rsa-securid-authenticator-as-2fa-tfa',
        'video-link' => '',
    );

    public static $QR_CODE = array(
        'id' => 'qrcode-authentication',
        'name' => 'QR Code Authentication',
        'code' => 'MOBILE AUTHENTICATION',
        'type' => 'OTHER',
        'description' => 'You have to scan the QR code from <em>miniOrange Authenticator</em> App and enter the code generated by the Authenticator app to authenticate yourself.',
        'supported-for' => array('Smartphones'),
        'challenge' => TRUE,
        'oob' => TRUE,
        'android-link' => 'https://play.google.com/store/apps/details?id=com.miniorange.android.authenticator',
        'ios-link' => 'https://apps.apple.com/app/id1482362759',
        'doc-link' => 'https://www.drupal.org/docs/extending-drupal/contributed-modules/contributed-modules/setup-guides-to-configure-various-2fa-mfa-tfa-methods/other-tfa-methods/setup-qr-code-authentication-as-2fa-tfa-method',
        'video-link' => 'https://www.youtube.com/watch?v=1XKuDPHCWMc&list=PL2vweZ-PcNpeOYZk3tPy6MoDV8ELT0odI&index=8',

    );

    public static $KBA = array(
        'id' => 'kba-authentication',
        'name' => 'Security Questions (KBA)',
        'code' => 'KBA',
        'type' => 'OTHER',
        'description' => 'You have to answer some knowledge-based security questions which are only known to you.',
        'supported-for' => array('Laptops', 'Smartphones'),
        'challenge' => TRUE,
        'oob' => FALSE,
        'doc-link' => 'https://www.drupal.org/docs/extending-drupal/contributed-modules/contributed-modules/setup-guides-to-configure-various-2fa-mfa-tfa-methods/other-tfa-methods/setup-security-questions-kba-as-2fa-tfa-method',
        'video-link' => '',
    );

    public static $SOFT_TOKEN = array(
        'id' => 'soft-token',
        'name' => 'miniOrange Authenticator',
        'code' => 'SOFT TOKEN',
        'type' => 'TOTP',
        'description' => 'You have to enter passcode generated by <em>miniOrange Authenticator App</em> to login.',
        'supported-for' => array('Smartphones'),
        'challenge' => FALSE,
        'oob' => FALSE,
        'android-link' => 'https://play.google.com/store/apps/details?id=com.miniorange.android.authenticator',
        'ios-link' => 'https://apps.apple.com/app/id1482362759',
        'doc-link' => 'https://www.drupal.org/docs/extending-drupal/contributed-modules/contributed-modules/setup-guides-to-configure-various-2fa-mfa-tfa-methods/totp-tfa-methods-authenticator-apps/setup-miniorange-authenticator-as-2fa-tfa-method',
        'video-link' => '',
    );

    public static $PUSH_NOTIFICATIONS = array(
        'id' => 'push-notifications',
        'name' => 'Push Notifications',
        'code' => 'PUSH NOTIFICATIONS',
        'type' => 'OTHER',
        'description' => 'You will receive a push notification on your phone, with an option to either <em>Accept</em> or <em>Deny</em> the login request',
        'supported-for' => array('Smartphones'),
        'challenge' => TRUE,
        'oob' => TRUE,
        'android-link' => 'https://play.google.com/store/apps/details?id=com.miniorange.android.authenticator',
        'ios-link' => 'https://apps.apple.com/app/id1482362759',
        'doc-link' => 'https://www.drupal.org/docs/extending-drupal/contributed-modules/contributed-modules/setup-guides-to-configure-various-2fa-mfa-tfa-methods/other-tfa-methods/setup-push-notification-as-2fa-tfa-method',
        'video-link' => 'https://www.youtube.com/watch?v=o3h8cQsds2M&list=PL2vweZ-PcNpeOYZk3tPy6MoDV8ELT0odI&index=12',
    );

    public static $HARDWARE_TOKEN = array(
        'id' => 'hardware-token',
        'name' => 'Yubikey Hardware Token',
        'code' => 'HARDWARE TOKEN',
        'type' => 'OTHER',
        'description' => 'You have to insert your YubiKey Hardware token and press the gold disc to authenticate yourself.',
        'supported-for' => array('Hardware Token'),
        'challenge' => TRUE,
        'oob' => TRUE,
        'doc-link' => 'https://www.drupal.org/docs/extending-drupal/contributed-modules/contributed-modules/setup-guides-to-configure-various-2fa-mfa-tfa-methods/other-tfa-methods/setup-yubikey-hardware-token-as-2fa-tfa-method',
        'video-link' => 'https://www.youtube.com/watch?v=afaDrXKncFk&list=PL2vweZ-PcNpeOYZk3tPy6MoDV8ELT0odI&index=15',
    );
    public static $NOT_CONFIGURED = array(
        'id' => 'not-configured',
        'name' => 'Not Configured',
        'code' => 'NOT CONFIGURED',
        'description' => '',
        'supported-for' => array(
            'hardware-token'
        ),
        'challenge' => TRUE,
        'oob' => TRUE,
        'doc-link' => '',
        'video-link' => '',
    );

    public static function getAuthType($code)
    {
        $arr = array(
            "SMS_AND_EMAIL" => AuthenticationType::$SMS_AND_EMAIL,
            "OTP_OVER_EMAIL" => AuthenticationType::$OTP_OVER_EMAIL,
            "EMAIL" => AuthenticationType::$EMAIL,
            "SMS" => AuthenticationType::$SMS,
            "EMAIL_VERIFICATION" => AuthenticationType::$EMAIL_VERIFICATION,
            "GOOGLE_AUTHENTICATOR" => AuthenticationType::$GOOGLE_AUTHENTICATOR,
            "MICROSOFT_AUTHENTICATOR" => AuthenticationType::$MICROSOFT_AUTHENTICATOR,
            "OKTA VERIFY" => AuthenticationType::$OKTA_VERIFY,
            "DUO_AUTHENTICATOR" => AuthenticationType::$DUO_AUTHENTICATOR,
            "AUTHY_AUTHENTICATOR" => AuthenticationType::$AUTHY_AUTHENTICATOR,
            "LASTPASS AUTHENTICATOR" => AuthenticationType::$LASTPASS_AUTHENTICATOR,
            "QR_CODE" => AuthenticationType::$QR_CODE,
            "KBA" => AuthenticationType::$KBA,
            "SOFT_TOKEN" => AuthenticationType::$SOFT_TOKEN,
            "PUSH_NOTIFICATIONS" => AuthenticationType::$PUSH_NOTIFICATIONS,
            "PHONE_VERIFICATION" => AuthenticationType::$OTP_OVER_PHONE,
            "HARDWARE_TOKEN" => AuthenticationType::$HARDWARE_TOKEN,
            "_2FAS_AUTHENTICATOR" => AuthenticationType::$_2FAS_AUTHENTICATOR,
            "ZOHO_ONEAUTH" => AuthenticationType::$ZOHO_ONEAUTH,
            "RSA_SECURID" => AuthenticationType::$RSA_SECURID,
        );
        foreach ($arr as $authType) {
            if (strcasecmp($authType['code'], $code) == 0) {
                return $authType;
            }
        }
        return NULL;
    }
}
