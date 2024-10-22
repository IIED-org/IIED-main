<?php

namespace Drupal\cloudflare;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Restores the true client Ip address.
 *
 * @see https://developers.cloudflare.com/fundamentals/get-started/reference/http-request-headers/
 */
class CloudFlareMiddleware implements HttpKernelInterface {

  use StringTranslationTrait;

  const CLOUDFLARE_RANGE_KEY = 'cloudflare_range_key';
  const CLOUDFLARE_CLIENT_IP_RESTORE_ENABLED = 'client_ip_restore_enabled';
  const CLOUDFLARE_REMOTE_ADDR_VALIDATE = 'remote_addr_validate';
  const CLOUDFLARE_BYPASS_HOST = 'bypass_host';
  const IPV4_ENDPOINTS_URL = 'https://www.cloudflare.com/ips-v4';
  const IPV6_ENDPOINTS_URL = 'https://www.cloudflare.com/ips-v6';

  /**
   * The kernel.
   *
   * @var \Symfony\Component\HttpKernel\HttpKernelInterface
   */
  protected $httpKernel;

  /**
   * Cache backend service.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * The settings configuration.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * The HTTP client to fetch the feed data with.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * TRUE/FALSE if client ip restoration enabled.
   *
   * @var bool
   */
  protected $isClientIpRestoreEnabled;

  /**
   * Validate remote IP address.
   *
   * @var bool
   */
  protected $remoteAddrValidate;

  /**
   * Host that bypasses CloudFlare.
   *
   * @var string
   */
  protected $bypassHost;

  /**
   * Constructs the CloudflareMiddleware object.
   *
   * @param \Symfony\Component\HttpKernel\HttpKernelInterface $http_kernel
   *   The decorated kernel.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Configuration factory.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   Cache.
   * @param \GuzzleHttp\ClientInterface $http_client
   *   HTTP client.
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger.
   */
  public function __construct(HttpKernelInterface $http_kernel, ConfigFactoryInterface $config_factory, CacheBackendInterface $cache, ClientInterface $http_client, LoggerInterface $logger) {
    $this->httpKernel = $http_kernel;
    $this->httpClient = $http_client;
    $this->cache = $cache;
    $this->config = $config_factory->get('cloudflare.settings');
    $this->logger = $logger;
    $this->isClientIpRestoreEnabled = $this->config->get(self::CLOUDFLARE_CLIENT_IP_RESTORE_ENABLED);
    $this->remoteAddrValidate = $this->config->get(self::CLOUDFLARE_REMOTE_ADDR_VALIDATE);
    $this->bypassHost = $this->config->get(self::CLOUDFLARE_BYPASS_HOST);
  }

  /**
   * {@inheritdoc}
   */
  public function handle(Request $request, $type = self::MAIN_REQUEST, bool $catch = TRUE): Response {
    if ($type !== self::MAIN_REQUEST) {
      return $this->httpKernel->handle($request, $type, $catch);
    }

    if (!$this->isClientIpRestoreEnabled) {
      return $this->httpKernel->handle($request, $type, $catch);
    }

    $cf_connecting_ip = $request->server->get('HTTP_CF_CONNECTING_IP', '');
    $has_http_cf_connecting_ip = !empty($cf_connecting_ip);
    $remoteAddrValidate = $this->remoteAddrValidate;
    $has_bypass_host = !empty($this->bypassHost);
    $client_ip = $request->getClientIp();
    $incoming_uri = $request->getHost();
    $request_expected_to_bypass_cloudflare = $has_bypass_host && $this->bypassHost == $incoming_uri;

    if ($request_expected_to_bypass_cloudflare) {
      return $this->httpKernel->handle($request, $type, $catch);
    }

    if (!$has_http_cf_connecting_ip) {
      $message = $this->t("Request came through without being routed through CloudFlare.");
      $this->logger->warning($message);
      return $this->httpKernel->handle($request, $type, $catch);
    }

    $has_ip_already_changed = $client_ip == $cf_connecting_ip;

    // Some environments may make the alteration for us. In which case no
    // action is required.
    if ($has_ip_already_changed) {
      $url_to_settings = Url::fromRoute('cloudflare.admin_settings_form');
      $link_to_settings = $url_to_settings->getInternalPath();
      $message = $this->t('Request has already been updated.  This functionality should be deactivated. Please go <a href="@link_to_settings">here</a> to disable "Restore Client Ip Address".', ['@link_to_settings' => $link_to_settings]);
      $this->logger->warning($message);
      return $this->httpKernel->handle($request, $type, $catch);
    }

    $cloudflare_ipranges = $this->getCloudFlareIpRanges();
    $request_originating_from_cloudflare = IpUtils::checkIp($client_ip, $cloudflare_ipranges);

    if ($remoteAddrValidate && $has_http_cf_connecting_ip && !$request_originating_from_cloudflare) {
      $message = $this->t("Client IP of @client_ip does not match a known CloudFlare IP but there is HTTP_CF_CONNECTING_IP of @cf_connecting_ip.", [
        '@cf_connecting_ip' => $cf_connecting_ip,
        '@client_ip' => $client_ip,
      ]);
      $this->logger->warning($message);
      return $this->httpKernel->handle($request, $type, $catch);
    }

    // As the changed remote address will make it impossible to determine
    // a trusted proxy, we need to make sure we set the right protocol as well.
    // Using incoming request to determine scheme that should be used will not
    // work in configurations where TLS is off-loaded before the server that
    // hosts Drupal, but Cloudflare tells us if original request was secure.
    // @see https://developers.cloudflare.com/fundamentals/get-started/reference/http-request-headers/#cf-visitor
    $cf_visitor = json_decode($request->server->get('HTTP_CF_VISITOR', '{}'), TRUE);
    // Use current request as a fall back.
    $is_secure = $request->isSecure();
    if (!empty($cf_visitor['scheme'])) {
      $is_secure = strtolower($cf_visitor['scheme']) === 'https';
    }
    $request->server->set('HTTPS', $is_secure ? 'on' : 'off');
    $request->server->set('REMOTE_ADDR', $cf_connecting_ip);
    $request->overrideGlobals();

    return $this->httpKernel->handle($request, $type, $catch);
  }

  /**
   * Get a list of cloudflare IP Ranges.
   *
   * @return array
   *   Listing of the CloudFlareIP edge server IP ranges
   */
  public function getCloudFlareIpRanges() {
    if ($cache = $this->cache->get(self::CLOUDFLARE_RANGE_KEY)) {
      return $cache->data;
    }

    try {
      $ipv4_raw_listings = trim((string) $this->httpClient
        ->get(self::IPV4_ENDPOINTS_URL)
        ->getBody());

      $ipv6_raw_listings = trim((string) $this->httpClient
        ->get(self::IPV6_ENDPOINTS_URL)
        ->getBody());

      $iv4_endpoints = explode("\n", $ipv4_raw_listings);
      $iv6_endpoints = explode("\n", $ipv6_raw_listings);
      $cloudflare_ips = array_merge($iv4_endpoints, $iv6_endpoints);
      $cloudflare_ips = array_map('trim', $cloudflare_ips);

      if (count($cloudflare_ips) === 0) {
        $this->logger->error("Unable to get a listing of CloudFlare IPs.");
        return [];
      }
      $this->cache->set(self::CLOUDFLARE_RANGE_KEY, $cloudflare_ips, Cache::PERMANENT);
      return $cloudflare_ips;
    }
    catch (RequestException $exception) {
      $this->logger->error("Unable to get a listing of CloudFlare IPs. " . $exception->getMessage());
      return [];
    }
  }

}
