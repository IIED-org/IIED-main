<?php

namespace Drupal\tfa\Plugin\TfaLogin;

use Drupal\Core\Config\Config;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\encrypt\EncryptionProfileManagerInterface;
use Drupal\encrypt\EncryptServiceInterface;
use Drupal\tfa\Plugin\TfaBasePlugin;
use Drupal\tfa\Plugin\TfaLoginInterface;
use Drupal\tfa\Plugin\TfaValidationInterface;
use Drupal\user\UserDataInterface;

/**
 * Trusted browser validation class.
 *
 * @TfaLogin(
 *   id = "tfa_trusted_browser",
 *   label = @Translation("TFA Trusted Browser"),
 *   description = @Translation("TFA Trusted Browser Plugin"),
 *   setupPluginId = "tfa_trusted_browser_setup",
 * )
 */
class TfaTrustedBrowser extends TfaBasePlugin implements TfaLoginInterface, TfaValidationInterface {
  use StringTranslationTrait;

  /**
   * Trust browser.
   *
   * @var bool
   */
  protected $trustBrowser;

  /**
   * Is cookie allowed in subdomains.
   *
   * @var bool
   */
  protected $allowSubdomains;

  /**
   * The cookie name.
   *
   * @var string
   */
  protected $cookieName;

  /**
   * Cookie expiration time.
   *
   * @var string
   */
  protected $expiration;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, UserDataInterface $user_data, EncryptionProfileManagerInterface $encryption_profile_manager, EncryptServiceInterface $encrypt_service) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $user_data, $encryption_profile_manager, $encrypt_service);
    $plugin_settings = \Drupal::config('tfa.settings')->get('login_plugin_settings');
    $settings = $plugin_settings['tfa_trusted_browser'] ?? [];
    // Expiration defaults to 30 days.
    $settings = array_replace([
      'cookie_allow_subdomains' => TRUE,
      'cookie_expiration' => 30,
      'cookie_name' => 'tfa-trusted-browser',
    ], $settings);
    $this->allowSubdomains = $settings['cookie_allow_subdomains'];
    $this->expiration = $settings['cookie_expiration'];
    $this->cookieName = $settings['cookie_name'];
    $this->userData = $user_data;
  }

  /**
   * {@inheritdoc}
   */
  public function loginAllowed() {
    if (isset($_COOKIE[$this->cookieName]) && $this->trustedBrowser($_COOKIE[$this->cookieName]) !== FALSE) {
      $this->setUsed($_COOKIE[$this->cookieName]);
      return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getForm(array $form, FormStateInterface $form_state) {
    $form['trust_browser'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Remember this browser for @time days?', ['@time' => $this->expiration]),
      '#description' => $this->t('Not recommended if you are on a public or shared computer.'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array $form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array $form, FormStateInterface &$form_state) {
    $trust_browser = $form_state->getValue('trust_browser');
    if (!empty($trust_browser)) {
      $this->setTrusted($this->generateBrowserId(), $this->getAgent());
    }
  }

  /**
   * The configuration form for this validation plugin.
   *
   * @param \Drupal\Core\Config\Config $config
   *   Config object for tfa settings.
   * @param array $state
   *   Form state array determines if this form should be shown.
   *
   * @return array
   *   Form array specific for this validation plugin.
   */
  public function buildConfigurationForm(Config $config, array $state = []) {
    $settings_form['cookie_allow_subdomains'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow cookie in subdomains'),
      '#default_value' => $this->allowSubdomains,
      '#description' => $this->t("If set, the cookie will be valid in the same subdomains as core's session cookie, otherwise it is only valid in the exact domain used to log in."),
      '#states' => $state,
    ];
    $settings_form['cookie_expiration'] = [
      '#type' => 'number',
      '#title' => $this->t('Cookie expiration'),
      '#default_value' => $this->expiration,
      '#description' => $this->t('Number of days to remember the trusted browser.'),
      '#min' => 1,
      '#max' => 365,
      '#size' => 2,
      '#states' => $state,
      '#required' => TRUE,
    ];
    $settings_form['cookie_name'] = [
      '#type' => 'value',
      '#title' => $this->t('Cookie name'),
      '#value' => $this->cookieName,
    ];

    return $settings_form;
  }

  /**
   * Finalize the browser setup.
   *
   * @throws \Exception
   */
  public function finalize() {
    if ($this->trustBrowser) {
      $name = $this->getAgent();
      $this->setTrusted($this->generateBrowserId(), $name);
    }
  }

  /**
   * Generate a random value to identify the browser.
   *
   * @return string
   *   Base64 encoded browser id.
   *
   * @throws \Exception
   */
  protected function generateBrowserId() {
    $id = base64_encode(random_bytes(32));
    return strtr($id, ['+' => '-', '/' => '_', '=' => '']);
  }

  /**
   * Store browser value and issue cookie for user.
   *
   * @param string $id
   *   Trusted browser id.
   * @param string $name
   *   The custom browser name.
   */
  protected function setTrusted($id, $name = '') {
    // Currently broken.
    // Store id for account.
    $records = $this->getUserData('tfa', 'tfa_trusted_browser', $this->configuration['uid'], $this->userData) ?: [];
    $request_time = \Drupal::time()->getRequestTime();

    $records[$id] = [
      'created' => $request_time,
      'ip' => \Drupal::request()->getClientIp(),
      'name' => $name,
    ];
    $this->setUserData('tfa', ['tfa_trusted_browser' => $records], $this->configuration['uid'], $this->userData);

    // Issue cookie with ID.
    $cookie_secure = ini_get('session.cookie_secure');
    $expiration = $request_time + $this->expiration * 86400;
    $domain = $this->allowSubdomains ? ini_get('session.cookie_domain') : '';
    setcookie($this->cookieName, $id, $expiration, base_path(), $domain, !empty($cookie_secure), TRUE);

    // @todo use services defined in module instead this procedural way.
    \Drupal::logger('tfa')->info('Set trusted browser for user UID @uid, browser @name', [
      '@name' => empty($name) ? $this->getAgent() : $name,
      '@uid' => $this->uid,
    ]);
  }

  /**
   * Updated browser last used time.
   *
   * @param int $id
   *   Internal browser ID to update.
   */
  protected function setUsed($id) {
    $result = $this->getUserData('tfa', 'tfa_trusted_browser', $this->uid, $this->userData);
    $result[$id]['last_used'] = \Drupal::time()->getRequestTime();
    $data = [
      'tfa_trusted_browser' => $result,
    ];
    $this->setUserData('tfa', $data, $this->uid, $this->userData);
  }

  /**
   * Check if browser id matches user's saved browser.
   *
   * @param string $id
   *   The browser ID.
   *
   * @return bool
   *   TRUE if ID exists otherwise FALSE.
   */
  protected function trustedBrowser($id) {
    // Check if $id has been saved for this user.
    $result = $this->getUserData('tfa', 'tfa_trusted_browser', $this->uid, $this->userData);
    if (isset($result[$id])) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Delete users trusted browser.
   *
   * @param string $id
   *   (optional) Id of the browser to be purged.
   *
   * @return bool
   *   TRUE is id found and purged otherwise FALSE.
   */
  protected function deleteTrusted($id = '') {
    $result = $this->getUserData('tfa', 'tfa_trusted_browser', $this->uid, $this->userData);
    if ($id) {
      if (isset($result[$id])) {
        unset($result[$id]);
        $data = [
          'tfa_trusted_browser' => $result,
        ];
        $this->setUserData('tfa', $data, $this->uid, $this->userData);
        return TRUE;
      }
    }
    else {
      $this->deleteUserData('tfa', 'tfa_trusted_browser', $this->uid, $this->userData);
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Get simplified browser name from user agent.
   *
   * @param string $name
   *   Default browser name.
   *
   * @return string
   *   Simplified browser name.
   */
  protected function getAgent($name = '') {
    if (isset($_SERVER['HTTP_USER_AGENT'])) {
      // Match popular user agents.
      $agent = $_SERVER['HTTP_USER_AGENT'];
      if (preg_match("/like\sGecko\)\sChrome\//", $agent)) {
        $name = 'Chrome';
      }
      elseif (strpos($_SERVER['HTTP_USER_AGENT'], 'Firefox') !== FALSE) {
        $name = 'Firefox';
      }
      elseif (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== FALSE) {
        $name = 'Internet Explorer';
      }
      elseif (strpos($_SERVER['HTTP_USER_AGENT'], 'Safari') !== FALSE) {
        $name = 'Safari';
      }
      else {
        // Otherwise filter agent and truncate to column size.
        $name = substr($agent, 0, 255);
      }
    }
    return $name;
  }

  /**
   * {@inheritdoc}
   */
  public function ready() {
    return TRUE;
  }

}
