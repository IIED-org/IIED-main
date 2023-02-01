<?php

namespace Drupal\acquia_search;

use Acquia\Hmac\Digest\Digest;
use Acquia\Hmac\Digest\DigestInterface;
use Acquia\Hmac\Guzzle\HmacAuthMiddleware;
use Acquia\Hmac\Key;
use Drupal\acquia_connector\Subscription;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Http\ClientFactory;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\HandlerStack;

/**
 * Acquia Search API Client.
 *
 * Not to be confused with Search API, this is the service that works with RAGE
 * to fetch the cores available to a customer.
 *
 * @package Drupal\acquia_search
 */
class AcquiaSearchApiClient {

  /**
   * Cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * The HTTP client factory service.
   *
   * @var \Drupal\Core\Http\ClientFactory
   */
  protected $clientFactory;

  /**
   * Acquia Search Logger Channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * HTTP headers.
   *
   * @var array
   */
  protected $headers = [
    'Content-Type' => 'application/json',
    'Accept' => 'application/json',
  ];

  /**
   * Time Service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * Acquia Subscription Service.
   *
   * @var \Drupal\acquia_connector\Subscription
   */
  protected $subscription;

  /**
   * Drupal's locking layer instance.
   *
   * @var \Drupal\Core\Lock\LockBackendInterface
   */
  protected $lock;

  /**
   * The message digest to use when signing requests.
   *
   * @var \Acquia\Hmac\Digest\DigestInterface
   */
  protected $digest;

  /**
   * AcquiaSearchApiClient constructor.
   *
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger_channel
   *   Logger Channel service.
   * @param \Drupal\acquia_connector\Subscription $subscription
   *   The Acquia subscription service.
   * @param \Drupal\Core\Http\ClientFactory $http_client_factory
   *   HTTP client.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   Cache backend.
   * @param \Drupal\Component\Datetime\TimeInterface $date_time
   *   Time Interface service.
   * @param \Drupal\Core\Lock\LockBackendInterface $lock
   *   Drupal's locking layer instance.
   * @param \Acquia\Hmac\Digest\DigestInterface|null $digest
   *   The message digest to use when signing requests.
   */
  public function __construct(LoggerChannelInterface $logger_channel, Subscription $subscription, ClientFactory $http_client_factory, CacheBackendInterface $cache, TimeInterface $date_time, LockBackendInterface $lock, DigestInterface $digest = NULL) {
    $this->logger = $logger_channel;
    $this->subscription = $subscription;
    $this->clientFactory = $http_client_factory;
    $this->time = $date_time;
    $this->cache = $cache;
    $this->lock = $lock;
    $this->digest = $digest ?: new Digest();
  }

  /**
   * Helper function to fetch all search v3 indexes for given subscription.
   *
   * @return array|bool
   *   Acquia Search indexes array, FALSE on Acquia Search API failure.
   *
   * @throws \Exception
   */
  public function getSearchIndexes() {
    $id = $this->subscription->getSettings()->getIdentifier();
    $cid = 'acquia_search.indexes.' . $id;
    $now = $this->time->getRequestTime();

    if ($cache = $this->cache->get($cid)) {
      return $cache->data;
    }

    $path = '/v2/indexes';
    $query_string = 'filter[network_id]=' . $id;
    $result = [];

    $timeout = 30;
    while (!$this->lock->acquire('acquia_search_get_search_indexes')) {
      // Throw an exception after X amount of seconds.
      if (($now + $timeout) < $this->time->getRequestTime()) {
        throw new \Exception("Couldn't acquire lock for 'acquia_search_get_search_indexes' in less than $timeout seconds.");
      }
    }

    $indexes = $this->searchRequest($path, $query_string);
    if (empty($indexes) && !is_array($indexes)) {
      // When API is not reachable, cache it for 1 minute.
      $this->cache->set($cid, FALSE, $now + 60, ['acquia_search_indexes']);

      return FALSE;
    }
    $this->lock->release('acquia_search_get_search_indexes');

    if (isset($indexes['data'])) {
      foreach ($indexes['data'] as $index) {
        $result[$index['id']] = [
          'balancer' => parse_url($index['attributes']['url'], PHP_URL_HOST),
          'core_id' => $index['id'],
          'data' => $index,
        ];
      }
    }
    // Cache will be set in both cases, 1. when search v3 cores are found and
    // 2. when there are no search v3 cores but api is reachable.
    $this->cache->set($cid, $result, $now + (24 * 60 * 60), ['acquia_search_indexes']);

    return $result;
  }

  /**
   * Gets the key for a given search index.
   *
   * @param string $index_name
   *   The index name.
   *
   * @return array|false
   *   Returns the keys, or FALSE if there was a failure in request.
   */
  public function getSearchIndexKeys(string $index_name) {
    $id = $this->subscription->getSettings()->getIdentifier();
    $cid = "acquia_search.indexes.{$id}.{$index_name}";
    $now = $this->time->getRequestTime();

    if (($cache = $this->cache->get($cid))) {
      return $cache->data;
    }
    $keys = $this->searchRequest('/v2/index/key', 'index_name=' . $index_name);
    $this->cache->set($cid, $keys, $now + (24 * 60 * 60), ['acquia_search_indexes']);

    return $keys;
  }

  /**
   * Create and send a request to search controller.
   *
   * @param string $path
   *   Path to call.
   * @param string $query_string
   *   Query string to call.
   *
   * @return array|false
   *   Response array or FALSE.
   */
  private function searchRequest(string $path, string $query_string) {
    $subscription_data = $this->subscription->getSubscription();
    // Return no results if there is no subscription data.
    if (!$subscription_data || !$this->subscription->isActive()) {
      return FALSE;
    }
    $options = [
      'headers' => [
        'X-Authorization-Timestamp' => $this->time->getRequestTime(),
      ],
      'timeout' => 10,
    ];

    try {
      // Create a new HMAC key for the middleware.
      $key_id = $this->subscription->getSettings()->getApplicationUuid();
      $key_secret = $this->subscription->getSettings()->getSecretKey();
      $key = new Key($key_id, $key_secret);

      // Create the client from factory using the HMAC middleware.
      $middleware = new HmacAuthMiddleware($key, 'search', []);
      $stack = HandlerStack::create();
      $stack->push($middleware);
      $client = $this->clientFactory->fromOptions(['handler' => $stack]);

      $host = $subscription_data['acquia_search']['api_host'];
      $uri = $host . $path . '?' . $query_string;
      $response = $client->get($uri, $options);
      if (!$response) {
        throw new \Exception('Empty Response');
      }
      $status_code = $response->getStatusCode();
      if ($status_code < 200 || $status_code > 299) {
        $this->logger->error("Couldn't connect to search v3 API: @message",
          ['@message' => $response->getReasonPhrase()]);
        return FALSE;
      }

      return Json::decode((string) $response->getBody());
    }
    catch (RequestException $e) {
      if ($e->getCode() == 401) {
        $this->logger->error("Couldn't connect to search v3 API:
        Received a 401 response from the API. @message",
          ['@message' => $e->getMessage()]);
      }
      elseif ($e->getCode() == 404) {
        $this->logger->error("Couldn't connect to search v3 API:
        Received a 404 response from the API. @message",
          ['@message' => $e->getMessage()]);
      }
      else {
        $this->logger->error("Couldn't connect to search v3 API:
        @message", ['@message' => $e->getMessage()]);
      }
    }
    catch (\Exception $e) {
      $this->logger->error("Couldn't connect to search v3 API: @message",
        ['@message' => $e->getMessage()]);
    }

    return FALSE;
  }

}
