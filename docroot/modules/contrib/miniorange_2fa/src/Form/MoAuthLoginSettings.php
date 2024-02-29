<?php
/**
 * @file
 * Contains support form for miniOrange 2FA Login
 *     Module.
 */

namespace Drupal\miniorange_2fa\Form;

use Drupal\Core\Url;
use Drupal\user\Entity\User;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\miniorange_2fa\MoAuthConstants;
use Drupal\miniorange_2fa\MoAuthUtilities;
use Drupal\miniorange_2fa\AuthenticationType;

/**
 * Showing LoginSetting form info.
 */
class MoAuthLoginSettings extends FormBase
{
    public function getFormId()
    {
        return 'miniorange_2fa_login_settings';
    }

    public function buildForm(array $form, FormStateInterface $form_state)
    {
        global $base_url;
        $utilities = new MoAuthUtilities();

        // For the safer side previous variables having whitelist keywords are kept on this page, please remove after this release
        $variables_and_values = array(
            'mo_auth_customer_admin_email',
            'mo_auth_2fa_license_type',
            'mo_auth_enable_two_factor',
            'mo_auth_enforce_inline_registration',
            'mo_auth_2fa_allow_reconfigure_2fa',
            'mo_auth_2fa_kba_questions',
            'mo_auth_enable_allowed_2fa_methods',
            'mo_auth_selected_2fa_methods',
            'mo_auth_enable_role_based_2fa',
            'mo_auth_role_based_2fa_roles',
            'mo_auth_enable_domain_based_2fa',
            'mo_auth_domain_based_2fa_domains',
            'mo_2fa_domain_and_role_rule',
            'mo_auth_use_only_2nd_factor',
            'mo_auth_enable_login_with_email',
            'mo_auth_enable_login_with_phone',
            'mo_auth_override_login_labels',
            'mo_auth_username_title',
            'mo_auth_username_description',
            'mo_auth_enable_trusted_IPs',
            'mo_auth_trusted_IP_address',
            'mo_auth_enable_whitelist_IPs', // Remove this variable after May 2023 release
            'mo_auth_whitelisted_IP_address',  // Remove this variable after May 2023 release
            'mo_auth_enable_custom_kba_questions',
            'mo_auth_redirect_user_after_login',
            'mo_auth_google_auth_app_name',
            // Advanced settings variables
            'mo_auth_custom_organization_name',
            'mo_auth_enable_2fa_for_password_reset',
            'mo_auth_customer_api_key',
            'mo_auth_enable_backdoor',

            // opt-in and opt-out variables
            'allow_end_users_to_decide',

            'auto_fetch_phone_number',
            'phone_number_field_machine_name',
            'auto_fetch_phone_number_country_code',

            // remember my device
            'mo_auth_rba',
            'mo_auth_rba_duration',
            'rba_allowed_devices',
            'mo_auth_rba_duration',
        );

        $mo_db_values = $utilities->miniOrange_set_get_configurations($variables_and_values, 'GET');
        $form['markup_top_2'] = array(
            '#markup' => '<div class="mo_2fa_table_layout_1"><div class="mo_2fa_table_layout mo_container_second_factor">'
        );

        $disabled = False;
        if (!$utilities::isCustomerRegistered()) {
            $form['header'] = array(
                '#markup' => t('<div class="mo_2fa_register_message"><p>You need to <a href="' . $base_url . '/admin/config/people/miniorange_2fa/customer_setup">Register/Login</a> with miniOrange before using this module.</p></div>'),
            );
            $disabled = True;
        }

        $form['markup_library'] = array(
            '#attached' => array(
                'library' => array(
                    "miniorange_2fa/miniorange_2fa.admin",
                    "miniorange_2fa/miniorange_2fa.license",
                    "miniorange_2fa/miniorange_2fa.copy_button",
                    "core/drupal.dialog.ajax",
                    "miniorange_2fa/miniorange_2fa.country_flag_dropdown",
                )
            ),
        );

        /**
         * Create container to hold @EnableTwo-Factor form elements.
         */
        $form['mo_Enable_two_factor_module'] = array(
            '#type' => 'fieldset',
            '#title' => t('2FA module settings &nbsp;<a target="_blank" href="https://developers.miniorange.com/docs/2fa-drupal/overview">[Know more]</a>'),
            '#attributes' => array('style' => 'padding:2% 2% 5%; margin-bottom:2%'),
            '#suffix ' => '<hr>',
        );

        $form['mo_Enable_two_factor_module']['mo_auth_enable_two_factor'] = array(
            '#type' => 'checkbox',
            '#default_value' => $mo_db_values['mo_auth_enable_two_factor'],
            '#title' => t('Enable Two-Factor module.'),
            '#disabled' => $disabled,
            '#description' => t('<strong><span style="color: red">Note:</span></strong> If you disable this checkbox, Second-Factor authentication will not be invoked for any user during login.'),
            '#prefix' => t('<br><hr>'),
            '#id' => "inlineRegistration",
            '#suffix' => '<br>',
        );

        $license_type = $mo_db_values['mo_auth_2fa_license_type'] == '' ? 'DEMO' : $mo_db_values['mo_auth_2fa_license_type'];
        $is_free = $license_type == 'DRUPAL_2FA_PLUGIN' || $license_type == 'PREMIUM' || $license_type == 'DRUPAL8_2FA_MODULE' ? FALSE : TRUE;
        $form['mo_Enable_two_factor_module']['mo_auth_enforce_inline_registration'] = array(
            '#type' => 'checkbox',
            '#title' => t('Enforce users to set up their 2FA Method on First Login (Inline Registration)'),
            '#default_value' => $mo_db_values['mo_auth_enforce_inline_registration'],
            '#description' => t('<strong>Note:</strong> If second factor is not setup for the user trying to login, they will be asked to setup before login.'),
            '#disabled' => $is_free,
            '#prefix' => t('<h5>Invoke Inline Registration to setup 2nd factor for users ' . MoAuthUtilities::mo_add_premium_tag() . '</span></h5><hr>'),
        );

        $form['mo_Enable_two_factor_module']['mo_auth_2fa_allow_reconfigure_2fa'] = array(
            '#type' => 'radios',
            '#title' => t('Change/Re-configure 2FA methods ' . $utilities::mo_add_premium_tag()),
            '#default_value' => $mo_db_values['mo_auth_2fa_allow_reconfigure_2fa'] == 'Allowed' ? 'Allowed' : 'Not_Allowed',
            '#options' => array(
                'Not_Allowed' => t('Not Allowed'),
                'Allowed' => t('Allowed'),
            ),
            '#disabled' => $is_free,
            '#prefix' => '<br><hr><br><div class="mo_2fa_highlight_background_note"><strong>Note: </strong>If you want to allow your users to change or re-configure their configured 2FA methods, then change the settings below.</div><div class="container-inline">',
            '#suffix' => '</div>'
        );

        $form['mo_Enable_two_factor_module']['mo_auth_2fa_kba_questions'] = array(
            '#type' => 'radios',
            '#title' => t('Security questions (KBA) as back up 2FA' . $utilities::mo_add_premium_tag()),
            '#default_value' => $mo_db_values['mo_auth_2fa_kba_questions'] == 'Not_Allowed' ? 'Not_Allowed' : 'Allowed',
            '#options' => array(
                'Not_Allowed' => t('Not Allowed'),
                'Allowed' => t('Allowed'),
            ),
            '#disabled' => $is_free,
            '#prefix' => '<br><hr><br><div class="mo_2fa_highlight_background_note"><strong>Note: </strong>If you do not want to allow user to configure security questions (KBA) as backup 2FA method, then change the settings below.</div><div class="container-inline">',
            '#suffix' => '</div>'
        );

        $form['mo_Enable_two_factor_module']['auto_fetch_phone_number'] = array(
            '#type' => 'checkbox',
            '#title' => t('Auto fetch phone number ' . $utilities::mo_add_premium_tag()),
            '#default_value' => $mo_db_values['auto_fetch_phone_number'],
            '#description' => t('<strong>Note:</strong> Enable this if you want to fetch phone number from user profile in the inline registration.'),
            '#disabled' => $is_free,
            '#prefix' => '<br><hr><br><div class="mo_2fa_highlight_background_note"><strong>Note: </strong>Enable this to auto fetch user\'s phone number from the profile attributes. Also, you can set the default country code.</div>',
        );

        $accountConfigUrl = Url::fromRoute('entity.user.field_ui_fields')->toString();
        $custom_fields = MoAuthUtilities::customUserFields();

        $form['mo_Enable_two_factor_module']['auto_fetch_phone_number_field_name'] = array(
            '#type' => 'select',
            '#title' => t('Select phone number field'),
            '#options' => $custom_fields,
            '#default_value' => $mo_db_values['phone_number_field_machine_name'],
            '#states' => array('visible' => array(':input[name = "auto_fetch_phone_number"]' => array('checked' => TRUE),),),
            '#description' => t('<strong>Note: </strong><a target="_blank" href="' . $accountConfigUrl . '">Click here</a> to check available fields on your Drupal site.'),
            '#disabled' => $is_free,
        );

        $form['mo_Enable_two_factor_module']['auto_fetch_phone_number_country_code'] = array(
            '#type' => 'textfield',
            '#title' => t('Select default country code'),
            '#default_value' => $mo_db_values['auto_fetch_phone_number_country_code'] . '00', //extra zeroes are append to show correct country code according to new JS library
            '#states' => array('visible' => array(':input[name = "auto_fetch_phone_number"]' => array('checked' => TRUE),),),
            '#disabled' => $is_free,
            '#id' => 'query_phone',
            '#attributes' => array('style' => 'width:15%;', 'class' => array('query_phone',)),
        );

        /**
         * Create container to hold @EnableAllowSpecific2Fa form elements.
         */
        $form['mo_Enable_allow_specific_2Fa'] = array(
            '#type' => 'details',
            '#title' => t('Allow specific 2FA methods to configure in inline registration ' . $utilities::mo_add_premium_tag()),
            '#open' => $mo_db_values['mo_auth_enable_allowed_2fa_methods'],
            '#attributes' => array('style' => 'padding:0% 2%; margin-bottom:2%')
        );
        $form['mo_Enable_allow_specific_2Fa']['mo_auth_enable_2fa_methods_for_inline'] = array(
            '#type' => 'checkbox',
            '#default_value' => $mo_db_values['mo_auth_enable_allowed_2fa_methods'],
            '#title' => t('Enable allow specific 2FA'),
            '#description' => t('<strong>Note:</strong> If you want to allow only specific 2FA methods to be configured by users while inline 2fa setup then, enable this checkbox and select appropriate 2fa methods.</br></br>'),
            '#disabled' => $is_free,
            '#prefix' => t('<hr><br><div class="mo_2fa_highlight_background_note"><strong>Note: </strong>To use this feature make sure you have enabled the "<u>ENFORCE 2 FACTOR REGISTRATION FOR USERS AT LOGIN TIME</u>" feature.</div>'),
        );

        $mo_get_2fa_methods = $utilities::get_2fa_methods_for_inline_registration(FALSE);
        $selected_2fa_methods = isset($mo_db_values['mo_auth_selected_2fa_methods']) ? json_decode($mo_db_values['mo_auth_selected_2fa_methods'], true) : '';

        $mo_2fa_method_type  = $utilities::get2FAMethodType($mo_get_2fa_methods);
        $table_rows = $utilities::generateMethodeTypeRows($mo_2fa_method_type, $selected_2fa_methods, $form_state);

        $form['mo_Enable_allow_specific_2Fa']['mo_auth_2fa_methods_table'] = [
          '#type' => 'table',
          '#header' => [
            $this->t('TOTP METHODS'), $this->t('OTP METHODS'), $this->t('OTHER METHODS')],
        ];

        foreach ($table_rows as $rowNum => $rows ) {
          $form['mo_Enable_allow_specific_2Fa']['mo_auth_2fa_methods_table'][$rowNum] = $rows;
        }

// Need to find solution for this checkbox
//        $form['mo_Enable_allow_specific_2Fa']['mo_auth_2fa_methods_advertise'] = array(
//            '#type' => 'checkboxes',
//            '#options' => array(AuthenticationType::$OTP_OVER_WHATSAPP['code'] => t(AuthenticationType::$OTP_OVER_WHATSAPP['name'] . ' <a href="mailto:drupalsupport@xecurify.com">[Contact us]</a>')),
//            '#disabled' => TRUE,
//            '#attributes' => array('style' => 'margin-bottom:1%'),
//        );

        /**
         * Create container to hold @RoleBased2FA form elements.
         */
        $form['mo_role_based_2fa'] = array(
            '#type' => 'details',
            '#title' => t('Role based 2FA ' . $utilities::mo_add_premium_tag()),
            '#open' => $mo_db_values['mo_auth_enable_role_based_2fa'],
            '#attributes' => array('style' => 'padding:0% 2%; margin-bottom:2%')
        );

        $form['mo_role_based_2fa']['mo_auth_two_factor_enable_role_based_2fa'] = array(
            '#type' => 'checkbox',
            '#default_value' => $mo_db_values['mo_auth_enable_role_based_2fa'],
            '#disabled' => $is_free,
            '#title' => t('Enable role based 2FA'),
            '#description' => t("<strong>Note:</strong> Enable this checkbox to allow 2FA for specific roles. 2FA will be invoked for the users having atleast one of the selected roles. </br><br>
                                 <strong>All Selected Methods:</strong> If you choose this option then the user will be able to choose from all the configured 2FA methods opted for, in the previous section. [Allow specific 2FA methods to configure in inline registration]"),
            '#prefix' => t('<hr><br><div class="mo_2fa_highlight_background_note"><strong>Note: </strong>If you have enabled "<u>LOGIN WITH 2ND FACTOR ONLY</u>" feature, Second-Factor authentication will invoke for all roles.</div><br>'),
        );

        $form['mo_role_based_2fa']['role_based_table_container'] = array(
             '#type' => 'container',
             '#states' => array('visible' => array(':input[name = "mo_auth_two_factor_enable_role_based_2fa"]' => array('checked' => TRUE),),),
        );

        $header = [
            'role' => $this->t('Role'),
            '2fa_method' => $this->t('2FA Method'),

        ];

        /* Table for role based 2FA methods */
        $form['mo_role_based_2fa']['role_based_table_container']['mo_auth_role_based_2fa_table'] = array(
            '#type' => 'table',
            '#header' => $header,
        );

       /**
        * @variable $roles_arr -> Original Drupal roles array
        * @variable $selected_roles -> Array of roles for which 2FA is enabled
        * @variable $role_based_2fa_methods -> Array of allowed 2FA methods
        */
        $roles_arr = $utilities::get_Existing_Drupal_Roles();

        $selected_roles = isset($mo_db_values['mo_auth_role_based_2fa_roles']) ? json_decode($mo_db_values['mo_auth_role_based_2fa_roles'], true) : array();

        $role_based_2fa_methods['ALL SELECTED METHODS'] = 'All Allowed Methods' ;
        $methods = $mo_db_values['mo_auth_enable_allowed_2fa_methods'] ? $selected_2fa_methods : $mo_get_2fa_methods ;

        foreach ($methods as $key => $value) {
            $role_based_2fa_methods[$key] = $value;
        }

        /* Table rows for role based 2FA method*/
        foreach ($roles_arr as $sysName => $displayName) {
            $form['mo_role_based_2fa']['role_based_table_container']['mo_auth_role_based_2fa_table'][$sysName]['checkbox'] = array(
              '#type' => 'checkbox',
              '#disabled' => $is_free,
              '#title' => t($displayName),
              '#id' => $sysName,
              '#default_value' => is_array($selected_roles) ? array_key_exists($sysName, $selected_roles) ? TRUE : FALSE : TRUE,
            );

            $form['mo_role_based_2fa']['role_based_table_container']['mo_auth_role_based_2fa_table'][$sysName]['2fa_methods'] = array(
              '#type' => 'select',
              '#options' => $role_based_2fa_methods,
              '#default_value' => $selected_roles[$sysName] ?? 'ALL SELECTED METHODS',
              '#states' => array('disabled' => array(':input[id = '.$sysName.']' => array('checked' => FALSE),),),
            );
        }


        /**
         * Create container to hold @DomainBased2FA form elements.
         */
        $form['mo_domain_based_2fa'] = array(
            '#type' => 'details',
            '#title' => t('Domain Based 2FA ' . $utilities::mo_add_premium_tag()),
            '#open' => $mo_db_values['mo_auth_enable_domain_based_2fa'],
            '#attributes' => array('style' => 'padding:0% 2%; margin-bottom:2%')
        );
        $form['mo_domain_based_2fa']['mo_auth_two_factor_invoke_2fa_depending_upon_domain'] = array(
            '#type' => 'checkbox',
            '#default_value' => $mo_db_values['mo_auth_enable_domain_based_2fa'],
            '#prefix' => t('<hr>'),
            '#disabled' => $is_free,
            '#title' => t('Enable User\'s Email Domain Based 2FA'),
            '#description' => t('<strong>Note:</strong> If you want to enable 2FA for specific domains then, enable this checkbox and enter the domains using semicolon(;) as a separator (<strong>eg. xxx.com;xxx.com;xxx.com</strong>)'),
        );
        $form['mo_domain_based_2fa']['mo_auth_domain_based_2fa_domains'] = array(
            '#type' => 'textarea',
            '#default_value' => $mo_db_values['mo_auth_domain_based_2fa_domains'],
            '#disabled' => $is_free,
            '#attributes' => array('placeholder' => t('Enter semicolon(;) separated domains ( eg. xxx.com;xxx.com;xxx.com )'),),
            '#states' => array('disabled' => array(':input[name = "mo_auth_two_factor_invoke_2fa_depending_upon_domain"]' => array('checked' => FALSE),),),
            '#suffix' => '<br>',
        );

        $form['mo_domain_based_2fa']['mo_2fa_rule_for_domain'] = array(
            '#type' => 'radios',
            '#title' => t('Interaction between role based and domain based 2FA'),
            '#default_value' => $mo_db_values['mo_2fa_domain_and_role_rule'] == 'OR' ? 'OR' : 'AND',
            '#options' => array(
                'AND' => t('Invoke 2FA, if user belongs to Role as well as Domain'),
                'OR' => t('Invoke 2FA, if user belongs to either Role or Domain'),
            ),
            '#disabled' => $is_free,
            '#states' => array('disabled' => array(':input[name = "mo_auth_two_factor_invoke_2fa_depending_upon_domain"]' => array('checked' => FALSE),),),
            '#suffix' => '<br>',
        );

        /**
         * Create container to hold @LoginWith2ndFactorOnly form elements.
         */
        $form['mo_Enable_two_factor_instead_password'] = array(
            '#type' => 'details',
            '#title' => t('Passwordless Login ' . $utilities::mo_add_premium_tag()),
            '#open' => $mo_db_values['mo_auth_use_only_2nd_factor'],
            '#attributes' => array('style' => 'padding:0% 2%; margin-bottom:2%')
        );
        $form['mo_Enable_two_factor_instead_password']['markup_second_factor_instead_password_note'] = array(
            '#markup' => t('<hr><br><div class="mo_2fa_highlight_background_note"><strong>Note: </strong>By default 2nd Factor is enabled after password authentication.
             If you do not want to remember passwords anymore and just login with 2nd Factor, please enable the option below.</div>'),
        );
        $form['mo_Enable_two_factor_instead_password']['mo_auth_two_factor_instead_password'] = array(
            '#type' => 'checkbox',
            '#default_value' => $mo_db_values['mo_auth_use_only_2nd_factor'],
            '#disabled' => $is_free,
            '#title' => t('Enable Password less login (Username/Email/Phone + 2nd Factor Authentication)'),
            '#description' => t('<strong>Note:</strong> To use this feature make sure you have enabled the <strong>"ENFORCE 2 FACTOR REGISTRATION FOR USERS AT LOGIN TIME"</strong> feature.'),
            '#states' => array('disabled' => array(':input[name = "mo_auth_enforce_inline_registration"]' => array('checked' => FALSE),),),
            '#suffix' => '<br><br>',
        );

        /**
         * Create container to hold @RememberMyDevice form elements.
         */
        $form['mo_remember_device'] = array(
            '#type' => 'details',
            '#title' => t('Remember My Device'),
            //'#open' => TRUE,
            '#attributes' => array('style' => 'padding:0% 2%; margin-bottom:2%')
        );

        $form['mo_remember_device']['mo_auth_rba'] = array(
            '#type' => 'checkbox',
            '#title' => t('Enable Remember My Device'),
            '#default_value' => $mo_db_values['mo_auth_rba'],
            '#description' => t('<strong>Note:</strong>Allow users to use remember the device(s) and skip 2FA for specific time.'),
            '#disabled' => $is_free,
        );

        $form['mo_remember_device']['rba_duration'] = array(
            '#type' => 'number',
            '#title' => t('Enter the Device Profiles Expiry Time (In days).'),
            '#default_value' => $mo_db_values['mo_auth_rba_duration'],
            '#min' => 1,
            '#max' => 365,
            '#step' => 1,
            '#states' => array('visible' => array(':input[name = "mo_auth_rba"]' => array('checked' => TRUE),),),
            '#description' => t('<strong>Note: </strong>Enter the number of days for which you wish to Remember device profiles.'),
            '#disabled' => $is_free,
        );

        $form['mo_remember_device']['rba_allowed_devices'] = array(
            '#type' => 'number',
            '#title' => t('Enter the number of devices'),
            '#default_value' => $mo_db_values['rba_allowed_devices'],
            '#min' => 1,
            '#max' => 5,
            '#step' => 1,
            '#states' => array('visible' => array(':input[name = "mo_auth_rba"]' => array('checked' => TRUE),),),
            '#description' => t('<strong>Note: </strong>Enter the number of devices allowed to be remembered at a time.'),
            '#disabled' => $is_free,
            '#suffix' => '<br>',
        );


        /**
         * Create container to hold @loginWithEmail&Phone form elements.
         */
        $form['mo_login_with_Email'] = array(
            '#type' => 'details',
            '#title' => t('Alter default login form ( Enable login with Email/Phone )'),
            '#open' => $mo_db_values['mo_auth_enable_login_with_email'] || $mo_db_values['mo_auth_enable_login_with_phone'],
            '#attributes' => array('style' => 'padding:0% 2%; margin-bottom:2%')
        );
        $form['mo_login_with_Email']['mo_auth_two_factor_enable_login_with_email'] = array(
            '#type' => 'checkbox',
            '#disabled' => $disabled,
            '#default_value' => $mo_db_values['mo_auth_enable_login_with_email'],
            '#title' => t('Enable login using email address'),
            '#description' => t('<strong>Note:</strong> This option enables login using email address as well as username.'),
            '#prefix' => t('<hr><br><div class="mo_2fa_highlight_background_note"><strong>Note: </strong>If you enable this feature, your users will be able to login with username, email address and phone number.</div>'),
        );
        $form['mo_login_with_Email']['mo_auth_two_factor_enable_login_with_phone'] = array(
            '#type' => 'checkbox',
            '#disabled' => $disabled,
            '#default_value' => $mo_db_values['mo_auth_enable_login_with_phone'],
            '#title' => t('Enable login using phone number'),
            '#description' => t('<strong>Note:</strong> This option enables login using phone number as well as username.'),
        );

        $form['mo_login_with_Email']['login_with_phone_number_field_machine_name'] = array(
            '#type' => 'select',
            '#title' => t('Select phone number field'),
            '#options' => $custom_fields,
            '#default_value' => $mo_db_values['phone_number_field_machine_name'],
            '#states' => array('visible' => array(':input[name = "mo_auth_two_factor_enable_login_with_phone"]' => array('checked' => TRUE),),),
            '#description' => t('<strong>Note: </strong><a target="_blank" href=" ' . $accountConfigUrl . ' ">Click here</a> to check the machine name of the phone number field.<br><br>'),
            '#disabled' => $is_free,
        );

        $form['mo_login_with_Email']['mo_auth_two_factor_override_login_labels'] = array(
            '#type' => 'checkbox',
            '#disabled' => $disabled,
            '#title' => t('Override login form username title and description'),
            '#default_value' => $mo_db_values['mo_auth_override_login_labels'],
            '#description' => t('<strong>Note: </strong>This option allows you to override the login form username title/description.'),
        );
        $form['mo_login_with_Email']['mo_auth_two_factor_username_title'] = array(
            '#type' => 'textfield',
            '#title' => t('Login form username title'),
            '#default_value' => $mo_db_values['mo_auth_username_title'],
            '#attributes' => array('placeholder' => t('eg. Login with username/email address')),
            '#description' => t('<strong>Note: </strong>Override the username field title.'),
            '#states' => array('disabled' => array(':input[name = "mo_auth_two_factor_override_login_labels"]' => array('checked' => FALSE),),),
        );
        $form['mo_login_with_Email']['mo_auth_two_factor_username_description'] = array(
            '#type' => 'textfield',
            '#title' => t('Login form username description'),
            '#default_value' => $mo_db_values['mo_auth_username_description'],
            '#attributes' => array('placeholder' => t('eg. You can use your username or email address to login.')),
            '#description' => t('<strong>Note: </strong>Override the username field description.<br><br>'),
            '#states' => array('disabled' => array(':input[name = "mo_auth_two_factor_override_login_labels"]' => array('checked' => FALSE),),),
        );

        /**
         * Create container to hold @TrustedIPAddresses form elements.
         */
        $form['mo_Trusted_IP_addresses'] = array(
            '#type' => 'details',
            '#title' => t('Trusted IP addresses ' . $utilities::mo_add_premium_tag()),
            '#open' => empty($mo_db_values['mo_auth_enable_trusted_IPs']) ? $mo_db_values['mo_auth_enable_whitelist_IPs'] : $mo_db_values['mo_auth_enable_trusted_IPs'],  // Make change here after May 2023 release
            '#attributes' => array('style' => 'padding:0% 2%; margin-bottom:2%')
        );
        $form['mo_Trusted_IP_addresses']['mo_auth_two_factor_invoke_2fa_depending_upon_IP'] = array(
            '#type' => 'checkbox',
            '#default_value' => empty($mo_db_values['mo_auth_enable_trusted_IPs']) ? $mo_db_values['mo_auth_enable_whitelist_IPs'] : $mo_db_values['mo_auth_enable_trusted_IPs'],  // Make change here after May 2023 release
            '#prefix' => t('<hr>'),
            '#disabled' => $is_free,
            '#title' => t('Trusted IP addresses'),
            '#description' => t('<strong>Note:</strong> Second factor authentication will not be invoked for these trusted IPs'),
        );
        $form['mo_Trusted_IP_addresses']['mo_auth_two_factor_trusted_IP'] = array(
            '#type' => 'textarea',
            '#default_value' => empty($mo_db_values['mo_auth_trusted_IP_address']) ? $mo_db_values['mo_auth_whitelisted_IP_address'] : $mo_db_values['mo_auth_trusted_IP_address'] ,
            '#disabled' => $is_free,
            '#attributes' => array('placeholder' => t('Enter semicolon(;) separated IP addresses ( Format for range: lower_range - upper_range )'),),
            '#states' => array('disabled' => array(':input[name = "mo_auth_two_factor_invoke_2fa_depending_upon_IP"]' => array('checked' => FALSE),),),
            '#suffix' => '<br>',
        );


        /**
         * Create container to hold @CustomizeKBAQuestions form elements.
         */
        $form['mo_customize_kba_option'] = array(
            '#type' => 'details',
            '#title' => t('Customize KBA questions'),
            '#id' => t('customize_kba'),
            '#open' => $mo_db_values['mo_auth_enable_custom_kba_questions'],
            '#attributes' => array('style' => 'padding:0% 2%; margin-bottom:2%')
        );
        $form['mo_customize_kba_option']['markup_custom_kba_questions_note'] = array(
            '#markup' => t('<hr><br><div class="mo_2fa_highlight_background_note"><b>Note: </b>Format for entering the KBA questions.
                        <ul>
                            <li>Enter semicolon ( ; ) separated questions including ( ? ) question mark.</li>
                            <li>No spaces before and after the semicolon ( ; ).</li>
                            <li>No semicolon ( ; ) after the last question.</li>
                            <li><strong>eg.</strong> This is the first question?;This is the second question?</li>
                        </ul></div>'),
        );
        $form['mo_customize_kba_option']['mo_auth_enable_custom_kba_questions'] = array(
            '#type' => 'checkbox',
            '#title' => t('Add custom KBA Questions'),
            '#default_value' => $mo_db_values['mo_auth_enable_custom_kba_questions'],
            '#disabled' => $disabled,
            '#description' => t('<strong>Note:</strong> If you want to add custom KBA questions, enable this option and add two set of questions below.'),
        );
        $form['mo_customize_kba_option']['mo_auth_enable_custom_kba_set_1'] = array(
            '#type' => 'textarea',
            '#title' => t('Enter question set 1'),
            '#default_value' => $utilities::mo_get_kba_questions('ONE', 'STRING'),
            '#disabled' => $disabled,
            '#states' => array('disabled' => array(':input[name = "mo_auth_enable_custom_kba_questions"]' => array('checked' => FALSE),),),
        );
        $form['mo_customize_kba_option']['mo_auth_enable_custom_kba_set_2'] = array(
            '#type' => 'textarea',
            '#title' => t('Enter question set 2'),
            '#default_value' => $utilities::mo_get_kba_questions('TWO', 'STRING'),
            '#disabled' => $disabled,
            '#states' => array('disabled' => array(':input[name = "mo_auth_enable_custom_kba_questions"]' => array('checked' => FALSE),),),
            '#suffix' => '<br>',
        );


        /**
         * Create container to hold @CustomizeEmailSMSTemplate form elements.
         */
        $email_template_url = MoAuthConstants::getBaseUrl() . '/login?username=' . $mo_db_values['mo_auth_customer_admin_email'] . '&redirectUrl=' . MoAuthConstants::getBaseUrl() . '/admin/customer/emailtemplateconfiguration';
        $logo_favicon_url = MoAuthConstants::getBaseUrl() . '/login?username=' . $mo_db_values['mo_auth_customer_admin_email'] . '&redirectUrl=' . MoAuthConstants::getBaseUrl() . '/admin/customer/customerrebrandingconfig';
        $sms_template_url = MoAuthConstants::getBaseUrl() . '/login?username=' . $mo_db_values['mo_auth_customer_admin_email'] . '&redirectUrl=' . MoAuthConstants::getBaseUrl() . '/admin/customer/showsmstemplate';
        $otp_url = MoAuthConstants::getBaseUrl() . '/login?username=' . $mo_db_values['mo_auth_customer_admin_email'] . '&redirectUrl=' . MoAuthConstants::getBaseUrl() . '/admin/customer/customerpreferences';
        $form['mo_customize_email_sms_template'] = array(
            '#type' => 'details',
            '#title' => t('Customize SMS and Email Template'),
            '#id' => 'sms_template',
            //'#open' => TRUE,
            '#attributes' => array('style' => 'padding:0% 2%; margin-bottom:2%'),
        );
        $form['mo_customize_email_sms_template']['customize_email_template'] = array(
            '#markup' => '<hr><br>
                         <div class="mo_customize_email_sms_template"><strong>Steps to customize email template</strong>
                             <ol>
                                <li>Click <a target="_blank" href="' . $email_template_url . '">here</a> and login.</li>
                                <li>Select Email Template to configure.</li>
                                <li>Switch to <u>SET CUSTOMIZED EMAIL TEMPLATE</u> radio button.</li>
                             </ol>
                         </div><hr><br>
                         <div class="mo_customize_email_sms_template"><strong>Steps to customize Logo and Favicon ( These are used in Email template )</strong>
                             <ol>
                                <li>Click <a target="_blank" href="' . $logo_favicon_url . '">here</a> and login.</li>
                                <li>Navigate to <u>LOGO AND FAVICON</u> tab.</li>
                                <li>Upload images for logo and favicon and save.</li>
                             </ol>
                         </div><hr><br>
                         <div class="mo_customize_email_sms_template"><strong>Steps to customize SMS template</strong>
                             <ol>
                                <li>Click <a target="_blank" href="' . $sms_template_url . '">here</a> and login.</li>
                                <li>Select SMS Template to configure.</li>
                                <li>Switch to <u>SET CUSTOMIZED SMS TEMPLATE</u> radio button.</li>
                             </ol>
                         </div><hr><br>
                         <div class="mo_customize_email_sms_template"><strong>Steps to customize OTP Length and Validity</strong>
                             <ol>
                                <li>Click <a target="_blank" href="' . $otp_url . '">here</a> and login.</li>
                                <li>Navigate to <u>ONE TIME PASSCODE (OTP) SETTINGS</u> option.</li>
                             </ol>
                         </div><br>
                         ',
        );

        /**
         * Create container to hold @2faOpt-inAndOpt-out form elements.
         */

        $form['mo_mfa_opt'] = array(
            '#type' => 'details',
            '#title' => $this->t("Opt-in and Opt-out options" . $utilities::mo_add_premium_tag()),
            '#open' => $mo_db_values['allow_end_users_to_decide'],
            '#attributes' => array('style' => 'padding:0% 2%; margin-bottom:2%')
        );

        $form['mo_mfa_opt']['allow_end_users_to_decide'] = array(
            '#type' => 'checkbox',
            '#title' => $this->t("Users can choose to opt-in or opt-out from 2FA"),
            '#disabled' => $is_free,
            '#default_value' => $mo_db_values['allow_end_users_to_decide'],
            '#description' => $this->t('<strong>Note: </strong>If you enable this option then user will get an option to enable/disable 2FA in their profile. Also user can skip inline registration.'),
        );

        /**
         * Create container to hold @AdvanceSettingsOption form elements.
         */
        $form['mo_advance_settings_option'] = array(
            '#type' => 'details',
            '#title' => t('Advance Settings'),
            '#attributes' => array('style' => 'padding:0% 2%; margin-bottom:3%')
        );


        $form['mo_advance_settings_option']['mo_auth_redirect_user_after_login'] = array(
            '#type' => 'textfield',
            '#title' => t('Redirect user after login'),
            '#default_value' => $mo_db_values['mo_auth_redirect_user_after_login'] == '' ? $base_url . '/user' : $mo_db_values['mo_auth_redirect_user_after_login'],
            '#attributes' => array('placeholder' => 'Enter the redirect URL', 'style' => 'width:100%', 'title' => 'This is my tooltip'),
            '#description' => t('<strong>Note: </strong>Enter the entire URL (<em> including https:// </em>) where you want to redirect user after successful authentication.'),
            '#disabled' => $disabled,
            '#prefix' => t('<br><hr><br>'),
            '#suffix' => '<br>',
        );

        $form['mo_advance_settings_option']['mo_auth_two_factor_google_authenticator_app_name'] = array(
            '#type' => 'textfield',
            '#title' => t('Change Google Authenticator account name'),
            '#default_value' => $mo_db_values['mo_auth_google_auth_app_name'] == '' ? 'miniOrangeAuth' : urldecode($mo_db_values['mo_auth_google_auth_app_name']),
            '#attributes' => array(
                'style' => 'width:100%',
                //'pattern' => '^[a-zA-Z0-9@#$&()-_.]+$',
            ),
            '#disabled' => $disabled,
            '#description' => t('<strong>Note: </strong>If you want to change the account name which will be shown in Google Authenticator app after configuring, then change this value.<strong> After changing this you will have to reconfigure your account into Google Authenticator app.</strong>'),
            '#prefix' => '<br><hr><br>',
        );

        /**
         *Create container to hold custom organization name.
         */
        $form['mo_advance_settings_option']['mo_auth_custom_organization_name'] = array(
            '#type' => 'textfield',
            '#title' => t('Enter custom organization name'),
            '#default_value' => $mo_db_values['mo_auth_custom_organization_name'] == '' ? 'login' : urldecode($mo_db_values['mo_auth_custom_organization_name']),
            '#attributes' => array(
                'style' => 'width:100%',
            ),
            '#disabled' => $disabled,
            '#description' => t('<strong>Note: </strong>If you have set the <strong>Organization Name</strong> under Basic Settings tab in <a target="_blank" href="' . $logo_favicon_url . '">Xecurify dashboard</a> then change this value same as Organization Name.'),
            '#prefix' => t('<br><hr><br>'),
        );

        /**
         * Create container to hold Enable 2fa for password reset.
         */
        $form['mo_advance_settings_option']['mo_auth_enable_2fa_for_password_reset'] = array(
            '#type' => 'checkbox',
            '#title' => t('Enable two factor authentication for password reset ' . $utilities::mo_add_premium_tag()),
            '#description' => t('<b>Note: </b>Checking this option will enable the two factor authentication for password reset flow.</strong>'),
            '#disabled' => $is_free,
            '#default_value' => $mo_db_values['mo_auth_enable_2fa_for_password_reset'],
            '#prefix' => t('<br><hr><br>'),
        );

        $form['mo_advance_settings_option']['mo_auth_backdoor_warning'] = array(
            '#type' => 'label',
            '#title' => t('It is highly recommended to keep backdoor login enabled.'),
            '#attributes' => array('class' => 'mo_2fa_warning'),
            '#prefix' => t('<br><hr><br>'),
        );

        /**
         * Create container to hold backdoor url.
         */
        $config = \Drupal::config('miniorange_2fa.settings');
        $backdoor_url = $disabled == FALSE ? $base_url . '/user/login?skip_2fa=' . $mo_db_values['mo_auth_customer_api_key'] : 'Register/Login with miniOrange to see the URL.';
        $form['mo_advance_settings_option']['mo_auth_enable_backdoor'] = array(
            '#type' => 'checkbox',
            '#title' => t('Check this option if you want to enable <b>backdoor login</b>'),
            '#description' => t('<b>Note: </b>Checking this option creates a backdoor to login to your website using Drupal credentials, incase you get locked out.
                                        <br><strong>In order to login using backdoor URL, user must have administrator privileges.</strong>
                <b><br>Note down this backdoor URL:</b> <span id="miniorange_2fa_backdoor_url"><code><b><a> ' . $backdoor_url . ' </a></b></code></span><span class="button button--small mo_copy">&#128461; Copy</span><br><br>'),
            '#disabled' => $disabled,
            '#default_value' => $mo_db_values['mo_auth_enable_backdoor'] == '' ? False : $mo_db_values['mo_auth_enable_backdoor'],
        );

        $form['Submit_LoginSettings_form'] = array(
            '#type' => 'submit',
            '#id' => 'miniorange_2fa_save_config_btn',
            '#button_type' => 'primary',
            '#value' => t('Save Settings'),
            '#disabled' => $disabled,
            '#suffix' => '<br><br><br></div>'
        );

//        $utilities::miniOrange_advertise_case_studies($form, $form_state);

        return $form;
    }

    public function validateForm(array &$form, FormStateInterface $form_state)
    {
        $form_values = $form_state->getValues();
        if ($form_values['mo_auth_two_factor_invoke_2fa_depending_upon_IP'] === 1 && !empty($form_values['mo_auth_two_factor_trusted_IP'])) {
            $mo_trusted_IPs = preg_replace('/\s+/', '', $form_values['mo_auth_two_factor_trusted_IP']);
            $valid_IPs = MoAuthUtilities::check_for_valid_IPs($mo_trusted_IPs);
            if ($valid_IPs !== TRUE) {
                $form_state->setErrorByName('mo_auth_two_factor_trusted_IP', $this->t($valid_IPs));
            }
        }
        if ($form_values['mo_auth_two_factor_override_login_labels'] === 1) {
            if (empty($form_values['mo_auth_two_factor_username_title'])) {
                $form_state->setErrorByName('mo_auth_two_factor_username_title', $this->t('Username title is mandatory to enable<strong> Override login form username title and description</strong> option'));
            }
            if (empty($form_values['mo_auth_two_factor_username_description'])) {
                $form_state->setErrorByName('mo_auth_two_factor_username_description', $this->t('Username description is mandatory to enable<strong> Override login form username title and description</strong> option'));
            }
        }
    }

    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $utilities = new MoAuthUtilities();
        $form_values = $form_state->getValues();

        $phone_number_field_machine_name = $utilities->miniOrange_set_get_configurations(['phone_number_field_machine_name'], 'GET')['phone_number_field_machine_name'];
        if (is_null($phone_number_field_machine_name)) {
            $phone_number_field_machine_name = trim($form_values['auto_fetch_phone_number_field_name']) == '' ? trim($form_values['login_with_phone_number_field_machine_name']) : trim($form_values['auto_fetch_phone_number_field_name']);
        } else {
            if ($phone_number_field_machine_name == trim($form_values['auto_fetch_phone_number_field_name'])) {
                $phone_number_field_machine_name = trim($form_values['login_with_phone_number_field_machine_name']);
            } else {
                $phone_number_field_machine_name = trim($form_values['auto_fetch_phone_number_field_name']);
            }
        }

        if (trim($phone_number_field_machine_name) !== 'select') {
            $user = User::load(\Drupal::currentUser()->id());
            try {
                $user->get($phone_number_field_machine_name)->value;
            } catch (\Exception $e) {
                \Drupal::messenger()->addMessage(t('The field %phone_number_field_machine_name does not exist. Please enter correct machine name.', array('%phone_number_field_machine_name' => $phone_number_field_machine_name)), 'error');
                return;
            }
        }

        /**
         * @DO NOT REMOVE THE SPACES BETWEEN FOLLOWING LINES
         */
        $variables_and_values = array(
            'mo_auth_enable_two_factor' => $form_values['mo_auth_enable_two_factor'] === 1,
            'mo_auth_enforce_inline_registration' => $form_values['mo_auth_enforce_inline_registration'] === 1,
            'mo_auth_2fa_allow_reconfigure_2fa' => $form_values['mo_auth_2fa_allow_reconfigure_2fa'],
            'mo_auth_2fa_kba_questions' => $form_values['mo_auth_2fa_kba_questions'],

            'mo_auth_enable_allowed_2fa_methods' => $form_values['mo_auth_enable_2fa_methods_for_inline'],
            'mo_auth_selected_2fa_methods' => $utilities->getSelected2faMethods($form_state, 'mo_auth_2fa_methods_table'),

            'mo_auth_enable_role_based_2fa' => $form_values['mo_auth_two_factor_enable_role_based_2fa'] === 1,
            'mo_auth_role_based_2fa_roles' => self::getRoleBased2faRoles($form_values),

            'mo_auth_enable_domain_based_2fa' => $form_values['mo_auth_two_factor_invoke_2fa_depending_upon_domain'] === 1,
            'mo_auth_domain_based_2fa_domains' => preg_replace('/\s+/', '', $form_values['mo_auth_domain_based_2fa_domains']),
            'mo_2fa_domain_and_role_rule' => $form_values['mo_2fa_rule_for_domain'],

            'mo_auth_use_only_2nd_factor' => $form_values['mo_auth_two_factor_instead_password'] === 1,

            'mo_auth_enable_login_with_email' => $form_values['mo_auth_two_factor_enable_login_with_email'] === 1,
            'mo_auth_enable_login_with_phone' => $form_values['mo_auth_two_factor_enable_login_with_phone'] === 1,
            'mo_auth_override_login_labels' => $form_values['mo_auth_two_factor_override_login_labels'] === 1,
            'mo_auth_username_title' => $form_values['mo_auth_two_factor_username_title'],
            'mo_auth_username_description' => $form_values['mo_auth_two_factor_username_description'],

            'mo_auth_enable_trusted_IPs' => $form_values['mo_auth_two_factor_invoke_2fa_depending_upon_IP'] === 1,
            'mo_auth_trusted_IP_address' => preg_replace('/\s+/', '', $form_values['mo_auth_two_factor_trusted_IP']),

            'mo_auth_enable_custom_kba_questions' => $form_values['mo_auth_enable_custom_kba_questions'] === 1,
            'mo_auth_custom_kba_set_1' => self::processKBAQuestions($form_values['mo_auth_enable_custom_kba_set_1']),
            'mo_auth_custom_kba_set_2' => self::processKBAQuestions($form_values['mo_auth_enable_custom_kba_set_2']),

            'mo_auth_redirect_user_after_login' => $form_values['mo_auth_redirect_user_after_login'],
            'mo_auth_google_auth_app_name' => urlencode($form_values['mo_auth_two_factor_google_authenticator_app_name']),

            'mo_auth_custom_organization_name' => urlencode($form_values['mo_auth_custom_organization_name']),

            'mo_auth_enable_2fa_for_password_reset' => $form_values['mo_auth_enable_2fa_for_password_reset'] === 1,
            'mo_auth_enable_backdoor' => $form_values['mo_auth_enable_backdoor'] === 1,

            'allow_end_users_to_decide' => $form_values['allow_end_users_to_decide'] === 1,

            'auto_fetch_phone_number' => $form_values['auto_fetch_phone_number'] === 1,
            'phone_number_field_machine_name' => trim($phone_number_field_machine_name),
            'auto_fetch_phone_number_country_code' => $form_values['auto_fetch_phone_number_country_code'],
            'mo_auth_rba' => $form_values['mo_auth_rba'],
            'mo_auth_rba_duration' => (int)$form_values['rba_duration'],
            'rba_allowed_devices' => (int)$form_values['rba_allowed_devices'],
        );

        $utilities->miniOrange_set_get_configurations($variables_and_values, 'SET');
        //drupal_flush_all_caches(); //TODO: Remove this after 3.08 release
        \Drupal::messenger()->addStatus(t("Login settings updated."));
    }


    /**
     * Process role based 2FA
     * @param $form_values
     * @return string
     */
    function getRoleBased2faRoles($form_values)
    {
          $mo_role_based_2fa_roles = [];
          $table_values  = $form_values['mo_auth_role_based_2fa_table'];
          foreach ($table_values as $key => $value) {
              if($value['checkbox'] == 1) {
                  $mo_role_based_2fa_roles[$key] = $value['2fa_methods'];
              }
          }
          return !empty($mo_role_based_2fa_roles) ? json_encode($mo_role_based_2fa_roles) : '';
    }

    /**
     * Process KBA questions before saving
     * @param $questions
     * @return string
     */
    function processKBAQuestions($questions)
    {
        $mo_kba_questions = trim($questions);
        $mo_kba_questions = rtrim($mo_kba_questions, ";");
        return $mo_kba_questions;
    }
}
