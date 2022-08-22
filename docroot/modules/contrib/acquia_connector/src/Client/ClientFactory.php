<?php

namespace Drupal\acquia_connector\Client;

use Drupal\acquia_connector\Settings;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Http\ClientFactory as HttpClientFactory;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Instantiates an Acquia Connector Client object.
 *
 * @see \Acquia\ContentHubClient\ContentHub
 */
class ClientFactory {

  /**
   * The contenthub client object.
   *
   * @var \Drupal\acquia_connector\Client\AcquiaConnectorClient
   */
  protected $client;

  /**
   * Settings object.
   *
   * @var \Drupal\acquia_connector\Settings
   */
  protected $settings;

  /**
   * The module extension list.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $moduleList;

  /**
   * Logger Factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * Acquia Connector Settings Config.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * Guzzle Client.
   *
   * @var \Drupal\Core\Http\ClientFactory
   */
  protected $httpClientFactory;

  /**
   * Drupal Time Service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * ClientManagerFactory constructor.
   *
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   * @param \Drupal\Core\Extension\ModuleExtensionList $module_list
   *   The module extension list.
   * @param \Drupal\Core\Http\ClientFactory $client_factory
   *   The date time service.
   * @param \Drupal\Component\Datetime\TimeInterface $date_time
   *   The date time service.
   */
  public function __construct(LoggerChannelFactoryInterface $logger_factory, ModuleExtensionList $module_list, HttpClientFactory $client_factory, TimeInterface $date_time) {
    $this->loggerFactory = $logger_factory;
    $this->moduleList = $module_list;
    $this->time = $date_time;
    $this->httpClientFactory = $client_factory;
  }

  /**
   * Get the Acquia Cloud Client.
   *
   * @param \Drupal\acquia_connector\Settings $settings
   *   Settings object with credentials.
   *
   * @return \Drupal\acquia_connector\Client\AcquiaConnectorClient
   *   Connector Client.
   */
  public function getClient(Settings $settings) {
    // If the client is cached, return it now.
    if (isset($this->client)) {
      return $this->client;
    }

    $client = $this->httpClientFactory->fromOptions(
      [
        'verify' => (boolean) $settings->getConfig()->get('spi.ssl_verify'),
        'client-user-agent' => $this->getClientUserAgent(),
        'http_errors' => FALSE,
      ]
    );

    $this->client = new AcquiaConnectorClient($client, $settings->getApiUrl(), $this->time->getRequestTime());

    return $this->client;
  }

  /**
   * Returns Client's user agent.
   *
   * @return string
   *   User Agent.
   */
  protected function getClientUserAgent() {
    // Find out the module version in use.
    $module_info = $this->moduleList->getExtensionInfo('acquia_connector');
    $module_version = (isset($module_info['version'])) ? $module_info['version'] : '0.0.0';
    $drupal_version = (isset($module_info['core'])) ? $module_info['core'] : '0.0.0';

    return 'AcquiaConnector/' . $drupal_version . '-' . $module_version;
  }

}
