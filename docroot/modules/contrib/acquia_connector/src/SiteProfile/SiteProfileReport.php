<?php

namespace Drupal\acquia_connector\SiteProfile;

use Drupal\acquia_connector\Subscription;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Backend Service for generating a Site Profile Report.
 */
class SiteProfileReport {

  use MessengerTrait;
  use StringTranslationTrait;

  /**
   * The Acquia client.
   *
   * @var \Drupal\acquia_connector\Client\ClientFactory
   */
  protected $clientFactory;

  /**
   * Drupal State Service.
   *
   * @var \Drupal\Core\State\State
   */
  protected $state;

  /**
   * Site Profile Serivce.
   *
   * @var \Drupal\acquia_connector\SiteProfile\SiteProfile
   */
  protected $siteProfile;

  /**
   * Module Handler Service.
   *
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  protected $moduleHandler;

  /**
   * Creates a Site Profile Report.
   *
   * @param \Drupal\acquia_connector\Subscription $subscription
   *   The Acquia subscription service.
   * @param \Drupal\Core\State\StateInterface $state
   *   Drupal State Service.
   * @param \Drupal\acquia_connector\SiteProfile\SiteProfile $site_profile
   *   Site Profile Service.
   * @param \Drupal\Core\Extension\ModuleHandler $module_handler
   *   Drupal Module Handler.
   */
  public function __construct(Subscription $subscription, StateInterface $state, SiteProfile $site_profile, ModuleHandler $module_handler) {
    $this->subscription = $subscription;
    $this->state = $state;
    $this->siteProfile = $site_profile;
    $this->moduleHandler = $module_handler;
  }

  /**
   * Gather site profile information about this site.
   *
   * @param string $method
   *   Optional identifier for the method initiating request.
   *   Values could be 'cron' or 'menu callback' or 'drush'.
   *
   * @return array
   *   An associative array keyed by types of information.
   */
  public function get($method = '') {
    $settings = $this->subscription->getSettings();
    // Fetch config from setting object, as the event can alter it.
    $config = $settings->getConfig();

    // Start with an empty subscription.
    $subscription = [];

    // Force a refresh of subscription data.
    $subscription = $this->subscription->getSubscription(TRUE);

    // Get the Drupal version.
    $drupal_version = $this->siteProfile->getVersionInfo();

    $stored = $this->dataStoreGet(['platform']);
    if (!empty($stored['platform'])) {
      $platform = $stored['platform'];
    }
    else {
      $platform = $this->siteProfile->getPlatform();
    }

    $acquia_hosted = $this->siteProfile->checkAcquiaHosted();
    $environment = $config->get('spi.site_environment');
    $env_detection_enabled = $config->get('spi.env_detection_enabled');

    $spi = [
      // Used in HMAC validation.
      'rpc_version'        => ACQUIA_CONNECTOR_ACQUIA_SPI_DATA_VERSION,
      // Used in Fix it now feature.
      'spi_data_version'   => ACQUIA_CONNECTOR_ACQUIA_SPI_DATA_VERSION,
      'site_key'           => sha1($settings->getSecretKey()),
      'site_uuid'          => $this->siteProfile->getIdFromSub($subscription),
      'env_changed_action' => $config->get('spi.environment_changed_action'),
      'acquia_hosted'      => $acquia_hosted,
      'name'               => $this->siteProfile->getSiteName($subscription['subscription_name']),
      'machine_name'       => $this->siteProfile->getMachineName($subscription),
      'environment'        => $environment,
      'modules'            => $this->siteProfile->getModules(),
      'platform'           => $platform,
      'quantum'            => $this->siteProfile->getQuantum(),
      'system_status'      => $this->siteProfile->getSystemStatus(),
      'failed_logins'      => $this->siteProfile->getFailedLogins(),
      '404s'               => $this->siteProfile->get404s(),
      'watchdog_size'      => $this->siteProfile->getWatchdogSize(),
      'watchdog_data'      => $this->siteProfile->getWatchdogData(),
      'last_nodes'         => $this->siteProfile->getLastNodes(),
      'last_users'         => $this->siteProfile->getLastUsers(),
      'extra_files'        => $this->siteProfile->checkFilesPresent(),
      'ssl_login'          => $this->siteProfile->checkLogin(),
      'distribution'       => $drupal_version['distribution'] ?? '',
      'base_version'       => $drupal_version['base_version'],
      'build_data'         => $drupal_version,
      'roles'              => Json::encode(user_roles()),
      'uid_0_present'      => $this->siteProfile->getUidZeroIsPresent(),
    ];

    $scheme = parse_url($config->get('spi.server'), PHP_URL_SCHEME);
    $via_ssl = (in_array('ssl', stream_get_transports(), TRUE) && $scheme == 'https') ? TRUE : FALSE;
    if ($config->get('spi.ssl_override')) {
      $via_ssl = TRUE;
    }

    $additional_data = [];

    $security_review = SecurityReviewController::create(\Drupal::getContainer());
    $security_review_results = $security_review->runSecurityReview();

    // It's worth sending along node access control information even if there
    // are no modules implementing it - some alerts are simpler if we know we
    // don't have to worry about node access.
    // Check for node grants modules.

    $node_grants_modules = [];
    if (method_exists($this->moduleHandler, 'invokeAllWith')) {
      $this->moduleHandler->invokeAllWith('node_grants', function (callable $hook, string $module) use (&$node_grants_modules) {
        // There is minimal overhead since the hook is not invoked.
        $node_grants_modules[] = $module;
      });
      $additional_data['node_grants_modules'] = $node_grants_modules;
    }
    else {
      //@phpstan-ignore-next-line
      $additional_data['node_grants_modules'] = $this->moduleHandler->getImplementations('node_grants');
    }

    // Check for node access modules.
    $node_access_modules = [];
    if (method_exists($this->moduleHandler, 'invokeAllWith')) {
      $this->moduleHandler->invokeAllWith('node_access', function (callable $hook, string $module) use (&$node_access_modules) {
        // There is minimal overhead since the hook is not invoked.
        $node_access_modules[] = $module;
      });
      $additional_data['node_access_modules'] = $node_grants_modules;
    }
    else {
      //@phpstan-ignore-next-line
      $additional_data['node_access_modules'] = $this->moduleHandler->getImplementations('node_access');
    }

    if (!empty($security_review_results)) {
      $additional_data['security_review'] = $security_review_results['security_review'];
    }

    // Collect all user-contributed custom tests that pass validation.
    $custom_tests_results = $this->testCollect();
    if (!empty($custom_tests_results)) {
      $additional_data['custom_tests'] = $custom_tests_results;
    }

    $spi_data = $this->moduleHandler->invokeAll('acquia_connector_spi_get');
    if (!empty($spi_data)) {
      foreach ($spi_data as $name => $data) {
        if (is_string($name) && is_array($data)) {
          $additional_data[$name] = $data;
        }
      }
    }

    include_once "core/includes/update.inc";
    $additional_data['pending_updates'] = (bool) update_get_update_list();

    if (!empty($additional_data)) {
      // JSON encode this additional data.
      $spi['additional_data'] = json_encode($additional_data);
    }

    if (!empty($method)) {
      $spi['send_method'] = $method;
    }

    if (!$via_ssl) {
      return $spi;
    }
    else {
      $variablesController = VariablesController::create(\Drupal::getContainer());
      // Values returned only over SSL.
      $spi_ssl = [
        'system_vars' => $variablesController->getVariablesData(),
        'settings_ra' => $this->siteProfile->getSettingsPermissions(),
        'admin_count' => $this->siteProfile->getAdminCount(),
        'admin_name' => $this->siteProfile->getSuperName(),
      ];

      return array_merge($spi, $spi_ssl);
    }
  }

