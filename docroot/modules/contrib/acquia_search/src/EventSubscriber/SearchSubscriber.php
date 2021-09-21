<?php

namespace Drupal\acquia_search\EventSubscriber;

use Drupal\acquia_search\AcquiaCryptConnector;
use Drupal\acquia_search\Helper\Flood;
use Drupal\acquia_search\Helper\Runtime;
use Drupal\acquia_search\Helper\Storage;
use Drupal\Component\Utility\Crypt;
use Solarium\Core\Client\Adapter\AdapterHelper;
use Solarium\Core\Client\Client;
use Solarium\Core\Client\Response;
use Solarium\Core\Event\Events;
use Solarium\Core\Plugin\AbstractPlugin;
use Solarium\Exception\HttpException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class SearchSubscriber.
 *
 * Extends Solarium plugin for the Acquia Search module: authenticate, etc.
 *
 * @package Drupal\acquia_search\EventSubscriber
 */
class SearchSubscriber extends AbstractPlugin implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   *
   * @var \Solarium\Client
   */
  protected $client;

  /**
   * Array of derived keys, keyed by environment id.
   *
   * @var array
   */
  protected $derivedKey = [];

  /**
   * Nonce.
   *
   * @var string
   */
  protected $nonce = '';

  /**
   * URI.
   *
   * @var string
   */
  protected $uri = '';

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      Events::PRE_EXECUTE_REQUEST => 'preExecuteRequest',
      Events::POST_EXECUTE_REQUEST => 'postExecuteRequest',
    ];
  }

  /**
   * Build Acquia Search Solr Authenticator.
   *
   * @param \Solarium\Core\Event\PreExecuteRequest|\Drupal\search_api_solr\Solarium\EventDispatcher\EventProxy $event
   *   PreExecuteRequest event.
   */
  public function preExecuteRequest($event) {
    /** @var \Solarium\Core\Event\PreExecuteRequest $event */
    /** @var \Solarium\Core\Client\Request $request */
    $request = $event->getRequest();

    if (!($this->client instanceof Client)) {
      return;
    }

    // Run Flood control checks.
    if (!Flood::isAllowed($request->getHandler())) {
      // If request should be blocked, show an error message.
      $message = 'The Acquia Search flood control mechanism has blocked a Solr query due to API usage limits. You should retry in a few seconds. Contact the site administrator if this message persists.';
      \Drupal::messenger()->addError($message);

      // Build a static response which avoids a network request to Solr.
      $response = new Response($message, ['HTTP/1.1 429 Too Many Requests']);
      $event->setResponse($response);
      $event->stopPropagation();
      return;
    }
    $request->addParam('request_id', uniqid(), TRUE);
    if ($request->getFileUpload()) {
      $helper = new AdapterHelper();
      $body = $helper->buildUploadBodyFromRequest($request);
      $request->setRawData($body);
    }

    // If we're hosted on Acquia, and have an Acquia request ID,
    // append it to the request so that we map Solr queries to Acquia search
    // requests.
    if (isset($_ENV['HTTP_X_REQUEST_ID'])) {
      $xid = empty($_ENV['HTTP_X_REQUEST_ID']) ? '-' : $_ENV['HTTP_X_REQUEST_ID'];
      $request->addParam('x-request-id', $xid, TRUE);
    }
    $endpoint = $this->client->getEndpoint();
    $this->uri = AdapterHelper::buildUri($request, $endpoint);

    $this->nonce = Crypt::randomBytesBase64(24);
    $raw_post_data = $request->getRawData();
    // We don't have any raw POST data for pings only.
    if (!$raw_post_data) {
      $parsed_url = parse_url($this->uri);
      $path = $parsed_url['path'] ?? '/';
      $query = isset($parsed_url['query']) ? '?' . $parsed_url['query'] : '';
      $raw_post_data = $path . $query;
    }

    $cookie = $this->calculateAuthCookie($raw_post_data, $this->nonce);
    $request->addHeader('Cookie: ' . $cookie);
    $request->addHeader('User-Agent: ' . 'acquia_search/' . Storage::getVersion());

  }

  /**
   * Validate response.
   *
   * @param \Solarium\Core\Event\PostExecuteRequest|\Drupal\search_api_solr\Solarium\EventDispatcher\EventProxy $event
   *   postExecuteRequest event.
   *
   * @throws \Solarium\Exception\HttpException
   */
  public function postExecuteRequest($event) {
    if (!($this->client instanceof Client)) {
      return;
    }

    /** @var \Solarium\Core\Event\PostExecuteRequest $event */
    $response = $event->getResponse();

    if ($response->getStatusCode() != 200) {
      throw new HttpException(
        $response->getStatusMessage(),
        $response->getStatusCode(),
        $response->getBody()
      );
    }

    if ($event->getRequest()->getHandler() == 'admin/ping') {
      return;
    }

    $this->authenticateResponse($event->getResponse(), $this->nonce, $this->uri);

  }

  /**
   * Validate the hmac for the response body.
   *
   * @param \Solarium\Core\Client\Response $response
   *   Solarium Response.
   * @param string $nonce
   *   Nonce.
   * @param string $url
   *   Url.
   *
   * @return \Solarium\Core\Client\Response
   *   Solarium Response.
   *
   * @throws \Solarium\Exception\HttpException
   */
  protected function authenticateResponse(Response $response, $nonce, $url) {

    $hmac = $this->extractHmac($response->getHeaders());
    if (!$this->validateResponse($hmac, $nonce, $response->getBody())) {
      throw new HttpException('Authentication of search content failed url: ' . $url);
    }

    return $response;

  }

  /**
   * Look in the headers and get the hmac_digest out.
   *
   * @param array $headers
   *   Headers array.
   *
   * @return string
   *   Hmac_digest or empty string.
   */
  public function extractHmac(array $headers): string {

    $reg = [];

    if (is_array($headers)) {
      foreach ($headers as $value) {
        if (stristr($value, 'pragma') && preg_match("/hmac_digest=([^;]+);/i", $value, $reg)) {
          return trim($reg[1]);
        }
      }
    }

    return '';

  }

  /**
   * Validate the authenticity of returned data using a nonce and HMAC-SHA1.
   *
   * @param string $hmac
   *   HMAC.
   * @param string $nonce
   *   Nonce.
   * @param string $string
   *   Data string.
   * @param string $derived_key
   *   Derived key.
   * @param string $env_id
   *   Environment Id.
   *
   * @return bool
   *   TRUE if response is valid.
   */
  public function validateResponse($hmac, $nonce, $string, $derived_key = NULL, $env_id = NULL) {

    if (empty($derived_key)) {
      $derived_key = $this->getDerivedKey($env_id);
    }

    return $hmac == hash_hmac('sha1', $nonce . $string, $derived_key);

  }

  /**
   * Get the derived key.
   *
   * Get the derived key for the solr hmac using the information shared with
   * acquia.com.
   *
   * @param string $env_id
   *   Environment Id.
   *
   * @return string|null
   *   Derived Key.
   */
  public function getDerivedKey($env_id = NULL): ?string {

    if (empty($env_id)) {
      $env_id = $this->client->getEndpoint()->getKey();
    }

    // Get derived key for Acquia Search V3.
    $search_v3_index = $this->getSearchIndexKeys();
    if ($search_v3_index) {
      $this->derivedKey[$env_id] = AcquiaCryptConnector::createDerivedKey($search_v3_index['product_policies']['salt'], $search_v3_index['key'], $search_v3_index['secret_key']);
      return $this->derivedKey[$env_id];
    }

    return NULL;

  }

  /**
   * Creates an authenticator based on a data string and HMAC-SHA1.
   *
   * @param string $string
   *   Data string.
   * @param string $nonce
   *   Nonce.
   * @param string $derived_key
   *   Derived key.
   * @param string $env_id
   *   Environment Id.
   *
   * @return string
   *   Auth cookie string.
   */
  public function calculateAuthCookie($string, $nonce, $derived_key = NULL, $env_id = NULL) {

    if (empty($derived_key)) {
      $derived_key = $this->getDerivedKey($env_id);
    }

    if (empty($derived_key)) {
      // Expired or invalid subscription - don't continue.
      return '';
    }

    $time = time();

    $hmac = hash_hmac('sha1', $time . $nonce . $string, $derived_key);

    return sprintf('acquia_solr_time=%s; acquia_solr_nonce=%s; acquia_solr_hmac=%s;', $time, $nonce, $hmac);

  }

  /**
   * Fetches the Acquia Search v3 index keys.
   *
   * @return array|null
   *   Search v3 index keys.
   */
  public function getSearchIndexKeys(): ?array {

    $core_service = Runtime::getPreferredSearchCoreService();

    // Preferred core isn't available - you have to configure it using settings
    // described in the README.txt.
    if (!$core_service->isPreferredCoreAvailable()) {
      return NULL;
    }

    return $core_service->getPreferredCore()['data'];

  }

}
