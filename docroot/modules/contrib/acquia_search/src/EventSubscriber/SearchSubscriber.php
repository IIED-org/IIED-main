<?php

namespace Drupal\acquia_search\EventSubscriber;

use Drupal\acquia_connector\Subscription;
use Drupal\acquia_search\AcquiaCryptConnector;
use Drupal\acquia_search\AcquiaSearchApiClient;
use Drupal\acquia_search\Client\Solarium\Endpoint as AcquiaSearchEndpoint;
use Drupal\acquia_search\Helper\Flood;
use Drupal\Component\Utility\Crypt;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Solarium\Core\Client\Adapter\AdapterHelper;
use Solarium\Core\Client\Request;
use Solarium\Core\Client\Response;
use Solarium\Core\Event\Events;
use Solarium\Core\Plugin\AbstractPlugin;
use Solarium\Exception\HttpException;
use Solarium\Plugin\NoWaitForResponseRequest;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class SearchSubscriber.
 *
 * Extends Solarium plugin for the Acquia Search module: authenticate, etc.
 *
 * @package Drupal\acquia_search\EventSubscriber
 */
class SearchSubscriber extends AbstractPlugin implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * Acquia search endpoint.
   *
   * @var \Drupal\acquia_search\Client\Solarium\Endpoint
   */
  protected $endpoint;

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
   * Acquia subscription service.
   *
   * @var \Drupal\acquia_connector\Subscription
   */
  protected $subscription;

  /**
   * Flood protection service.
   *
   * @var \Drupal\acquia_search\Helper\Flood
   */
  protected $flood;

  /**
   * Acquia Search Api Client.
   *
   * @var \Drupal\acquia_search\AcquiaSearchApiClient
   */
  protected $acquiaSearchApiClient;

  /**
   * Solarium Search Credential Subscriber.
   *
   * @param \Drupal\acquia_connector\Subscription $subscription
   *   Acquia Subscription Service.
   * @param \Drupal\acquia_search\AcquiaSearchApiClient $acquia_search_api_client
   *   Acquia Search Api Client.
   * @param \Drupal\acquia_search\Helper\Flood $flood
   *   Flood protection service.
   * @param array|null $options
   *   Options passed from Solarium.
   */
  public function __construct(Subscription $subscription, AcquiaSearchApiClient $acquia_search_api_client, Flood $flood, array $options = NULL) {
    $this->subscription = $subscription;
    $this->flood = $flood;
    $this->acquiaSearchApiClient = $acquia_search_api_client;
    parent::__construct($options);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    // HMAC Cookie injection has to fire before NoWaitForResponseRequest (pri 5)
    // and after PostBigRequest (priority 10). Set priority to 9.
    return [
      Events::PRE_EXECUTE_REQUEST => ['preExecuteRequest', 9],
      Events::POST_EXECUTE_REQUEST => ['postExecuteRequest', 9],
    ];
  }

  /**
   * Build Acquia Search Solr Authenticator.
   *
   * @param \Solarium\Core\Event\PreExecuteRequest|\Drupal\search_api_solr\Solarium\EventDispatcher\EventProxy $event
   *   PreExecuteRequest event.
   * @param string $event_name
   *   Name of the event.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher
   *   The calling event dispatcher.
   */
  public function preExecuteRequest($event, string $event_name, EventDispatcherInterface $dispatcher) {
    /** @var \Solarium\Core\Event\PreExecuteRequest $event */
    /** @var \Solarium\Core\Client\Request $request */
    $request = $event->getRequest();

    if (!$event->getEndpoint() instanceof AcquiaSearchEndpoint || $event->getEndpoint()->getHost() === 'localhost') {
      return;
    }

    $this->endpoint = $event->getEndpoint();

    // Run Flood control checks.
    if (!$this->flood->isAllowed($request->getHandler())) {
      // If request should be blocked, show an error message.
      $message = $this->t('Flood protection has blocked this Solr request. See more at <a href="@url">The Acquia Search flood control mechanism has blocked a Solr query due to API usage limits</a>', [
        '@url' => Flood::FLOOD_LIMIT_ARTICLE_URL,
      ]);
      \Drupal::logger('acquia_search')->error($message);

      // Build a static response which avoids a network request to Solr.
      $response = new Response((string) $message, ['HTTP/1.1 429 Too Many Requests']);
      $event->setResponse($response);
      $event->stopPropagation();
      return;
    }

    // When 'NoWaitForResponseRequest' plugin exists we need to inject its
    // altering data here to ensure hmac is calculated properly.
    // @see \Solarium\Plugin\NoWaitForResponseRequest.
    $event_subscribers = $dispatcher->getListeners($event_name);
    foreach ($event_subscribers as $subscriber) {
      if ($subscriber[0] instanceof NoWaitForResponseRequest) {
        $charset = $request->getParam('ie') ?? 'utf-8';
        $request->setMethod(Request::METHOD_POST);
        $request->setContentType(Request::CONTENT_TYPE_APPLICATION_X_WWW_FORM_URLENCODED, ['charset' => $charset]);
        $request->setRawData($request->getQueryString());
        $request->clearParams();
      }
    }

    if ($request->getFileUpload()) {
      $helper = new AdapterHelper();
      $body = $helper->buildUploadBodyFromRequest($request);
      $request->setRawData($body);
    }

    $request->addParam('request_id', uniqid(), TRUE);

    // If we're hosted on Acquia, and have an Acquia request ID,
    // append it to the request so that we map Solr queries to Acquia search
    // requests.
    if (getenv('HTTP_X_REQUEST_ID')) {
      $xid = empty(getenv('HTTP_X_REQUEST_ID')) ? '-' : getenv('HTTP_X_REQUEST_ID');
      $request->addParam('x-request-id', $xid, TRUE);
    }
    $this->uri = AdapterHelper::buildUri($request, $this->endpoint);

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
    if ($cookie === '') {
      $message = $this->t('Could not build authentication cookie due to missing derived key for HMAC values.');
      \Drupal::service('acquia_search.logger_channel')->error($message);
      $response = new Response($message, ['HTTP/1.1 401 Unauthorized']);
      $event->setResponse($response);
      $event->stopPropagation();
      return;
    }
    $module_version = $this->subscription->getSubscription()['acquia_search']['module_version'];
    $request->addHeader('Cookie: ' . $cookie);
    $request->addHeader('User-Agent: ' . 'acquia_search/' . $module_version);
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
    /** @var \Solarium\Core\Event\PostExecuteRequest $event */
    $response = $event->getResponse();
    if (!$event->getEndpoint() instanceof AcquiaSearchEndpoint) {
      return;
    }

    $this->endpoint = $event->getEndpoint();

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

    // If the response is a 200 and empty, likely a NoWaitForResponseRequest.
    if ($event->getResponse()->getBody() === '') {
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

    foreach ($headers as $value) {
      if (stristr($value, 'pragma') && preg_match("/hmac_digest=([^;]+);/i", $value, $reg)) {
        return trim($reg[1]);
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
      $env_id = $this->endpoint->getKey();
    }

    if (isset($this->derivedKey[$env_id])) {
      return $this->derivedKey[$env_id];
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
    $preferredCoreId = $this->endpoint->getCore();
    if ($preferredCoreId) {
      $keys = $this->acquiaSearchApiClient->getSearchIndexKeys($preferredCoreId);
      return $keys ?: NULL;
    }

    return NULL;
  }

}
