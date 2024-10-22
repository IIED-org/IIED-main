<?php

namespace Drupal\cloudflare\Form;

// cspell:ignore e-mail
use Cloudflare\API\Adapter\ResponseException;
use Drupal\cloudflare\CloudFlareComposerDependenciesCheckInterface;
use Drupal\cloudflare\CloudFlareStateInterface;
use Drupal\cloudflare\CloudFlareZoneInterface;
use Drupal\Component\Utility\EmailValidator;
use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Egulias\EmailValidator\EmailValidator as EguliasEmailValidator;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Settings form for CloudFlare module.
 *
 * @package Drupal\cloudflare\Form
 */
class SettingsForm extends FormBase implements ContainerInjectionInterface {

  // The length of the Api key.
  // The Api will throw a non-descriptive http code: 400 exception if the key
  // length is greater than 37. If the key is invalid but the expected length
  // the Api will return a more informative http code of 403.
  const API_KEY_LENGTH = 37;

  /**
   * Email validator class.
   *
   * @var \Drupal\Component\Utility\EmailValidator|\Egulias\EmailValidator\EmailValidator
   */
  protected $emailValidator;

  /**
   * Wrapper to access the CloudFlare zone api.
   *
   * @var \Drupal\cloudflare\CloudFlareZoneInterface
   */
  protected $zoneApi;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Tracks rate limits associated with CloudFlare API.
   *
   * @var \Drupal\cloudflare\CloudFlareStateInterface
   */
  protected $state;

  /**
   * Checks that the composer dependencies for CloudFlare are met.
   *
   * @var \Drupal\cloudflare\CloudFlareComposerDependenciesCheckInterface
   */
  protected $cloudFlareComposerDependenciesCheck;

  /**
   * Boolean indicates if CloudFlare dependencies have been met.
   *
   * @var bool
   */
  protected $cloudFlareComposerDependenciesMet;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // This is a hack because could not get custom ServiceProvider to work.
    // See: https://www.drupal.org/node/2026959
    $has_zone_mock = $container->has('cloudflare.zonemock');
    $has_composer_mock = $container->has('cloudflare.composer_dependency_checkmock');

    // Drupal\Component\Utility\EmailValidator introduced in 8.7.x. Adding
    // condition here for backward compatibility.
    // @see https://www.drupal.org/i/3038799
    if (class_exists('\Drupal\Component\Utility\EmailValidator')) {
      $email_validator = new EmailValidator();
    }
    else {
      $email_validator = new EguliasEmailValidator();
    }