  /**
   * Get SPI data out of local storage.
   *
   * @param array $keys
   *   Array of keys to extract data for.
   *
   * @return array
   *   Stored data or false if no data is retrievable from storage.
   */
  public function dataStoreGet(array $keys) {
    $store = [];
    foreach ($keys as $key) {
      if ($cache = \Drupal::cache()->get('acquia.spi.' . $key)) {
        if (!empty($cache->data)) {
          $store[$key] = $cache->data;
        }
      }
    }
    return $store;
  }

  /**
   * Put SPI data in local storage.
   *
   * @param array $data
   *   Keyed array of data to store.
   * @param int $expire
   *   Expire time or null to use default of 1 day.
   */
  public function dataStoreSet(array $data, $expire = NULL) {
    if (is_null($expire)) {
      $expire = \Drupal::time()->getRequestTime() + (60 * 60 * 24);
    }
    foreach ($data as $key => $value) {
      \Drupal::cache()->set('acquia.spi.' . $key, $value, $expire);
    }
  }

  /**
   * Collects all user-contributed test results that pass validation.
   *
   * @return array
   *   An associative array containing properly formatted user-contributed
   *   tests.
   */
  private function testCollect() {
    $custom_data = [];

    // Collect all custom data provided by hook_insight_custom_data().
    $collections = $this->moduleHandler->invokeAll('acquia_connector_spi_test');

    foreach ($collections as $test_name => $test_params) {
      $status = TestStatusController::create(\Drupal::getContainer());
      $result = $status->testValidate([$test_name => $test_params]);

      if ($result['result']) {
        $custom_data[$test_name] = $test_params;
      }
    }

    return $custom_data;
  }

}
