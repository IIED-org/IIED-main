<?php

namespace Drupal\acquia_connector\EventSubscriber\KernelTerminate;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Crypt;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Site\Settings;
use Drupal\Core\State\StateInterface;
use GuzzleHttp\ClientInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\KernelEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Acquia Telemetry Event Subscriber.
 *
 * This event sends anonymized data to Acquia to help track modules and versions
 * Acquia sites use to ensure module updates don't break customer sites.
 *
 * @package Drupal\acquia_connector\EventSubscriber
 */
class AcquiaTelemetry implements EventSubscriberInterface {

  /**
   * Amplitude API URL.
   *
   * @var string
   * @see https://developers.amplitude.com/#http-api
   */
  protected $apiUrl = 'https://api.amplitude.com/httpapi';

  /**
   * The extension.list.module service.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $moduleList;

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * The config.factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Drupal Time Service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * Constructs a telemetry object.
   *
   * @param \Drupal\Core\Extension\ModuleExtensionList $module_list
   *   The extension.list.module service.
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The HTTP client.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config.factory service.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   */
  public function __construct(ModuleExtensionList $module_list, ClientInterface $http_client, ConfigFactoryInterface $config_factory, StateInterface $state, TimeInterface $time) {
    $this->moduleList = $module_list;
    $this->httpClient = $http_client;
    $this->configFactory = $config_factory;
    $this->state = $state;
    $this->time = $time;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::TERMINATE][] = ['onTerminateResponse'];
    return $events;
  }

  /**
   * Sends Telemetry on a daily basis. This occurs after the response is sent.
   *
   * @param \Symfony\Component\HttpKernel\Event\KernelEvent $event
   *   The event.
   */
  public function onTerminateResponse(KernelEvent $event) {
    $send_timestamp = $this->state->get('acquia_connector.telemetry.timestamp');
    if ($this->time->getCurrentTime() - $send_timestamp > 86400) {
      $this->sendTelemetry("Drupal Module Statistics");
      $this->state->set('acquia_connector.telemetry.timestamp', $this->time->getCurrentTime());
    }
  }

  /**
   * Returns the Amplitude API key.
   *
   * This is not intended to be private. It is typically included in client
   * side code. Fetching data requires an additional API secret.
   *
   * @see https://developers.amplitude.com/#http-api
   *
   * @return string
   *   The Amplitude API key.
   */
  private function getApiKey() {
    return Settings::get('acquia_connector.telemetry.key', 'f32aacddde42ad34f5a3078a621f37a9');
  }

  /**
   * Sends an event to Amplitude.
   *
   * @param array $event
   *   The Amplitude event.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   *
   * @see https://developers.amplitude.com/#http-api
   */
  private function sendEvent(array $event) {
    $this->httpClient->request('POST', $this->apiUrl, [
      'form_params' => [
        'api_key' => $this->getApiKey(),
        'event' => Json::encode($event),
      ],
    ]);
  }

  /**
   * Creates and sends an event to Amplitude.
   *
   * @param string $event_type
   *   The event type. This accepts any string that is not reserved. Reserved
   *   event types include: "[Amplitude] Start Session", "[Amplitude] End
   *   Session", "[Amplitude] Revenue", "[Amplitude] Revenue (Verified)",
   *   "[Amplitude] Revenue (Unverified)", and "[Amplitude] Merged User".
   * @param array $event_properties
   *   (optional) Event properties.
   *
   * @return bool
   *   TRUE if event was successfully sent, otherwise FALSE.
   *
   * @throws \Exception
   *   Thrown if state key acquia_telemetry.loud is TRUE and request fails.
   *
   * @see https://amplitude.zendesk.com/hc/en-us/articles/204771828#keys-for-the-event-argument
   */
  public function sendTelemetry($event_type, array $event_properties = []) {
    $event = $this->createEvent($event_type, $event_properties);

    // Failure to send Telemetry should never cause a user facing error or
    // interrupt a process. Telemetry failure should be graceful and quiet.
    try {
      $this->sendEvent($event);
      return TRUE;
    }
    catch (\Exception $e) {
      if ($this->state->get('acquia_connector.telemetry.loud')) {
        throw new \Exception($e->getMessage(), $e->getCode(), $e);
      }
      return FALSE;
    }
  }

  /**
   * Get an array of information about Lightning extensions.
   *
   * @return array
   *   An array of extension info keyed by the extensions machine name. E.g.,
   *   ['lightning_layout' => ['version' => '8.2.0', 'status' => 'enabled']].
   */
  private function getExtensionInfo() {
    $all_modules = $this->moduleList->getAllAvailableInfo();
    $installed_modules = $this->moduleList->getAllInstalledInfo();
    $extension_info = [];

    foreach ($all_modules as $name => $extension) {
      // Remove all custom modules from reporting.
      if (strpos($this->moduleList->getPath($name), '/custom/') !== FALSE) {
        continue;
      }

      // Tag all core modules in use. If the version matches the core
      // Version, assume it is a core module.
      $core_comparison = [
        $extension['version'],
        $extension['core_version_requirement'],
        \Drupal::VERSION,
      ];
      if (count(array_unique($core_comparison)) === 1) {
        if (array_key_exists($name, $installed_modules)) {
          $extension_info['core'][$name] = 'enabled';
        }
        continue;
      }

      // Version is unset for dev versions. In order to generate reports, we
      // need some value for version, even if it is just the major version.
      $extension_info['contrib'][$name]['version'] = $extension['version'] ?? 'dev';

      // Check if module is installed.
      $extension_info['contrib'][$name]['status'] = array_key_exists($name, $installed_modules) ? 'enabled' : 'disabled';
    }

    return $extension_info;
  }

  /**
   * Creates an Amplitude event.
   *
   * @param string $type
   *   The event type.
   * @param array $properties
   *   The event properties.
   *
   * @return array
   *   An Amplitude event with basic info already populated.
   */
  private function createEvent($type, array $properties) {
    $modules = $this->getExtensionInfo();
    $default_properties = [
      'extensions' => $modules['contrib'],
      'php' => [
        'version' => phpversion(),
      ],
      'drupal' => [
        'version' => \Drupal::VERSION,
        'core_enabled' => $modules['core'],
      ],
    ];

    return [
      'event_type' => $type,
      'user_id' => $this->getUserId(),
      'event_properties' => NestedArray::mergeDeep($default_properties, $properties),
    ];
  }

  /**
   * Gets a unique ID for this application. "User ID" is an Amplitude term.
   *
   * @return string
   *   Returns a hashed site uuid.
   */
  private function getUserId() {
    return Crypt::hashBase64($this->configFactory->get('system.site')->get('uuid'));
  }

  /**
   * Gets an array of all Acquia Drupal extensions.
   *
   * @return array
   *   A flat array of all Acquia Drupal extensions.
   */
  public function getAcquiaExtensionNames() {
    $module_names = array_keys($this->moduleList->getAllAvailableInfo());

    return array_values(array_filter($module_names, function ($name) {
      return $name === 'cohesion' || strpos($name, 'acquia') !== FALSE ||
        strpos($name, 'lightning_') !== FALSE ||
        strpos($name, 'acms') !== FALSE;
    }));
  }

}
