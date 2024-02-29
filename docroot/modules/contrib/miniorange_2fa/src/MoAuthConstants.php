<?php
/**
 * @file
 * Contains constants class.
 */

namespace Drupal\miniorange_2fa;
/**
 * @file
 * This class represents constants used
 *     throughout project.
 */
class MoAuthConstants
{
    public static $PLUGIN_NAME                  = 'Drupal Two-Factor Plugin';
    public static $TRANSACTION_NAME             = 'Drupal Two-Factor Module';
    public static $APPLICATION_NAME             = 'drupal_2fa';
    public static $LICENSE_TYPE                 = 'DRUPAL_2FA_PLUGIN';
    public static $PREMIUM_PLAN                 = 'drupal_2fa_premium_plan';
    public static $ADD_USER_PLAN                = 'drupal_2fa_add_user_plan';
    public static $RENEW_SUBSCRIPTION_PLAN      = 'drupal_2fa_renew_subscription_plan';
    public static $WBSITE_SECURITY              = 'https://plugins.miniorange.com/drupal-web-security-pro';
    public static $PORTAL_URL                   = 'https://portal.miniorange.com/initializepayment?requestOrigin=';

    public static $DEFAULT_CUSTOMER_ID          = '16622';
    public static $DEFAULT_CUSTOMER_API_KEY     = 'XzjkmAaAOzmtJRmXddkXyhgDXnMCrdZz';

    public static $CUSTOMER_CHECK_API           = '/rest/customer/check-if-exists';
    public static $CUSTOMER_CREATE_API          = '/rest/customer/add';
    public static $CUSTOMER_GET_API             = '/rest/customer/key';
    public static $CUSTOMER_CHECK_LICENSE       = '/rest/customer/license';
    public static $SUPPORT_QUERY                = '/rest/customer/contact-us';

    public static $USERS_CREATE_API             = '/api/admin/users/create';
    public static $USERS_GET_API                = '/api/admin/users/get';
    public static $USERS_UPDATE_API             = '/api/admin/users/update';
    public static $USERS_SEARCH_API             = '/api/admin/users/search';
    public static $USERS_DELETE_API             = '/api/admin/users/delete';
    public static $USERS_DISABLE_API            = '/api/admin/users/disable';
    public static $USERS_ENABLE_API             = '/api/admin/users/enable';

    public static $AUTH_CHALLENGE_API           = '/api/auth/challenge';
    public static $AUTH_VALIDATE_API            = '/api/auth/validate';
    public static $AUTH_STATUS_API              = '/api/auth/auth-status';
    public static $AUTH_REGISTER_API            = '/api/auth/register';
    public static $AUTH_REGISTRATION_STATUS_API = '/api/auth/registration-status';
    public static $AUTH_GET_GOOGLE_AUTH_API     = '/api/auth/google-auth-secret';
    public static $AUTH_GET_ALL_USER_API        = '/api/admin/users/getall';

    //Case studies links
    const headless_drupal_2fa = 'https://www.drupal.org/case-study/secure-your-headless-drupal-website-with-robust-2-factor-authentication';
    const SSO_and_2fa = 'https://www.drupal.org/case-study/drupal-salesforce-sso-with-oauth-server-and-2fa';
    const hardware_token_2fa = 'https://www.drupal.org/case-study/abt-associates';
    const passwordless_login = 'https://www.drupal.org/case-study/passwordless-login';
    const drupal_case_studies = 'https://www.drupal.org/node/3196471/case-studies';

    //Guide links
    const INLINE_REGISTRATION = 'https://www.drupal.org/docs/extending-drupal/contributed-modules/contributed-modules/setup-guides-to-configure-various-2fa-mfa-tfa-methods/feature-guides/inline-registration';

    //KBA validation constants
    const KBA_ANSWER_LENGTH = 3;
    CONST ALPHANUMERIC_PATTERN        = '/^[\w\s]+$/'; // This is the pattern for preg_match() function
    CONST ALPHANUMERIC_LENGTH_PATTERN = '^[\w\s?]{'.self::KBA_ANSWER_LENGTH.',}$'; // This is the pattern for Javascript validation | Current pattern - '^[\w\s?]{3,}$'
    const VALIDATION_MESSAGE          = 'The answer must be at least '. self::KBA_ANSWER_LENGTH .' characters long and contain only alphanumeric characters.';


    /**
     * Function that handles the custom
     * organization name
     */
    public static function getBaseUrl()
    {
        $getBrandingName = \Drupal::config('miniorange_2fa.settings')->get('mo_auth_custom_organization_name');
        return "https://" . $getBrandingName . ".xecurify.com/moas";
    }
}