    return new static(
      $container->get('config.factory'),
      $container->get('cloudflare.state'),
      $has_zone_mock ? $container->get('cloudflare.zonemock') : $container->get('cloudflare.zone'),
      $email_validator,
      $has_composer_mock ? $container->get('cloudflare.composer_dependency_checkmock') : $container->get('cloudflare.composer_dependency_check')
    );
  }

  /**
   * Constructs a new CloudFlareAdminSettingsForm.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\cloudflare\CloudFlareStateInterface $state
   *   Tracks rate limits associated with CloudFlare API.
   * @param \Drupal\cloudflare\CloudFlareZoneInterface $zone_api
   *   ZoneApi instance for accessing api.
   * @param \Drupal\Component\Utility\EmailValidator|\Egulias\EmailValidator\EmailValidator $email_validator
   *   The email validator.
   * @param \Drupal\cloudflare\CloudFlareComposerDependenciesCheckInterface $check_interface
   *   Checks if composer dependencies are met.
   */
  public function __construct(ConfigFactoryInterface $config_factory, CloudFlareStateInterface $state, CloudFlareZoneInterface $zone_api, $email_validator, CloudFlareComposerDependenciesCheckInterface $check_interface) {
    $this->configFactory = $config_factory;
    $this->state = $state;
    $this->zoneApi = $zone_api;
    $this->emailValidator = $email_validator;
    $this->cloudFlareComposerDependenciesCheck = $check_interface;
    $this->cloudFlareComposerDependenciesMet = $check_interface->check();
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'cloudflare.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cloudflare_admin';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->configFactory->get('cloudflare.settings');
    $form = array_merge($form, $this->buildApiCredentialsSection($config));
    $form = array_merge($form, $this->buildZoneSelectSection($config));
    $form = array_merge($form, $this->buildGeneralConfig($config));

    // Form elements are being disabled after parent::buildForm because:
    // 1: parent::buildForm creates the submit button
    // 2: we want to disable the submit button since dependencies unmet.
    if (!$this->cloudFlareComposerDependenciesMet) {
      $this->messenger()->addError((CloudFlareComposerDependenciesCheckInterface::ERROR_MESSAGE));

      $form['api_credentials_fieldset']['apikey']['#disabled'] = TRUE;
      $form['api_credentials_fieldset']['email']['#disabled'] = TRUE;
      $form['cloudflare_config']['client_ip_restore_enabled']['#disabled'] = TRUE;
      $form['cloudflare_config']['bypass_host']['#disabled'] = TRUE;
      $form['actions']['submit']['#disabled'] = TRUE;
    }

    return $form;
  }

  /**
   * Builds credentials section for inclusion in the settings form.
   *
   * @param \Drupal\Core\Config\Config $config
   *   The readonly configuration.
   *
   * @return array
   *   Form Api render array with credentials section.
   */
  protected function buildApiCredentialsSection(Config $config) {
    $section = [];

    $section['api_credentials_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('API Credentials'),
    ];
    $section['api_credentials_fieldset']['auth_using'] = [
      '#type' => 'radios',
      '#title' => $this->t('Authenticate using'),
      '#default_value' => $config->get('auth_using'),
      '#options' => [
        'key' => $this->t('Key and Email'),
        'token' => $this->t('Token'),
      ],
    ];
    $section['api_credentials_fieldset']['api_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('CloudFlare API Token (required when using token)'),
      '#description' => $this->t('Your API Token. Get it at <a href="https://www.cloudflare.com/a/account/my-account">cloudflare.com/a/account/my-account</a>.'),
      '#default_value' => $config->get('api_token'),
    ];
    $section['api_credentials_fieldset']['apikey'] = [
      '#type' => 'textfield',
      '#title' => $this->t('CloudFlare API Key (required when using key)'),
      '#description' => $this->t('Your API key. Get it at <a href="https://www.cloudflare.com/a/account/my-account">cloudflare.com/a/account/my-account</a>.'),
      '#default_value' => $config->get('apikey'),
    ];
    $section['api_credentials_fieldset']['email'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Account e-mail address (required when using key)'),
      '#default_value' => $config->get('email'),
    ];

    return $section;
  }

  /**
   * Builds zone selection section for inclusion in the settings form.
   *
   * @param \Drupal\Core\Config\Config $config
   *   The readonly configuration.
   *
   * @return array
   *   Form Api render array with selection section.
   */
  protected function buildZoneSelectSection(Config $config) {
    $section = [];

    $section['zone_selection_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Current Zone Selection'),
      '#weight' => 0,
    ];

    $section['zone_selection_fieldset']['zone_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Limit by zone name'),
      '#default_value' => $config->get('zone_name'),
    ];

    $zone_ids = $config->get('zone_id') ?? [];
    if (is_array($zone_ids) && !empty($zone_ids)) {
      // Get the zones.
      $zones = [];
      if ($config->get('valid_credentials') === TRUE && $this->cloudFlareComposerDependenciesMet) {
        try {
          $zones = $this->zoneApi->listZones();
        }
        catch (RequestException $e) {
          $this->messenger()->addError($this->t('Unable to connect to CloudFlare in order to validate credentials. Please try again later. Error message: @message', ['@message' => $e->getMessage()]));
        }
      }

      // Find this zone_id.
      foreach ($zones as $zone) {
        foreach ($zone_ids as $zone_id) {
          if ($zone->id == $zone_id) {
            $zone_text[$zone_id] = $zone->name;
            break;
          }
        }
      }
      $selected_zones = [];
      foreach ($zone_ids as $zone_id) {
        $selected_zones[] = $zone_text[$zone_id];
      }

      $default_value = implode(PHP_EOL, $selected_zones);
      $description = $this->t('To change the current selected zones click the "Next" button below.');
    }
    else {
      $default_value = $this->t('No zone selected');
      $description = $this->t('No zone has been selected. Enter valid API credentials then click the "Next" button below.');
    }

    $section['zone_selection_fieldset']['zone'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Selected Zones'),
      '#description' => $description,
      '#default_value' => $default_value,
      '#disabled' => TRUE,
    ];

    return $section;
  }

  /**
   * Builds general config section for inclusion in the settings form.
   *
   * @param \Drupal\Core\Config\Config $config
   *   The readonly configuration.
   *
   * @return array
   *   Form API render array with selection section.
   */
  protected function buildGeneralConfig(Config $config) {
    $section = [];

    $section['cloudflare_config'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Configuration'),
    ];

    $section['cloudflare_config']['client_ip_restore_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Restore Client Ip Address'),
      '#description' => $this->t('CloudFlare operates as a reverse proxy and replaces the client IP address. This setting will restore it.<br /> Read more <a href="https://support.cloudflare.com/hc/en-us/articles/200170986-How-does-CloudFlare-handle-HTTP-Request-headers-">here</a>.'),
      '#default_value' => $config->get('client_ip_restore_enabled'),
    ];

    $section['cloudflare_config']['remote_addr_validate'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Validate remote IP address'),
      '#description' => $this->t('<strong>WARNING: disabling this can have security related consequences. Leave enabled if unsure.</strong> <br /> When "Restore Client Ip Address" above is enabled, this module will validate that the request is originating from <a href="https://www.cloudflare.com/ips/">Cloudflare IPs</a> before replacing it with the IP address provided in <a href="https://developers.cloudflare.com/fundamentals/get-started/reference/http-request-headers/#cf-connecting-ip">CF-Connecting-IP</a> header. For example, when your Drupal is running in Kubernetes, this remote IP might be of your ingress controller and not originating from Cloudflare, so you want to disable this validation.'),
      '#default_value' => $config->get('remote_addr_validate'),
    ];

    $section['cloudflare_config']['bypass_host'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Host to Bypass CloudFlare'),
      '#description' => $this->t('Optional: Specify a host (no http/https) used for authenticated users to edit the site that bypasses CloudFlare. <br /> This will help suppress log warnings regarding requests bypassing CloudFlare.'),
      '#default_value' => $config->get('bypass_host'),
    ];

    return $section;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $auth_using = trim($form_state->getValue('auth_using'));
    if ($auth_using === 'key') {
      // Get the email address and apikey.
      $email = trim($form_state->getValue('email'));
      $apikey = trim($form_state->getValue('apikey'));
      // Validate the email address.
      if (!$this->emailValidator->isValid($email)) {
        $form_state->setErrorByName('email', $this->t('Please enter a valid e-mail address.'));
        return;
      }

      // This check seems superfluous.  However, the Api only returns a http 400
      // code. This proactive check gives us more information.
      $is_api_key_valid = strlen($apikey) == $this::API_KEY_LENGTH;
      $is_api_key_alpha_numeric = ctype_alnum($apikey);
      $is_api_key_lower_case = !(preg_match('/[A-Z]/', $apikey));

      if (!$is_api_key_valid) {
        $form_state->setErrorByName('apikey', $this->t('Invalid Api Key: Key should be 37 chars long.'));
        return;
      }

      if (!$is_api_key_alpha_numeric) {
        $form_state->setErrorByName('apikey', $this->t('Invalid Api Key: Key can only contain alphanumeric characters.'));
        return;
      }

      if (!$is_api_key_lower_case) {
        $form_state->setErrorByName('apikey', $this->t('Invalid Api Key: Key can only contain lowercase or numerical characters.'));
        return;
      }

      try {
        // Confirm that the credentials can authenticate with the CloudFlareApi.
        $this->zoneApi->assertValidCredentials($apikey, $email, $this->cloudFlareComposerDependenciesCheck, $this->state);
      }
      catch (ClientException $e) {
        if ($e->getResponse()->getStatusCode() === 403) {
          $form_state->setErrorByName('apikey', $e->getMessage());
          return;
        }
        $form_state->setErrorByName('apikey', $this->t("An unknown error has occurred when attempting to connect to CloudFlare's API: @error", ['@error' => $e->getMessage()]));
        return;
      }
      catch (RequestException $e) {
        $form_state->setErrorByName('apikey', $this->t('Unable to connect to CloudFlare in order to validate credentials. Request error: @error', ['@error' => $e->getMessage()]));
        return;
      }
      catch (ResponseException $e) {
        $form_state->setErrorByName('apikey', $this->t('Unable to connect to validate Cloudflare credentials. Request error: @error', ['@error' => $e->getMessage()]));
        return;
      }
    }
    elseif ($auth_using === 'token') {
      // Get the email address and apikey.
      $token = trim($form_state->getValue('api_token'));
      try {
        if (empty($token)) {
          throw new \Exception('CloudFlare API Token field is empty!');
        }
        // Confirm that the credentials can authenticate with the CloudFlareApi.
        $this->zoneApi->assertValidToken($token, $this->cloudFlareComposerDependenciesCheck, $this->state);
      }
      catch (ClientException $e) {
        if ($e->getResponse()->getStatusCode() === 403) {
          $form_state->setErrorByName('api_token', $e->getMessage());
          return;
        }
        $form_state->setErrorByName('api_token', $this->t("An unknown error has occurred when attempting to connect to CloudFlare's API") . $e->getMessage());
        return;
      }
      catch (RequestException $e) {
        $message = $this->t('Unable to connect to CloudFlare in order to validate credentials.');
        $form_state->setErrorByName('api_token', $message);
        return;
      }
      catch (\Exception $e) {
        $message = $this->t('Please enter a Cloudflare API Token to proceed.');
        $form_state->setErrorByName('api_token', $message);
        return;
      }
    }

    // Validate the bypass host.
    $bypass_host = trim($form_state->getValue('bypass_host'));
    if (!empty($bypass_host)) {
      // Validate the bypass host does not begin with http.
      if (strpos($bypass_host, 'http') > -1) {
        $form_state->setErrorByName('bypass_host', $this->t('Please enter a host without http/https'));
        return;
      }

      // Validate the host domain.
      try {
        Url::fromUri("http://$bypass_host");
      }
      catch (\InvalidArgumentException $e) {
        $form_state->setErrorByName('bypass_host', $this->t('You have entered an invalid host.'));
        return;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $api_key = trim($form_state->getValue('apikey'));
    $email = trim($form_state->getValue('email'));
    $token = trim($form_state->getValue('api_token'));
    $auth_using = trim($form_state->getValue('auth_using'));
    $zone_name = trim($form_state->getValue('zone_name'));

    $bypass_host = trim(rtrim($form_state->getValue('bypass_host'), "/"));
    $client_ip_restore_enabled = $form_state->getValue('client_ip_restore_enabled');
    $remote_addr_validate = $form_state->getValue('remote_addr_validate');

    $config = $this->configFactory->getEditable('cloudflare.settings');
    $config
      ->set('api_token', $token)
      ->set('auth_using', $auth_using)
      ->set('zone_name', $zone_name)
      ->set('apikey', $api_key)
      ->set('email', $email)
      ->set('valid_credentials', TRUE)
      ->set('bypass_host', $bypass_host)
      ->set('remote_addr_validate', $remote_addr_validate)
      ->set('client_ip_restore_enabled', $client_ip_restore_enabled);
    $config->save();
  }

}
