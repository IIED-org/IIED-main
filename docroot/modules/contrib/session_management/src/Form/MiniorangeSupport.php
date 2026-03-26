<?php

namespace Drupal\session_management\Form;

use Drupal\Core\Url;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\session_management\ContactAPI\MiniorangeContact;
use Drupal\Core\Render\Markup;
use Drupal\session_management\Utilities;

/**
 * Support / Contact us form.
 */
class MiniorangeSupport extends FormBase
{



  /**
   * {@inheritdoc}
   */
  public function getFormId()
  {
    return 'miniorange-support';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state)
  {

    global $base_url;
    $module_path = $base_url . '/' . \Drupal::service('extension.list.module')->getPath("session_management");
    $module_data = \Drupal::service('extension.list.module')->getExtensionInfo('session_management');
    $features = [
      [
        'title' => 'Limit Concurrent User Sessions',
        'description' => 'Restrict the number of simultaneous logins per user to prevent multiple active sessions across devices.',
      ],
      [
        'title' => 'Auto Logout After Inactivity',
        'description' => 'Automatically logs users out after a defined period of inactivity to enhance security.',
      ],
      [
        'title' => 'Terminate Sessions on Password Change',
        'description' => 'Instantly ends all active sessions when a user updates their password, ensuring session security.',
      ],
      [
        'title' => 'Active Session Control for Users',
        'description' => 'Allows users to view and manage their currently active sessions, terminating any suspicious ones.',
      ],
      [
        'title' => 'IP-Based Session Restriction',
        'description' => 'Enforce login restrictions based on whitelisted or blacklisted IP addresses for enhanced access control.',
      ],
      [
        'title' => 'Session Monitoring and History',
        'description' => 'Admins can review detailed session logs and history for all users, including login time, IP, and device info.',
      ],

    ];

    $related_products = [
      [
        'title' => 'OAuth Client',
        'description' => 'OAuth/OpenID Connect Client SSO (OAuth 2.0) module allows users residing at the OAuth Provider\'s side to login to your Drupal site. The module syncs with all OAuth/OpenID providers that conform to OAuth 2.0 or OpenID Connect 1.0 standards.',
        'image' => 'OAuth-Client.webp',
        'link' => 'https://plugins.miniorange.com/drupal-sso-oauth-openid-single-sign-on'
      ],
      [
        'title' => 'SAML SP',
        'description' => 'miniOrange provides Drupal SAML SP as a Single Sign-On solution that allows you to login to your Drupal site using SAML 2.0 compliant Identity Provider credentials. You can easily configure the Identity Provider with your Drupal site by simply providing a metadata URL or metadata file.',
        'image' => 'SAML-SP.webp',
        'link' => 'https://plugins.miniorange.com/drupal-saml-single-sign-on-sso',
      ],
      [
        'title' => 'Two Factor Authentication',
        'description' => 'Second-Factor Authentication (TFA) adds a second layer of security with an option to configure truly Passwordless Login. You can configure the module to send an OTP to your preferred mode of communication like phone/email, integrate with TOTP Apps like Google Authenticator or configure hardware token 2FA method.',
        'image' => '2FA.webp',
        'link' => 'https://plugins.miniorange.com/drupal-two-factor-authentication-2fa'
      ],
      [
        'title' => 'OTP Verification',
        'description' => 'Drupal OTP Verification module verifies Email Address/Mobile Number of users by sending verification code(OTP) during registration. It eliminates the possibility of a user registering with invalid personal details (phone number or email) on the Drupal site.',
        'image' => 'OTP.webp',
        'link' => 'https://plugins.miniorange.com/drupal-otp-verification'
      ],
      [
        'title' => 'REST API Authentication',
        'description' => 'Drupal REST & JSON API Authentication module secures your Drupal site APIs against unauthorized access by enforcing different authentication methods including Basic Authentication, API Key Authentication, JWT Authentication, Third-Party Provider Authentication, etc.',
        'image' => 'REST-API.webp',
        'link' => 'https://plugins.miniorange.com/drupal-rest-api-authentication'
      ],
      [
        'title' => 'Website Security Pro',
        'description' => 'The Website Security Pro module safeguards your Drupal site with enterprise-grade security. It protects against brute force and DDoS attacks, enforces strong passwords, monitors and blacklists suspicious IPs, and secures login and registration forms. Designed to block hackers and malware, it ensures your site stays secure, stable, and reliable.',
        'image' => 'Web-Security.webp',
        'link' => 'https://plugins.miniorange.com/drupal-web-security-pro',
      ],
    ];

    $form['markup_library'] = array(
      '#attached' => array(
        'library' => array(
          "session_management/session_management.mo_session"
        )
      )
    );


    // Premium Features Section
    $form['premium_features_section'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['section-container'],
      ],
    ];

