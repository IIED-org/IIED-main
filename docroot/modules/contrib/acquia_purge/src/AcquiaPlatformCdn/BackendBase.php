<?php

namespace Drupal\acquia_purge\AcquiaPlatformCdn;

use Drupal\acquia_purge\AcquiaCloud\PlatformInfoInterface;
use Drupal\acquia_purge\Plugin\Purge\Purger\DebuggerAwareTrait;
use Drupal\acquia_purge\Plugin\Purge\Purger\DebuggerInterface;
use Drupal\purge\Logger\LoggerChannelPartInterface;
use GuzzleHttp\ClientInterface;

/**
 * Provides a backend for the Platform CDN purger.
 */
abstract class BackendBase implements BackendInterface {
  use DebuggerAwareTrait;

  /**
   * The Guzzle HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * Acquia Platform CDN configuration settings.
   *
   * Associative array with arbitrary settings coming from:
   * \Drupal\acquia_purge\AcquiaCloud\PlatformInfoInterface::getPlatformCdnConfiguration.
   *
   * @var array
   */
  protected $config;

  /**
   * Information object interfacing with the Acquia platform.
   *
   * @var \Drupal\acquia_purge\AcquiaCloud\PlatformInfoInterface
   */
  protected $platformInfo;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $config, PlatformInfoInterface $acquia_purge_platforminfo, LoggerChannelPartInterface $logger, DebuggerInterface $debugger, ClientInterface $http_client) {
    $this->platformInfo = $acquia_purge_platforminfo;
    $this->httpClient = $http_client;
    $this->logger = $logger;
    $this->config = $config;
    $this->setDebugger($debugger);
  }

  /**
   * {@inheritdoc}
   */
  public static function getTemporaryRuntimeError() {
    if ($error = \Drupal::cache()->get('acquia_purge_cdn_runtime_error')) {
      return $error->data;
    }
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public static function platformInfo(PlatformInfoInterface $set = NULL) {
    static $platforminfo;
    if (is_null($platforminfo) && (!is_null($set))) {
      $platforminfo = $set;
    }
    elseif (is_null($platforminfo)) {
      throw new \RuntimeException("BackendBase::platformInfo can't deliver requested instance.");
    }
    return $platforminfo;
  }

  /**
   * {@inheritdoc}
   */
  public static function setTemporaryRuntimeError($message, $timeout = 300) {
    \Drupal::cache()->set(
      'acquia_purge_cdn_runtime_error',
      $message,
      time() + $timeout
    );
  }

}
