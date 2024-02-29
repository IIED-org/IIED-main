<?php

/**
 * @file
 * Contains support form for miniOrange 2FA Login Module.
 */
namespace Drupal\miniorange_2fa\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Render\Markup;
use Drupal\Core\Form\FormStateInterface;
use Drupal\miniorange_2fa\MoAuthUtilities;
use Drupal\miniorange_2fa\MoAuthConstants;

/* Showing Licensing form info. */

class MoAuthLicensing extends FormBase
{
    public function getFormId()
    {
        return 'miniorange_2fa_licensing';
    }

    public function buildForm(array $form, FormStateInterface $form_state)
    {

        $user_email = \Drupal::config('miniorange_2fa.settings')->get('mo_auth_customer_admin_email');

        $mo_Premium_Plan_URL = MoAuthConstants::$PORTAL_URL . MoAuthConstants::$PREMIUM_PLAN;

        $form['markup_library'] = array(
            '#attached' => array(
                'library' => array(
                    "miniorange_2fa/miniorange_2fa.admin",
                    "miniorange_2fa/miniorange_2fa.main",
                    "core/drupal.dialog.ajax",

                )
            ),
        );

        $form['header_top_style_2'] = array(
            '#markup' => t('<div class="mo_2fa_table_layout_1"><div class="mo_2fa_table_layout mo_container_second_factor">
                            <br><h2>&emsp; Upgrade Plans</h2><hr>')
        );

        $rows = [[
            Markup::create(t('<a href="#plans">Plans & Feature Comparison</a>')),
            Markup::create(t('<a href="#steps_upgrade_premium">Upgrade Steps</a>')),
            Markup::create(t('<a href="#faq">FAQs</a>')),
            Markup::create(t('<a href="#payment_method">Payment Methods</a>')),
            Markup::create(t('<a href="#refund_policy">Refund Policy</a>')),
        ]];

        $form['miniorange_2fa_client_topnav'] = array(
            '#type' => 'table',
            '#responsive' => TRUE,
            '#rows' => $rows,
            '#sticky' => TRUE,
            '#attributes' => ['class' => ['mo_topnav_bar']],
        );

        $form['markup_free'] = array(
            '#markup' => t('<div id="contact"><br><h2>If you want to test any of our paid modules, please contact us at <a href="mailto:drupalsupport@xecurify.com">drupalsupport@xecurify.com</a></h2></div>')
        );

        $free_plan_text = $premium_plan_text =  'You are on this plan';
        $license_type = \Drupal::config('miniorange_2fa.settings')->get('mo_auth_2fa_license_type');

        if (isset($license_type) && $license_type != 'DEMO' ) {
          $free_plan_text = 'Thank you for upgrading';
        } else {
          $premium_plan_text = 'Upgrade Now';
        }

        $features = [
            [Markup::create(t('<h1>FREE</h1><h6>For 1 User - Forever</h6>')), Markup::create(t('<h1>PREMIUM</h1><h6>For 1+ Users</h6>')),],
            [Markup::create(t('<p class="mo_2fa_pricing_rate"><sup>$</sup> 0</p>')), Markup::create(t('
                <p class="mo_2fa_pricing_rate" id="premium_price"><sup>$</sup>65/year</p>
                <div class="container-inline"><label for="instances_premium">Users</label>&nbsp;&nbsp;
                <select id="instances_premium" name="instances" onchange="Instance_Pricing(this.value)">
                    <option value="10">Upto 10</option>
                    <option value="20">Upto 20</option>
                    <option value="30">Upto 30</option>
                    <option value="40">Upto 40</option>
                    <option value="50">Upto 50</option>
                    <option value="60">Upto 60</option>
                    <option value="70">Upto 70</option>
                    <option value="80">Upto 80</option>
                    <option value="90">Upto 90</option>
                    <option value="100">Upto 100</option>
                    <option value="150">Upto 150</option>
                    <option value="200">Upto 200</option>
                    <option value="250">Upto 250</option>
                    <option value="300">Upto 300</option>
                    <option value="350">Upto 350</option>
                    <option value="400">Upto 400</option>
                    <option value="450">Upto 450</option>
                    <option value="500">Upto 500</option>
                    <option value="600">Upto 600</option>
                    <option value="700">Upto 700</option>
                    <option value="800">Upto 800</option>
                    <option value="900">Upto 900</option>
                    <option value="1000">Upto 1000</option>
                    <option value="2000">More than 1000</option>
                </select></div>')),],
            [Markup::create(t('<a class="button" disabled>'.$free_plan_text.'</a><br><br>')), Markup::create(t('<a class="button" href="' . $mo_Premium_Plan_URL . '" target="_blank">'.$premium_plan_text.'</a> <br><br>')),],
            [Markup::create(t('<h4>FEATURE LIST</h4>')), Markup::create(t('<h4>FEATURE LIST</h4>')),],
            [
                //Features of Free version

                Markup::create(t(
                    '<div class="mo_2fa_feature_list">
                            <ul class="checkmark">
                                <li>All Authentication Methods*</li>
                                <li>Supports all the languages</li>
                                <li>Backup security questions(KBA)</li>
                                <li>Add your Own security questions</li>
                                <li>Customize number of KBA to be asked while login</li>
                                <li>Login with Email address</li>
                                <li>Login with Phone number</li>
                                <li>Override login form username title and description</li>
                                <li>Change app name in Google Authenticator app</li>
                                <li>Multiple device support <small>(configure Authenticator on multiple devices)</small></li>
                                <li>Custom Email Templates</li>
                                <li>Custom SMS Templates</li>
                                <li>Custom OTP length and validity</li>
                                <li>Backdoor URL (in case you get locked out)</li>
                                <li>Custom hook to override messages/text in the login flow</li>
                            </ul>
                           </div>'
                )),

                //Features of Premium version
                Markup::create(t(
                    '<br><h3>ALL THE FEATURES OF FREE </h3><h2> + </h2> <br>
                           <div class="mo_2fa_feature_list">
                            <ul class="checkmark">
                                <li>Support for Headless/decoupled Architecture</li>
                                <li>Passwordless login</li>
                                <li>Enable Role based 2FA</li>
                                <li>Mandate unique 2FA method for each role</li>
                                <li>Enforce 2FA registration for users</li>
                                <li>Select 2FA methods to be configured by end users</li>
                                <li>2FA over Password Reset</li>
                                <li>IP specific 2FA (Trusted IP Address)</li>
                                <li>Remember Device</li>
                                <li>End to End 2FA Integration</li>
                                <li>Basic Email Support Available</li>
                                <li>Opt-in/Opt-out from 2FA</li>
                                <li>Premium GoToMeeting Support Available</li>
                               <br><br><br><br><br>
                            </ul>
                           </div>'
                )),
            ]
        ];


        $form['miniorange_oauth_login_feature_list'] = array(
            '#type' => 'table',
            '#responsive' => TRUE,
            '#rows' => $features,
            '#size' => 3,
            '#attributes' => array(
                'class' => ['mo_upgrade_plans_features mo_2fa_feature_table'],
                'id' => 'plans'
            ),
        );

        $rows = [
            [Markup::create(t('<b>1.</b> Click on the <strong>Upgrade Now</strong> button of the Premium Plan and you will be redirected to miniOrange login console.</li>')), Markup::create(t('<b>4.</b> On successful payment completion, goto <a href=" ' . MoAuthUtilities::get_mo_tab_url('CUSTOMER_SETUP') . ' ">Register/Login</a> tab'))],
            [Markup::create(t('<b>2.</b> Enter your username and password with which you have created an account with us. After that you will be redirected to payment page.')), Markup::create(t('<b>5.</b> Click on the <strong>Check License</strong> button.'))],
            [Markup::create(t('<b>3.</b> Enter your card details and proceed for payment. Upon successful payment, your licence will be updated to the premium version.')), Markup::create(t('<b>6.</b> Clicking the Check Licence button will fetch the latest licence details and all the premium features of the module will be unlocked.'))],
        ];

        $form['miniorange_2fa_how_to_upgrade'] = [
            '#markup' => t('<div id = "steps_upgrade_premium"><br><br><br><br>'),
        ];

        $form['miniorange_2fa_how_to_upgrade_table'] = array(
            '#type' => 'table',
            '#responsive' => TRUE,
            '#header' => [
                'how_to_upgrade' => [
                    'data' => 'HOW TO UPGRADE TO THE PREMIUM VERSION MODULE',
                    'colspan' => 2,
                ],
            ],
            '#rows' => $rows,
            '#attributes' => ['style' => 'border:groove', 'class' => ['mo_how_to_upgrade']],
            '#suffix' => '</div><br>'
        );

        $form['miniorange_2fa_faq_header'] = array(
            '#markup' => t('<div><h3 style="text-align: center; margin:3%;">FAQs</h3><hr></div>'),
            '#attributes' => ['class' => ['mo_container']]
        );

        $form['miniorange_2fa_faq1_title'] = array(
            '#type' => 'details',
            '#title' => t('Are the licenses perpetual?'),
            '#attributes' => array('style' => 'padding:0; margin:2%',),
            '#prefix' => '<div class="container-inline">'
        );
        $form['miniorange_2fa_faq1_title']['faq1_description'] = array(
            '#markup' => t('<div>The 2FA module is subscription based module. Your module will continue function unless - a) Your transaction limit is exhausted; in that case you would need to purchase more SMS/ Email/ IVR transactions as required. b) Your module licence expires; when you purchase the module your licence is active for 1 year after the date of activation. After that, you would have to renew your licence and maintainance plan annually. We also have discounts should you opt to renew for more than 1 year at a time.</div>'),
        );

        $form['miniorange_2fa_faq2_title'] = array(
            '#type' => 'details',
            '#title' => t('What is the refund policy?'),
            '#attributes' => array('style' => 'padding:0; margin:2%')
        );

        $form['miniorange_2fa_faq2_title']['faq2_description'] = array(
            '#markup' => t('<div>At miniOrange, we want to ensure you are 100% happy with your purchase. If the premium plugin you purchased is not working as advertised and you\'ve attempted to resolve any issues with our support team, which couldn\'t get resolved. We will refund the whole amount within 10 days of the purchase. Please email us at drupalsupport@xecurify.com for any queries regarding the return policy or contact us here.
                 The plugin licenses are perpetual and the Support Plan includes 12 months of maintenance (support and version updates). You can renew maintenance after 12 months at 50% of the current license cost.</div></div>'),
        );

        $form['miniorange_2fa_faq3_title'] = array(
            '#type' => 'details',
            '#title' => t('Does miniOrange offer technical support?'),
            '#attributes' => array('style' => 'padding:0; margin:2%')
        );
        $form['miniorange_2fa_faq3_title']['faq3_description'] = array(
            '#markup' => t('<div>Yes, we provide 24*7 support for all and any issues you might face while using the module, which includes technical support from our developers. You can get prioritized support based on the Support Plan you have opted. You can check out the different Support Plans from <a href="https://www.miniorange.com/support-plans" target="_blank"> here</a>.</div>'),
        );

        $form['miniorange_2fa_faq4_title'] = array(
            '#type' => 'details',
            '#title' => t('Does miniOrange store any user data?'),
            '#attributes' => array('style' => 'padding:0 ; margin:2%')
        );

        $form['miniorange_2fa_faq4_title']['faq4_description'] = array(
            '#markup' => t('<div>miniOrange does not store any user data from the 2FA module, except for the email address used for authentication purposes. This email address is securely stored and used exclusively for authentication purposes. We will never sell, rent, or disclose your data to marketers or any third parties. You also have the right to request the deletion of all data linked to your account. Please refer to the <a href="https://plugins.miniorange.com/drupal-end-user-license-agreement" target="_blank"> EULA</a> for further details.</div>'),
        );


        $form['miniorange_2fa_faq_btn'] = array(
            '#markup' => t('<div class="mo_2fa_text_align" id="faq"><a href="https://faq.miniorange.com/kb/drupal/two-factor-authentication-drupal/" class="button" target="_blank">More FAQs</a></div>')
        );

        $form['miniorange_2fa_payment_methods_header'] = array(
            '#markup' => t('<div id="payment_method"><h3 style="text-align: center;">Payment Methods</h3><hr></div>'),
        );

        $form['miniorange_2fa_payment_methods'] = [
            '#type' => 'table',
            '#responsive' => TRUE,
            '#attributes' => ['class' => ['miniorange_2fa_types', 'mo_container']],
        ];

        $data = ['title', 'description'];
        foreach ($data as $data_shown) {
            $row = $this->miniorange_2fa_payment_method($data_shown);
            $form['miniorange_2fa_payment_methods'][$data_shown] = $row;
        }


        $disclaimer = t('
            <div class="mo_container" id="refund_policy">
               <h3 style="text-align: center;">REFUND POLICY</h3><hr><br><br>
               <p>We will refund the whole amount if the premium version of the module does not work as advertised and our support team fails to resolve the issue. The refund policy is valid within 10 days of purchase.</p>
               <p>Note that this policy does not cover the following cases:<br>

                  1. Change of mind or change in requirements after purchase.<br>
                  2. Infrastructure issues not allowing the functionality to work.<br><br>

                  Please email us at <a href="mailto:drupalsupport@xecurify.com">drupalsupport@xecurify.com</a> for any queries regarding the return policy.
                  <br><br>
               </p>

            <div style="margin: 0 4% 3% 1.5%; text-align: justify;">

            <h3 style="font-size:small">* Few authentication methods need credits like SMS transactions, email transactions etc.</h3>
                 If you have any doubts regarding the upgrade plans, you can mail us at <a href="mailto:drupalsupport@xecurify.com">drupalsupport@xecurify.com</a> or submit query using the contact us</a>.
         </div>
        </div>');

        $form['header']['#markup'] = $disclaimer;

        return $form;
    }

    private function miniorange_2fa_payment_method($data)
    {
        global $base_url;
        $moModulePath = MoAuthUtilities::moGetModulePath();
        if ($data == 'title') {
            $row['mo_2fa_payment_method1'] = [
                '#markup' => t('<div class="mo_2fa_text_align"><img src="' . $base_url . '/' . $moModulePath . '/includes/images/card_payment.png" width="120" ><h4></h4></div>'),
            ];

            $row['mo_2fa_payment_method2'] = [
                '#markup' => t('<div class="mo_2fa_text_align"><img src="' . $base_url . '/' . $moModulePath . '/includes/images/bank_transfer.png" width="150" ><h4><h4></div>'),
            ];
        } else {
            $row['mo_2fa_payment_method1_desc'] = [
                '#markup' => t('<div class="mo_2fa_text_justify"><p>If the payment is made through Credit Card/International Debit Card, the license will be created automatically once the payment is completed.</p></div>'),
            ];

            $row['mo_2fa_payment_method2_desc'] = [
                '#markup' => t('<div class="mo_2fa_text_justify"><p>If you want to use bank transfer for the payment then contact us at <a href="mailto:drupalsupport@xecurify.com">drupalsupport@xecurify.com</a> so that we can provide you the bank details.</p></div>'),
            ];
        }
        return $row;
    }

    public function submitForm(array &$form, FormStateInterface $form_state)
    {
    }
}