    $form['premium_features_section']['plan_info'] = [
      '#type' => 'markup',
      '#prefix' => '<div class="section-title">',
    ];

    $form['premium_features_section']['plan_info']['module_info'] = [
      '#type' => 'markup',
      '#markup' => '<span>Current Plan:<br/><h3>Free Version</h3></span>',
      '#attributes' => ['class' => ['module-info']],
    ];

    // Right side upgrade button
    $form['premium_features_section']['plan_info']['upgrade_button'] = [
      '#type' => 'link',
      '#title' => $this->t('Upgrade plan'),
      '#url' => Url::fromUri('https://portal.miniorange.com/initializepayment?requestOrigin=drupal_session_management_premium_plan'),
      '#attributes' => [
        'class' => ['button', 'button--primary'],
        'target' => '_blank',
        'rel' => 'noopener noreferrer',
      ],
      '#suffix' => '</div>',
    ];

    $form['premium_features_section']['plan_info']['hr'] = [
      '#type' => 'markup',
      '#markup' => '<hr>',
    ];

    $form['premium_features_section']['title'] = [
      '#type' => 'markup',
      '#markup' => '<h3>Premium Features</h3>',
      '#prefix' => '<div class="section-title">',
    ];

    $form['premium_features_section']['upgrade-button'] = [
      '#type' => 'link',
      '#title' => $this->t('View all features'),
      '#url' => \Drupal\Core\Url::fromUri('https://plugins.miniorange.com/drupal-session-management#features'),
      '#attributes' => [
        'target' => '_blank',
        'rel' => 'noopener noreferrer',
        'class' => 'view-all-features-button',
      ],
      '#suffix' => '</div>',
    ];

