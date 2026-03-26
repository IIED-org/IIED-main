<?php

namespace Drupal\session_management;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class Utilities
{

  public static function addSupportButton(array &$form, FormStateInterface $form_state)
  {
    $base_url = \Drupal::request()->getSchemeAndHttpHost() . \Drupal::request()->getBasePath();
    $module_path = \Drupal::service('extension.list.module')->getPath('session_management');
    $support_image_url = $base_url . '/' . $module_path . '/includes/images/mo-customer-support.png';
    $form['mo_ldap_auth_customer_support_icon'] = [
      '#markup' => t('<a class="use-ajax mo-bottom-corner" data-dialog-options="{&quot;width&quot;:&quot;55%&quot;}"
data-dialog-type="modal" href="support"><img src="' . $support_image_url . '" alt="support image"></a>'),
    ];
  }

  public static function getPremiumBadge()
  {
    $svg_lock = '<svg width="19" height="22" viewBox="0 0 19 30" fill="none" xmlns="http://www.w3.org/2000/svg">
    <path fill-rule="evenodd" clip-rule="evenodd" d="M1.91486 11.5646H2.34263L2.34999 7.58775C2.43861 5.71192 3.25127 4.03061 4.51089 2.81355L4.52081 2.80363C5.8041 1.56928 7.54684 0.810059 9.4553 0.810059C11.3673 0.810059 13.1148 1.57184 14.3997 2.81355C15.6827 4.05397 16.5027 5.77623 16.5651 7.69461L16.5686 11.5646H16.9957C17.5233 11.5646 18.0048 11.7809 18.3494 12.1258L18.4419 12.2298C18.7334 12.5674 18.9106 13.0044 18.9106 13.4795V23.2749C18.9106 23.8025 18.6943 24.284 18.3494 24.6286C18.0048 24.9735 17.5233 25.1898 16.9957 25.1898H1.91486C1.38728 25.1898 0.905761 24.9735 0.561182 24.6286C0.216282 24.284 0 23.8025 0 23.2749V13.4795C0 12.9564 0.215322 12.4787 0.561182 12.1303C0.905761 11.7809 1.38728 11.5646 1.91486 11.5646ZM9.4553 15.0402C10.163 15.0402 10.7367 15.6139 10.7367 16.3216C10.7367 16.8812 10.3783 17.3563 9.87859 17.5313L10.0958 18.5282L10.7367 21.4663H9.4553H8.17392L8.81477 18.5282L9.03201 17.5313C8.53226 17.3563 8.17392 16.8812 8.17392 16.3216C8.17392 15.6139 8.74759 15.0402 9.4553 15.0402ZM4.63951 11.5646H14.2711V7.73076C14.2231 6.44651 13.6693 5.29471 12.8106 4.46414C11.9419 3.62236 10.7584 3.10854 9.4553 3.10854C8.15313 3.10854 6.9703 3.62172 6.10613 4.45774C5.2522 5.28479 4.70093 6.41867 4.63855 7.68373L4.63951 11.5646Z" fill="#003ECC"/>
    </svg>
    ';
    $url = Url::fromRoute('session_management.licensing_form')->toString();
    return '<a href="' . $url . '" target="_blank" style="border-bottom: none; display: inline-block; vertical-align: middle;" title="This is a premium feature. Click to know more.">' . $svg_lock . '</a>';
  }
}
