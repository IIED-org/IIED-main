<?php

namespace Drupal\acquia_search;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Crypt;
use Drupal\Core\Cache\CacheBackendInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

/**
 * Acquia implementation of the Search API Client.
 *
 * @package Drupal\acquia_search
 */
class AcquiaSearchApiClient {

  /**
   * Authentication array.
   *
   * @var array
   */
  protected $authInfo;

  /**
   * Cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * The HTTP client to fetch the feed data with.
   *
   * @var \GuzzleHttp\Client
   */
  protected $client;

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
   * AcquiaSearchApiClient constructor.
   *
   * @param array $auth_info
   *   Authorization array.
   * @param \GuzzleHttp\Client $http_client
   *   HTTP client.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   Cache backend.
   */
  public function __construct(array $auth_info, Client $http_client, CacheBackendInterface $cache) {

    $this->authInfo = $auth_info;
    $this->client = $http_client;
    $this->cache = $cache;
  }

  /**
   * Helper function to fetch all search v3 indexes for given subscription.
   *
   * @param string $id
   *   Acquia Subscription Identifier.
   *
   * @return array|bool
   *   Acquia Search indexes array, FALSE on Acquia Search API failure.
   */
  public function getSearchIndexes(string $id) {

    if (empty($id)) {
      return FALSE;
    }

    $cid = 'acquia_search.indexes.' . $id;
    $now = \Drupal::time()->getRequestTime();

    if (($cache = $this->cache->get($cid)) && $cache->expire > $now) {
      return $cache->data;
    }

    $path = '/v2/index/configure';
    $query_string = 'network_id=' . $id;
    $result = [];

    $indexes = $this->searchRequest($path, $query_string);
    if (empty($indexes) && !is_array($indexes)) {
      // When API is not reachable, cache it for 1 minute.
      $this->cache->set($cid, FALSE, \Drupal::time()->getRequestTime() + 60);

      return FALSE;
    }

    foreach ($indexes as $index) {
      $result[$index['key']] = [
        'balancer' => $index['host'],
        'core_id' => $index['key'],
        'data' => $index,
      ];
    }
    // Cache will be set in both cases, 1. when search v3 cores are found and
    // 2. when there are no search v3 cores but api is reachable.
    $this->cache->set($cid, $result, $now + (24 * 60 * 60));

    return $result;

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
  public function searchRequest(string $path, string $query_string) {

    $host = $this->authInfo['host'];
    $req_time = \Drupal::time()->getRequestTime();
    $authorization_string = $this->calculateAuthString();
    $req_params = [
      'GET',
      preg_replace('/^https?:\/\//', '', $host),
      $path,
      $query_string,
      $authorization_string,
      $req_time,
    ];
    $authorization_header = $this->calculateAuthHeader($req_params, $authorization_string);

    $data = [
      'host' => $host,
      'headers' => [
        'Authorization' => $authorization_header,
        'X-Authorization-Timestamp' => $req_time,
      ],
    ];

    $uri = $data['host'] . $path . '?' . $query_string;
    $options = [
      'headers' => $data['headers'],
      'timeout' => 5,
    ];

    try {
      $response = $this->client->get($uri, $options);
      if (!$response) {
        throw new \Exception('Empty Response');
      }
      $stream_size = $response->getBody()->getSize();
      $data = Json::decode($response->getBody()->read($stream_size));
      $status_code = $response->getStatusCode();

      if ($status_code < 200 || $status_code > 299) {
        \Drupal::logger('acquia_search')->error("Couldn't connect to search v3 API: @message",
          ['@message' => $response->getReasonPhrase()]);
        return FALSE;
      }
      return $data;
    }
    catch (RequestException $e) {
      if ($e->getCode() == 401) {
        \Drupal::logger('acquia_search')->error("Couldn't connect to search v3 API:
          Received a 401 response from the API. @message", ['@message' => $e->getMessage()]);
      }
      elseif ($e->getCode() == 404) {
        \Drupal::logger('acquia_search')->error("Couldn't connect to search v3 API:
          Received a 404 response from the API. @message", ['@message' => $e->getMessage()]);
      }
      else {
        \Drupal::logger('acquia_search')->error("Couldn't connect to search v3 API:
          @message", ['@message' => $e->getMessage()]);
      }
    }
    catch (\Exception $e) {
      \Drupal::logger('acquia_search')->error("Couldn't connect to search v3 API: @message",
        ['@message' => $e->getMessage()]);
    }

    return FALSE;
  }

  /**
   * Creates an authenticator based on a HMAC V2 signer.
   *
   * @param array $req_params
   *   Request parameters.
   * @param string $authorization_string
   *   Authorization string.
   *
   * @return string
   *   Returns the signed auth header
   */
  private function calculateAuthHeader(array $req_params, string $authorization_string): string {
    $signature_base_string = implode("\n", $req_params);
    $digest = hash_hmac('sha256', $signature_base_string, base64_decode($this->authInfo['key'], TRUE), TRUE);
    $signature = base64_encode($digest);
    $authorization_header_string = str_replace("=", "=\"", str_replace("&", "\",", $authorization_string));
    return 'acquia-http-hmac ' . $authorization_header_string . '",signature="' . $signature . '"';
  }

  /**
   * Calculates authorization string.
   *
   * @return string
   *   Returns the auth string.
   */
  private function calculateAuthString() {

    $nonce = Crypt::randomBytesBase64(24);
    return 'id=' . $this->authInfo['app_uuid'] . '&nonce=' . $nonce . '&realm=search&version=2.0';

  }

}
