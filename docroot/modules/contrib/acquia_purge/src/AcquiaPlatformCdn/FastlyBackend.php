<?php

namespace Drupal\acquia_purge\AcquiaPlatformCdn;

use Drupal\acquia_purge\AcquiaCloud\Hash;
use Drupal\acquia_purge\AcquiaCloud\PlatformInfoInterface;
use Drupal\acquia_purge\Plugin\Purge\Purger\DebuggerInterface;
use Drupal\acquia_purge\Plugin\Purge\TagsHeader\TagsHeaderValue;
use Drupal\purge\Logger\LoggerChannelPartInterface;
use Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\ResponseInterface;

/**
 * Provides a Fastly backend for the Platform CDN purger.
 */
class FastlyBackend extends BackendBase implements BackendInterface {

  /**
   * String: The Fastly API endpoint to connect to.
   */
  const API_ENDPOINT = 'https://api.fastly.com/';

  /**
   * Float: the number of seconds to wait for Fastly to open a socket.
   */
  const CONNECT_TIMEOUT = 1.5;

  /**
   * Float: the timeout of the request in seconds.
   */
  const TIMEOUT = 3.0;

  /**
   * Fastly Service ID: unique identifier for a Fastly service.
   *
   * @var string
   */
  protected $serviceId;

  /**
   * Fastly Key: also called 'Fastly Token' in the API documentation.
   *
   * @var string
   */
  protected $token;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $config, PlatformInfoInterface $acquia_purge_platforminfo, LoggerChannelPartInterface $logger, DebuggerInterface $debugger, ClientInterface $http_client) {
    parent::__construct($config, $acquia_purge_platforminfo, $logger, $debugger, $http_client);
    $this->serviceId = (string) $this->config['service_id'];
    $this->token = (string) $this->config['token'];
    // Call platformInfo() to having platform information accessible to
    // the static helper functions.
    self::platformInfo($this->platformInfo);
  }

  /**
   * {@inheritdoc}
   */
  public function invalidateTags(array $invalidations) {

    // Set the invalidation objects into PROCESSING state and extract the tags.
    $tags = [];
    foreach ($invalidations as $invalidation) {
      $invalidation->setState(InvalidationInterface::PROCESSING);
      $tags[] = $invalidation->getExpression();
    }
    $tags = new TagsHeaderValue($tags, self::getHashedTags($tags));

    // Execute the API call and triage the response.
    $success = FALSE;
    try {
      $request = new Request('POST', $this->fastlyRequestUri('service/service_id/purge'));
      $request_opt = $this->fastlyRequestOpt(['Surrogate-Key' => $tags->__toString()]);
      // Pass the TagsHeaderValue to DebuggerMiddleware (when loaded).
      if ($this->debugger()->enabled()) {
        $request_opt['acquia_purge_tags'] = $tags;
      }
      $response = $this->httpClient->send($request, $request_opt);
      $data = $this->fastlyResponseData($response);
      if (count($data)) {
        $success = TRUE;
      }
      else {
        throw new RequestException(
          'Unexpected API response.',
          $request,
          $response
        );
      }
    }
    catch (\Exception $e) {
      $this->debugger()->logFailedRequest($e);
    }

    // Update the invalidation objects accordingly.
    if ($success) {
      foreach ($invalidations as $invalidation) {
        $invalidation->setState(InvalidationInterface::SUCCEEDED);
      }
    }
    else {
      foreach ($invalidations as $invalidation) {
        $invalidation->setState(InvalidationInterface::FAILED);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function invalidateUrls(array $invalidations) {
    $request_opt = $this->fastlyRequestOpt();
    $request_opt['verify'] = FALSE;
    unset($request_opt['headers']['Accept']);

    // Iterate over each URL and attempt to purge it.
    foreach ($invalidations as $invalidation) {
      $invalidation->setState(InvalidationInterface::PROCESSING);

      // Execute the API call and triage the response.
      $success = FALSE;
      try {
        $request = new Request('PURGE', $invalidation->getExpression());
        $response = $this->httpClient->send($request, $request_opt);
        $data = $this->fastlyResponseData($response);
        if (isset($data['status']) && $data['status'] === 'ok') {
          $success = TRUE;
        }
        else {
          throw new RequestException(
            'Unexpected API response.',
            $request,
            $response
          );
        }
      }
      catch (\Exception $e) {
        $this->debugger()->logFailedRequest($e);
      }

      // Update the invalidation object accordingly.
      if ($success) {
        $invalidation->setState(InvalidationInterface::SUCCEEDED);
      }
      else {
        $invalidation->setState(InvalidationInterface::FAILED);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function invalidateEverything(array $invalidations) {

    // Set the 'everything' object(s) into processing mode.
    foreach ($invalidations as $invalidation) {
      $invalidation->setState(InvalidationInterface::PROCESSING);
    }

    // Every response is tagged with the site identifier by ::tagsHeaderValue(),
    // so that we can use it here as key to purge all CDN content with.
    $key = current(Hash::cacheTags([$this->platformInfo->getSiteIdentifier()]));

    // Execute the API call and triage the response.
    $success = FALSE;
    try {
      $request = new Request('POST', $this->fastlyRequestUri('service/service_id/purge/%s', $key));
      $response = $this->httpClient->send($request, $this->fastlyRequestOpt());
      $data = $this->fastlyResponseData($response);
      if (isset($data['status']) && $data['status'] === 'ok') {
        $success = TRUE;
      }
      else {
        throw new RequestException(
          'Unexpected API response.',
          $request,
          $response
        );
      }
    }
    catch (\Exception $e) {
      $this->debugger()->logFailedRequest($e);
    }

    // Update the invalidation objects accordingly.
    if ($success) {
      foreach ($invalidations as $invalidation) {
        $invalidation->setState(InvalidationInterface::SUCCEEDED);
      }
    }
    else {
      foreach ($invalidations as $invalidation) {
        $invalidation->setState(InvalidationInterface::FAILED);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function tagsHeaderName() {
    return 'Surrogate-Key';
  }

  /**
   * {@inheritdoc}
   */
  public static function tagsHeaderValue(array $tags) {
    $tags_hashed = self::getHashedTags($tags);

    // Always add a hashed site identifier to the tags on the Surrogate-Key
    // header, so that ::invalidateEverything() can use it to wipe the CDN.
    $tags[] = $identifier = self::platformInfo()->getSiteIdentifier();
    $tags_hashed[] = current(Hash::cacheTags([$identifier]));
    return new TagsHeaderValue($tags, $tags_hashed);
  }

  /**
   * Get a Guzzle option array prepared for Fastly API calls.
   *
   * @param string[] $headers
   *   Additional headers to merge into the headers option.
   *
   * @return mixed[]
   *   Guzzle option array.
   */
  protected function fastlyRequestOpt(array $headers = []) {
    $opt = [
      'headers' => $headers,
      'http_errors' => FALSE,
      'connect_timeout' => self::CONNECT_TIMEOUT,
      'timeout' => self::TIMEOUT,
    ];
    $opt['headers']['Accept'] = 'application/json';
    $opt['headers']['Fastly-Key'] = $this->token;
    $opt['headers']['User-Agent'] = 'Acquia Purge';
    // Trigger the debugging middleware when Purge's debug mode is enabled.
    if ($this->debugger()->enabled()) {
      $opt['acquia_purge_debugger'] = $this->debugger();
    }
    return $opt;
  }

  /**
   * Get a fully qualified Fastly API uri in which 'service_id' is replaced.
   *
   * This helper can be used similar to sprintf() by passing in placeholders
   * like '%s' and '%d' to get substituted URLs, for example:
   *   $this->fastlyRequestUri('service/service_id/purge/%s', $key)
   *
   * @param string $path
   *   The API path on the Fastly API.
   *
   * @return string
   *   Fastly API uri with 'service_id' replaced.
   */
  protected function fastlyRequestUri($path) {
    $args = func_get_args();
    $args[0] = str_replace('service_id', $this->serviceId, $args[0]);
    $args[0] = ltrim($args[0], '/');
    return self::API_ENDPOINT . call_user_func_array('sprintf', $args);
  }

  /**
   * Decode JSON from a Fastly response object body.
   *
   * @param \Psr\Http\Message\ResponseInterface $response
   *   The HTTP response object.
   *
   * @throws \RuntimeException
   *   Thrown when common runtime issues are detected, for invalid credentials
   *   for instance. Calls to setTemporaryRuntimeError() will be made as well.
   *
   * @return array
   *   JSON decoded response data from the Fastly API.
   */
  protected function fastlyResponseData(ResponseInterface $response) {
    if ($data = json_decode($response->getBody(), TRUE)) {

      // Detect invalid credentials and suspend operations for a full day,
      // we do this to prevent flooding Fastly in unattended environments.
      if (isset($data['msg']) && (strpos($data['msg'], 'credentials') !== FALSE)) {
        $message = "Invalid credentials - please contact Acquia Support and";
        $message .= " clear the cache after the issue got resolved.";
        self::setTemporaryRuntimeError($message, 86400);
        throw new \RuntimeException($message);
      }

      // Detect invalid environments and suspend operations for 12 hours.
      if (isset($data['msg'], $data['detail']) && ($data['msg'] == 'Record not found')) {
        if ($data['detail'] == 'Cannot find service') {
          $message = "Invalid environment - please contact Acquia Support and";
          $message .= " clear the cache after the issue got resolved.";
          self::setTemporaryRuntimeError($message, 43200);
          throw new \RuntimeException($message);
        }
      }

      return $data;
    }
    return [];
  }

  /**
   * Salts input tags with the site identifier and hashes them.
   *
   * @param string[] $tags
   *   Non-associative array cache tags.
   *
   * @return string[]
   *   Non-associative array with salted and hashed copies of the input tags.
   */
  protected static function getHashedTags(array $tags) {
    $identifier = self::platformInfo()->getSiteIdentifier();
    $tags_prefixed = [];
    foreach ($tags as $tag) {
      $tags_prefixed[] = $identifier . $tag;
    }
    return Hash::cacheTags($tags_prefixed);
  }

  /**
   * {@inheritdoc}
   */
  public static function validateConfiguration(array $config) {
    // Calls to ::validateConfiguration are made very early, and because of this
    // it would be too expensive to do any live validation of the given
    // token and service_id. Instead, we only verify that they look right and
    // let ::fastlyResponseData() make calls to ::setTemporaryRuntimeError()
    // so that cache invalidation gets suspended in case of bad API creds.
    if (!isset($config['service_id'], $config['token'])) {
      return FALSE;
    }
    if (!(strlen($config['service_id']) && strlen($config['token']))) {
      return FALSE;
    }
    return TRUE;
  }

}
