<?php
/**
 * @file
 * Contains Setup Two-Factor page for miniOrange
 *     2FA Login Module.
 */

namespace Drupal\miniorange_2fa\Form;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;
use Drupal\miniorange_2fa\MoAuthConstants;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\miniorange_2fa\MiniorangeUser;
use Drupal\miniorange_2fa\UsersAPIHandler;
use Drupal\miniorange_2fa\MoAuthUtilities;
use Drupal\miniorange_2fa\AuthenticationType;
use Drupal\miniorange_2fa\MiniorangeCustomerProfile;
use Drupal\user\Entity\User;

/**
 * Showing Setup Two-Factor page.
 */
class MoAuthSetupTwoFactor extends FormBase
{
  private MoAuthUtilities $utilities;
  private array $all_2fa_methods;
  private array $sorted_2fa_methods;

  public function __construct() {
    $this->utilities           = new MoAuthUtilities();
    $this->all_2fa_methods     = $this->utilities::get_2fa_methods_for_inline_registration(true);
    $this->sorted_2fa_methods  = $this->utilities::get2FAMethodType($this->all_2fa_methods);
  }

  public function getFormId() {
    return 'miniorange_2fa_setup_two_factor';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    global $base_url;
    $user_id          = \Drupal::currentUser()->id();
    $custom_attribute = $this->utilities::get_users_custom_attribute($user_id);

    $user_email = NULL;
    if (!empty($custom_attribute)) {
      $user_email = $custom_attribute[0]->miniorange_registered_email;
    }

    $customer           = new MiniorangeCustomerProfile();
    $user_api_handler   = new UsersAPIHandler($customer->getCustomerID(), $customer->getAPIKey());
    $miniorange_user    = new MiniorangeUser($customer->getCustomerID(), $user_email, '', '', '');
    $response           = $user_api_handler->get($miniorange_user);
    $configured_methods = $this->utilities::mo_auth_get_configured_methods($custom_attribute);

    $variables_and_values = array(
      'mo_auth_firstuser_id',
      'mo_auth_2fa_Success/Error message',
      'mo_auth_2fa_Success/Error status',
    );

    $mo_db_values = MoAuthUtilities::miniOrange_set_get_configurations($variables_and_values, 'GET');

    /**
     * used in test_otp_over_email, test_otp_over_sms and test_otp_over_sms_and_email forms
     */
    \Drupal::configFactory()->getEditable('miniorange_2fa.settings')->set('txId_Value', 'EMPTY_VALUE')->save();

    $success_error_message = $mo_db_values['mo_auth_2fa_Success/Error message'];
    $success_error_status = $mo_db_values['mo_auth_2fa_Success/Error status'];

    if ($success_error_message != NULL && $success_error_status != NULL) {
      \Drupal::messenger()->addMessage(t('%success_error_message', array('%success_error_message' => $success_error_message)), $success_error_status);
      \Drupal::configFactory()->getEditable('miniorange_2fa.settings')->set('mo_auth_2fa_Success/Error message', NULL)->save();
      \Drupal::configFactory()->getEditable('miniorange_2fa.settings')->set('mo_auth_2fa_Success/Error status', NULL)->save();
    }

    $form['#attached']['library'] = [
      "miniorange_2fa/miniorange_2fa.admin",
      "miniorange_2fa/miniorange_2fa.license",
      "core/drupal.dialog.ajax",
      "miniorange_2fa/miniorange_2fa.show_help_text",
      "miniorange_2fa/miniorange_2fa.country_flag_dropdown",
    ];

    $form['markup_top_2'] = [
      '#markup' => '<div class="mo_2fa_table_layout_1"><div class="mo_2fa_table_layout mo_container_second_factor">'
    ];

    if (!$this->utilities::isCustomerRegistered()) {
      $form['header'] = [
        '#markup' => t('<div class="mo_2fa_register_message"><p>You need to <a href="' . $base_url . '/admin/config/people/miniorange_2fa/customer_setup">Register/Login</a> with miniOrange before using this module.</p></div>')
      ];
    }

    if (isset($mo_db_values['mo_auth_firstuser_id']) && $mo_db_values['mo_auth_firstuser_id'] != $user_id) {
      $firstuser = User::load($mo_db_values['mo_auth_firstuser_id']);
      $username = $firstuser->get('name')->value;
      $form['mo_setup_second_factor']['mo_auth_method'] = array(
        '#markup' => t('<b>Setup Second Factor</b><div><hr><p>You do not have permissions to edit configurations. Only ' . '<b>' . $username . '</b>' . ' can setup second factor from this tab.
                               <br><br>In order to setup 2FA for your account please refer to <a href="'.MoAuthConstants::INLINE_REGISTRATION.'">this</a> guide.</p></div>')
      );
      return $form;
    }

    $form['mo_setup_2fa_table'] = [
      '#type' => 'table',
      '#header' => [
        'current_method' => $this->t('Active Method'),
        'configured_method' => $this->t('Configured Method'),
        'color_code' => $this->t('Colour Code'),
      ],
      '#attributes' => ['class' => ['mo_2fa_setup_table']],
      '#rows' => $this->getMainTableRows($configured_methods, $response),
    ];

    $reader_details = [
      'otp'   => 'OTP (One Time Passcode) based 2FA methods',
      'totp'  => 'TOTP based 2FA methods',
      'other' => 'Other 2FA methods',
    ];

    foreach ($reader_details as $id => $details) {
      $form["{$id}_table_details"] = [
        '#type' => 'details',
        '#open' => TRUE,
        '#title' => $this->t($details),
        '#attributes' => ['class' => ['mo_2fa_details_title_custom_css']],
      ];

      $form["{$id}_table_details"]["{$id}_method_table"] = [
        '#type' => 'table',
        '#header' => self::getTableHeaders(),
        '#rows' => self::getTableRows($response,$configured_methods, $id),
      ];
    }

    return $form;
    }

  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

  public static function getTableHeaders() {
    return [
      'method'  => [ 'data' => t('Method Name'),       'width' => '40%'],
      'devices' => [ 'data' => t('Supported Devices'), 'width' => '20%'],
      'action'  => [ 'data' => t('Action'),            'width' => '20%'],
      'guides'  => [ 'data' => t('Setup Guides'),      'width' => '20%'],
    ];
  }

  public function getMainTableRows($configured_methods, $response) {
    $configured_methods_string = 'None';
    if(!empty($configured_methods)) {
        $configured_methods_string = '<ul>';
        foreach ($configured_methods as $method) {
            $method_name = AuthenticationType::getAuthType($method)['name'];
            $configured_methods_string .= '<li>' . $method_name . '</li>';
        }
        $configured_methods_string .= '</ul>';
    }

    return [
      0 => [
        'current_method' => [
          'data' => [
            '#type' => 'item',
            '#markup' =>  isset($response->authType) ? AuthenticationType::getAuthType($response->authType)['name'] : 'None',
          ]
        ],

        'configured_method' => [
          'data' => [
            '#type' => 'item',
            '#markup' => $configured_methods_string,
          ]
        ],

        'color_code' => [
          'data' => [
            '#type' => 'item',
            '#markup' => '<div><span class="mo2f-color-icon mo2f-active-method"></span> - Active Method</div>
                          <div><span class="mo2f-color-icon mo2f-configured-method"></span> - Configured Method</div>',
          ]
        ]
      ]
    ];
  }

  public function getTableRows($response, $configured_methods, $table_name = '') {
    $current_method =  $this->sorted_2fa_methods[$table_name];
    $table_rows     = [];


    foreach ($current_method as $code => $name) {
      $current_method_details = AuthenticationType::getAuthType($code);
      $users_active_method    = AuthenticationType::$NOT_CONFIGURED;
      $width                  = '50%';
      $class                  = '';
      $configure              = '/'. Url::fromRoute('miniorange_2fa.configure_admin_2fa')->getInternalPath(). '?authMethod=' . $current_method_details['code']; // fromRoute->toString() generating wrong URL on localhost

      if (is_object($response) && isset($response->authType)) {
        $users_active_method = AuthenticationType::getAuthType($response->authType);
        $class               = $this->getClassName($code, $users_active_method,$configured_methods);
      }


      if($current_method_details['code'] == in_array($current_method_details['code'], $this->utilities->mo_TOTP_2fa_mentods()) || $current_method_details['code'] == AuthenticationType::$KBA['code']) {
        $width = '80%';
      }

      $table_rows[$code] = [
        'data' => [
          'method'  => $this->getMethodName($current_method_details),
          'devices' => $this->getSupportedDevices($current_method_details['supported-for']),
          'action'  => $this->getConfigureButton($class, $configure, $width, $code),
          'guides'  => $this->getGuideButtons($current_method_details),
        ],
        'class' => $class,
      ];
    }

    return $table_rows;
  }

  public function getMethodName($current_method_details) {
    return[
      'data' => [
        '#type' => 'item',
        '#markup' => $this->getHelpIcon($current_method_details),
      ]
    ];
  }

  public function getSupportedDevices($supported_devices) {
    $images = '';
    global $base_url;
    $module_path = \Drupal::service('module_handler')->getModule('miniorange_2fa')->getPath();

    foreach ($supported_devices as $device) {
      $images .= '<div class="mo_2fa_column" title="'.$device.'">';
      $images .= '<img src="'.$base_url.'/'.$module_path.'/includes/images/icons/' . $device . '.svg">';
      $images .= '</div>';
    }

    return [
      'data' => [
        '#markup' => '<div class="mo_2fa_row">' . $images . '</div>',
      ],
    ];
  }

  public function getConfigureButton($class, $configure, $width, $code) {
    if (!$this->utilities::isCustomerRegistered() ) {
      return [
        'data' => [
          '#type' => 'item',
          '#markup' => '<span title="Please Register/Login with miniOrange before using this module." class="action-link action-link--small button button--small action-link--icon-cog">Configure</span>',
          '#disabled' => TRUE,
          '#attributes' => [
            'title' => t('Please Register/Login with miniOrange before using this module.'),
            'disabled' => 'disabled',
          ]
        ]
      ];
    }

    $activate_link   = [];
    $configure_link  = [];
    $configure_text  = 'Configure';
    $email_based_2fa = $code == 'EMAIL' || $code == 'OUT OF BAND EMAIL';

    if(!$email_based_2fa) {
      if($class == 'mo_2fa_already_configured') {
        $configure_text = 'Reconfigure';
        $activate_link = [
          'activate' => [
            'title' => t('Activate'),
            'url' => Url::fromRoute('miniorange_2fa.activate_admin_2fa_method', ['method_name' => $code]),
          ]
        ];
      }

      if($class == 'mo_2fa_active_method') {
        $configure_text = 'Reconfigure';
      }

      $configure_link = [
        'configure' => [
          'title' => t($configure_text),
          'url' => Url::fromUserInput($configure),
          'attributes' => [
            'class' => ['use-ajax action-link action-link--small action-link--icon-cog'],
            'data-dialog-type' => 'modal',
            'data-dialog-options' => Json::encode([
              'width' => $width,
            ]),
          ]
        ],
      ];
    } else {
      $activate_link = [
        'activate' => [
          'title' => t('Activate'),
          'attributes' => [
            'title' => 'This method is configured by default. You cannot reconfigure again.',
          ],
          'url' => Url::fromRoute('miniorange_2fa.activate_admin_2fa_method' ,['method_name' => $code]),
        ]
      ];
    }

    return [
      'data' => [
        '#type'  => 'dropbutton',
        '#links' => $activate_link + $configure_link,
        '#dropbutton_type' => 'small',
        '#attributes' => [
          'disabled' => TRUE,
        ]
      ],
    ];
  }

  public function getGuideButtons($current_method_details) {
    $doc_link     = !empty($current_method_details['doc-link']) ? $current_method_details['doc-link'] : Url::fromRoute('miniorange_2fa.empty_guide_links', array('method_code' => $current_method_details['name']))->toString();
    $doc_target   = !empty($current_method_details['doc-link']) ? '_blank' : '';
    $video_link   = !empty($current_method_details['video-link']) ? $current_method_details['video-link'] : Url::fromRoute('miniorange_2fa.empty_guide_links', array('method_code' => $current_method_details['code']))->toString();
    $video_target = !empty($current_method_details['video-link']) ? '_blank' : '';

    return [
      'data' => [
        '#type' => 'item',
        '#markup' => '<a class="button button--small use-ajax" target="'.$doc_target.'" data-dialog-type = "modal"  data-ajax-progress="fullscreen" data-dialog-options="{&quot;width&quot;:&quot;50%&quot;}"  href="'.$doc_link.'">&#128366; Doc</a><a target="'.$video_target.'" class="button button--small use-ajax"  data-dialog-type = "modal"  data-ajax-progress="fullscreen" data-dialog-options="{&quot;width&quot;:&quot;50%&quot;}"  href="'.$video_link.'">&#9654; Video</a>'
      ]
    ];
  }

  public function getClassName($code, $users_active_method,$configured_methods) {
    if($code == $users_active_method['code']) {
      return 'mo_2fa_active_method';
    } elseif (in_array($code, $configured_methods)) {
      return 'mo_2fa_already_configured';
    } else {
      return '';
    }
  }

  public function getHelpIcon($current_method_details) {
    $title = $current_method_details['name'];
    $description = $current_method_details['description'];
    $help_text                  = '<div class="mo-2fa--help--title">'.$title.'</div><div class="mo-2fa--help--content">'. $description. '</div>';
    $help_text_encoded          = htmlspecialchars($help_text, ENT_QUOTES, 'UTF-8');
    return Markup::create($title.'<span role="tooltip" tabindex="0" aria-expanded="false" class="mo-2fa--help js-miniorange-2fa-help miniorange-2fa-help" data-miniorange-2fa-help="'.$help_text_encoded.'"><span aria-hidden="true">?</span></span>');
  }
}
