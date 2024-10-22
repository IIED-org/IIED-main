<?php

namespace Drupal\cloudflare;

use Cloudflare\API\Adapter\Guzzle;
use Cloudflare\API\Auth\APIKey;
use Cloudflare\API\Auth\APIToken;
use Cloudflare\API\Endpoints\Zones;
use Drupal\cloudflare\Exception\ComposerDependencyException;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use GuzzleHttp\Exception\ClientException;
use Psr\Log\LoggerInterface;

/**
 * Zone methods for CloudFlare.
 */
class Zone implements CloudFlareZoneInterface {
  use StringTranslationTrait;

  /**
   * The settings configuration.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Tracks rate limits associated with CloudFlare Api.
   *
   * @var \Drupal\cloudflare\CloudFlareStateInterface
   */
  protected $state;

  /**
   * ZoneApi object for interfacing with CloudFlare Php Sdk.
   *
   * @var \Cloudflare\API\Endpoints\Zones
   */
  protected $zoneApi;

  /**
   * The current cloudflare ZoneId.
   *
   * @var string
   */
  protected $zone;

  /**
   * The zone name to filter for.
   *
   * @var string
   */
  protected $zoneName;

  /**
   * Flag for valid credentials.
   *
   * @var bool
   */
  protected $validCredentials;

  /**
   * Checks that the composer dependencies for CloudFlare are met.
   *
   * @var \Drupal\cloudflare\CloudFlareComposerDependenciesCheckInterface
   */
  protected $cloudFlareComposerDependenciesCheck;

  /**
   * The cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * {@inheritdoc}
   */
  public static function create(ConfigFactoryInterface $config_factory, LoggerInterface $logger, CacheBackendInterface $cache, CloudFlareStateInterface $state, CloudFlareComposerDependenciesCheckInterface $check_interface) {
    $config = $config_factory->get('cloudflare.settings');
    $auth_using = $config->get('auth_using');
    if ($auth_using === 'key') {
      $api_key = $config->get('apikey');
      $email = $config->get('email');
    }
    elseif ($auth_using === 'token') {
      $token = $api_key = $config->get('api_token');
    }

    // If someone has not correctly installed composer here is where we need to
    // handle it to prevent PHP error.
    try {
      $check_interface->assert();
      if ($auth_using === 'key') {
        $key = new APIKey($email, $api_key);
      }
      elseif ($auth_using === 'token') {
        $key = new APIToken($token);
      }

      $adapter = new Guzzle($key);
      $zoneapi = new Zones($adapter);
    }
    catch (ComposerDependencyException $e) {
      $zoneapi = NULL;
    }

    return new static(
      $config_factory,
      $logger,
      $cache,
      $state,
      $zoneapi,
      $check_interface
    );
  }

  /**
   * Zone constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend.
   * @param \Drupal\cloudflare\CloudFlareStateInterface $state
   *   Tracks rate limits associated with CloudFlare Api.
   * @param \Cloudflare\API\Endpoints\Zones|null $zone_api
   *   ZoneApi instance for accessing api.
   * @param \Drupal\cloudflare\CloudFlareComposerDependenciesCheckInterface $check_interface
   *   Checks that composer dependencies are met.
   */
  public function __construct(ConfigFactoryInterface $config_factory, LoggerInterface $logger, CacheBackendInterface $cache, CloudFlareStateInterface $state, $zone_api, CloudFlareComposerDependenciesCheckInterface $check_interface) {
    $this->config = $config_factory->get('cloudflare.settings');
    $this->logger = $logger;
    $this->cache = $cache;
    $this->state = $state;
    $this->zoneApi = $zone_api;
    $this->zone = $this->config->get('zone');
    $this->zoneName = $this->config->get('zone_name');
    $this->validCredentials = $this->config->get('valid_credentials');
    $this->cloudFlareComposerDependenciesCheck = $check_interface;
  }

  /**
   * {@inheritdoc}
   */
  public function listZones() {
    $this->cloudFlareComposerDependenciesCheck->assert();
    $zones = [];
    $cid = 'cloudflare_zone_listing';
    try {

      if ($cached = $this->cache->get($cid)) {
        return $cached->data;
      }

      else {
        $next_page = 0;
        $total_pages = 1;

        while ($next_page < $total_pages) {
          $this->zoneName = !empty($this->zoneName) ? $this->zoneName : '';
          $results = $this->zoneApi->listZones($this->zoneName, '', $next_page);
          $zones = array_merge($zones, $results->result);
          $this->state->incrementApiRateCount();
          $total_pages = $results->result_info->total_pages;
          $next_page = $results->result_info->page;
        }

        $this->cache->set($cid, $zones, time() + 60 * 5, ['cloudflare_zone']);
      }
    }
    catch (ClientException $e) {
      $this->logger->error($e->getMessage());
      throw $e;
    }
    return $zones;
  }

  /**
   * {@inheritdoc}
   */
  public static function assertValidToken($apitoken, CloudFlareComposerDependenciesCheckInterface $composer_dependency_check, CloudFlareStateInterface $state, $zone_name = '') {
    $composer_dependency_check->assert();
    $key = new APIToken($apitoken);
    $adapter = new Guzzle($key);
    $zone_api_direct = new Zones($adapter);

    try {
      $zone_api_direct->listZones($zone_name);
    }
    finally {
      $state->incrementApiRateCount();
    }

  }

  /**
   * {@inheritdoc}
   */
  public static function assertValidCredentials($apikey, $email, CloudFlareComposerDependenciesCheckInterface $composer_dependency_check, CloudFlareStateInterface $state) {
    $composer_dependency_check->assert();
    $key = new APIKey($email, $apikey);
    $adapter = new Guzzle($key);
    $zone_api_direct = new Zones($adapter);

    try {
      $zone_api_direct->listZones();
    }
    finally {
      $state->incrementApiRateCount();
    }
  }

}