    // Features Grid Container
    $form['premium_features_section']['features_grid'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['features-grid'],
      ],
    ];

    foreach ($features as $index => $feature) {
      $form['premium_features_section']['features_grid']['feature_' . $index] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['feature-box']
        ],
      ];

      $form['premium_features_section']['features_grid']['feature_' . $index]['content'] = [
        '#type' => 'markup',
        '#markup' => '<h5>' . $feature['title'] . '</h5>' . $feature['description'],
      ];
    }

    // Related Products Section
    $form['related_products_section'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['section-container'],
      ],
    ];

    $form['related_products_section']['title'] = [
      '#type' => 'markup',
      '#markup' => '<h3>Related Products</h3>',
      '#prefix' => '<div class="section-title">',
    ];

    $form['related_products_section']['upgrade-button'] = [
      '#type' => 'link',
      '#title' => $this->t('View all products'),
      '#url' => \Drupal\Core\Url::fromUri('https://plugins.miniorange.com/drupal'),
      '#attributes' => [
        'class' => 'view-all-features-button',
        'target' => '_blank',
        'rel' => 'noopener noreferrer',
      ],
      '#suffix' => '</div>',
    ];

    // Features Grid Container
    $form['related_products_section']['features_grid'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['features-grid']
      ],
    ];

    // Feature boxes with images

    foreach ($related_products as $index => $product) {
      $form['related_products_section']['features_grid']['feature_' . $index] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['feature-box']
        ],
      ];

      $form['related_products_section']['features_grid']['feature_' . $index]['title'] = [
        '#type' => 'markup',
        '#markup' => '<h5>' . $product['title'] . '</h5><hr>',
      ];

      // Image
      $form['related_products_section']['features_grid']['feature_' . $index]['image'] = [
        '#type' => 'markup',
        '#markup' => $this->t('<div><img class="feature-image" src=":module_path/includes/images/:image" alt=":title"></div>', [
          ':module_path' => $module_path,
          ':image' => $product['image'],
          ':title' => $product['title'],
        ]),
      ];

      // Title and Description
      $form['related_products_section']['features_grid']['feature_' . $index]['content'] = [
        '#type' => 'markup',
        '#markup' => '<span class="product-description">' . $product['description'] . '</span>',
      ];

      // View Details Button
      $form['related_products_section']['features_grid']['feature_' . $index]['button'] = [
        '#type' => 'link',
        '#title' => $this->t('View details'),
        '#url' => \Drupal\Core\Url::fromUri($product['link']),
        '#attributes' => [
          'class' => 'view-all-features-button',
          'target' => '_blank',
          'rel' => 'noopener noreferrer',
          'style' => 'margin-top: 8px; width: fit-content;',
        ],
      ];
    }


    $form['markup_how_to_upgrade'] = [
      '#type' => 'markup',
      '#markup' => '<h3 class="mo_saml_text_center"><br>How to Upgrade to Licensed Version Module</h3>'
    ];

    $form['upgrade_steps'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Upgrade steps'),
      '#attributes' => [
        'class' => ['upgrade-steps-fieldset'],
      ],
    ];

    $form['upgrade_steps']['step_1'] = [
      '#type' => 'markup',
      '#markup' => '<div class="upgrade-step"><strong>1.</strong> Click on <a href="https://portal.miniorange.com/initializepayment?requestOrigin=drupal_session_management_premium_plan" target="_blank" rel="noopener noreferrer">Upgrade plan</a> button for required licensed plan and you will be redirected to miniOrange login console.</div>',
    ];

    $form['upgrade_steps']['step_2'] = [
      '#type' => 'markup',
      '#markup' => '<div class="upgrade-step"><strong>2.</strong> Enter your username and password with which you have created an account with us. After that you will be redirected to payment page.</div>',
    ];

    $form['upgrade_steps']['step_3'] = [
      '#type' => 'markup',
      '#markup' => '<div class="upgrade-step"><strong>3.</strong> Enter your card details and proceed for payment. On successful payment completion, the Licensed version module(s) will be available to download.</div>',
    ];

    $form['upgrade_steps']['step_4'] = [
      '#type' => 'markup',
      '#markup' => '<div class="upgrade-step"><strong>4.</strong> Download the licensed module(s) from Module Releases and Downloads section.</div>',
    ];

    $form['upgrade_steps']['step_5'] = [
      '#type' => 'markup',
      '#markup' => '<div class="upgrade-step"><strong>5.</strong> Uninstall and then delete the free version of the module from your Drupal site.</div>',
    ];

    $form['upgrade_steps']['step_6'] = [
      '#type' => 'markup',
      '#markup' => '<div class="upgrade-step"><strong>6.</strong> Now install the downloaded licensed version of the module.</div>',
    ];

    $form['upgrade_steps']['step_7'] = [
      '#type' => 'markup',
      '#markup' => '<div class="upgrade-step"><strong>7.</strong> Clear Drupal Cache from <a href="' . $base_url . '/admin/config/development/performance" target="_blank" rel="noopener noreferrer">here</a>.</div>',
    ];

    $form['upgrade_steps']['step_8'] = [
      '#type' => 'markup',
      '#markup' => '<div class="upgrade-step"><strong>8.</strong> After enabling the licensed version of the module, login using the account you have registered with us.</div>',
    ];

    //Utilities::addSupportButton( $form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {}
}
