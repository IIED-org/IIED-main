<?php

namespace Drupal\acquia_purge\AcquiaPlatformCdn;

use Drupal\acquia_purge\AcquiaCloud\PlatformInfoInterface;
use Drupal\acquia_purge\Plugin\Purge\Purger\DebuggerInterface;
use Drupal\purge\Logger\LoggerChannelPartInterface;
use GuzzleHttp\ClientInterface;

/**
 * Provides a backend for the Platform CDN purger.
 */
class BackendFactory {

  /**
   * Backends for Platform CDN vendors.
   *
   * @var string[]
   */
  protected static $backendClasses = [
    'fastly' => FastlyBackend::class,
  ];

  /**
   * Get a instantiated Platform CDN purger backend.
   *
   * @param \Drupal\acquia_purge\AcquiaCloud\PlatformInfoInterface $platforminfo
   *   Information object interfacing with the Acquia platform.
   * @param \Drupal\purge\Logger\LoggerChannelPartInterface $logger
   *   The logger passed to the Platform CDN purger.
   * @param \Drupal\acquia_purge\Plugin\Purge\Purger\DebuggerInterface $debugger
   *   The centralized debugger for Acquia purger plugins.
   * @param \GuzzleHttp\ClientInterface $http_client
   *   An HTTP client that can perform remote requests.
   *
   * @return null|\Drupal\acquia_purge\AcquiaPlatformCdn\BackendInterface
   *   The instantiated backend or NULL in case of failure.
   */
  public static function get(PlatformInfoInterface $platforminfo, LoggerChannelPartInterface $logger, DebuggerInterface $debugger, ClientInterface $http_client) {
    if (!$backend_config = self::getConfig($platforminfo)) {
      return NULL;
    }
    if (!$backend_class = self::getClassFromConfig($backend_config)) {
      return NULL;
    }
    if (!$backend_class::validateConfiguration($backend_config)) {
      return NULL;
    }
    return new $backend_class(
      $backend_config,
      $platforminfo,
      $logger,
      $debugger,
      $http_client
    );
  }

  /**
   * Get the CDN configuration array.
   *
   * @param \Drupal\acquia_purge\AcquiaCloud\PlatformInfoInterface $platforminfo
   *   Information object interfacing with the Acquia platform.
   *
   * @return array|null
   *   NULL when unconfigured, or associative array with arbitrary settings
   *   coming from:
   *   \Drupal\acquia_purge\AcquiaCloud\PlatformInfoInterface::getPlatformCdnConfiguration
   */
  public static function getConfig(PlatformInfoInterface $platforminfo) {
    try {
      return $platforminfo->getPlatformCdnConfiguration();
    }
    catch (\RuntimeException $e) {
      return NULL;
    }
  }

  /**
   * Get the backend class based on platform configuration.
   *
   * @param \Drupal\acquia_purge\AcquiaCloud\PlatformInfoInterface $platforminfo
   *   Information object interfacing with the Acquia platform.
   *
   * @return string|null
   *   Class providing the Platform CDN purger backend, or NULL if unconfigured.
   */
  public static function getClass(PlatformInfoInterface $platforminfo) {
    if ($config = self::getConfig($platforminfo)) {
      return self::getClassFromConfig($config);
    }
    return NULL;
  }

  /**
   * Get the backend class from the Platform CDN configuration array.
   *
   * @param array $config
   *   Acquia Platform CDN configuration settings.
   *
   * @return string|null
   *   Class providing the Platform CDN purger backend, or NULL if unconfigured.
   */
  public static function getClassFromConfig(array $config) {
    if (isset(self::$backendClasses[$config['vendor']])) {
      return self::$backendClasses[$config['vendor']];
    }
    return NULL;
  }

}
