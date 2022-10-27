<?php

namespace Drupal\acquia_connector\Client;

use Drupal\acquia_connector\ConnectorException;
use Drupal\acquia_connector\CryptConnector;
use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Crypt;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

/**
 * Acquia connector client.
 *
 * @package Drupal\acquia_connector
 */
class AcquiaConnectorClient {

  use LoggerChannelTrait;
  use StringTranslationTrait;
  use MessengerTrait;

  /**
   * The HTTP client to fetch the feed data with.
   *
   * @var \GuzzleHttp\Client
   */
  protected $client;

  /**
   * Request headers.
   *
   * @var array
   */
  protected $headers;

  /**
   * Acquia SPI server.
   *
   * @var string
   */
  protected $server;

  /**
   * Current request timestamp.
   *
   * @var int
   */
  protected $time;

  /**
   * Connector Client Constructor.
   *
   * @param \GuzzleHttp\Client $client
   *   HTTP Client.
   * @param string $uri
   *   URI being passed into the client.
   * @param int $request_timestamp
   *   Request timestamp.
   */
  public function __construct(Client $client, string $uri, int $request_timestamp) {
    $this->server = $uri;
    $this->client = $client;
    $this->time = $request_timestamp;

    $this->headers = [
      'Content-Type' => 'application/json',
      'Accept' => 'application/json',
    ];
  }

  /**
   * Get account settings to use for creating request authorizations.
   *
   * @param string $email
   *   Acquia account email.
   * @param string $password
   *   Plain-text password for Acquia account. Will be hashed for communication.
   *
   * @return array|false
   *   Credentials array or FALSE.
   *
   * @throws \Drupal\acquia_connector\ConnectorException
   */
  public function getSubscriptionCredentials($email, $password) {
    $body = ['email' => $email];
    $authenticator = $this->buildAuthenticator($email, $this->time, ['rpc_version' => ACQUIA_CONNECTOR_ACQUIA_SPI_DATA_VERSION]);
    $data = [
      'body' => $body,
      'authenticator' => $authenticator,
    ];

    // Don't use nspiCall() - key is not defined yet.
    $communication_setting = $this->request('POST', '/agent-api/subscription/communication', $data);
    if ($communication_setting) {
      $crypt_pass = new CryptConnector($communication_setting['algorithm'], $password, $communication_setting['hash_setting']);
      $pass = $crypt_pass->cryptPass();

      $body = [
        'email' => $email,
        'pass' => $pass,
        'rpc_version' => ACQUIA_CONNECTOR_ACQUIA_SPI_DATA_VERSION,
      ];
      $authenticator = $this->buildAuthenticator($pass, $this->time, ['rpc_version' => ACQUIA_CONNECTOR_ACQUIA_SPI_DATA_VERSION]);
      $data = [
        'body' => $body,
        'authenticator' => $authenticator,
      ];

      // Don't use nspiCall() - key is not defined yet.
      $response = $this->request('POST', '/agent-api/subscription/credentials', $data);
      if ($response['body']) {
        return $response['body'];
      }
    }
    return FALSE;
  }

  /**
   * Get Acquia subscription from Acquia.
   *
   * @param string $id
   *   Acquia Subscription ID.
   * @param string $key
   *   Acquia Subscription key.
   * @param array $body
   *   Optional.
   *
   * @return array|false
   *   Acquia Subscription array or FALSE.
   *
   * @throws \Exception
   */
  public function getSubscription($id, $key, array $body = []) {
    $body['identifier'] = $id;
    // There is an identifier and key, so attempt communication.
    $subscription = [];

    if ($search_info = $this->getSearchModulesData()) {
      $body['search_version'] = $search_info;
    }

    try {
      $response = $this->nspiCall('/agent-api/subscription', $body, $key);
      if (!empty($response['result']['authenticator']) && $this->validateResponse($key, $response['result'], $response['authenticator'])) {
        $subscription += $response['result']['body'];
        $subscription['timestamp'] = $this->time;
        return $subscription;
      }
    }
    catch (ConnectorException $e) {
      $this->messenger()->addError($this->t('Error occurred while retrieving Acquia subscription information. See logs for details.'));
      if ($e->isCustomized()) {
        $this->getLogger('acquia connector')
          ->error($e->getCustomMessage() . '. Response data: @data', ['@data' => json_encode($e->getAllCustomMessages())]);
      }
      else {
        $this->getLogger('acquia connector')->error($e->getMessage());
      }
      throw $e;
    }

    return FALSE;
  }

  /**
   * Get information on Acquia Search modules.
   *
   * @return array|null
   *   Versions for enabled search modules, NULL otherwise.
   */
  protected function getSearchModulesData(): ?array {

    // This is the only search module compatible with this version of Acquia
    // Connector for now.
    if (!\Drupal::moduleHandler()->moduleExists('acquia_search')) {
      return NULL;
    }

    // Include Acquia Search Solr for Search API module version number.
    $modules = ['acquia_search', 'search_api_solr'];
    $result = [];

    foreach ($modules as $name) {
      $extension_list = \Drupal::service('extension.list.module');
      $info = $extension_list->getExtensionInfo($name);
      // Send the version, or at least the core compatibility as a fallback.
      $result[$name] = isset($info['version']) ? (string) $info['version'] : (string) $info['core_version_requirement'];
    }

    return $result;

  }

  /**
   * Get Acquia subscription from Acquia.
   *
   * @param string $id
   *   Acquia Subscription ID.
   * @param string $key
   *   Acquia Subscription key.
   * @param array $body
   *   Optional.
   *
   * @return array|false
   *   Response result or FALSE.
   */
  public function sendNspi($id, $key, array $body = []) {
    $body['identifier'] = $id;

    try {
      $response = $this->nspiCall('/spi-api/site', $body);
      if (!empty($response['result']['authenticator']) && $this->validateResponse($key, $response['result'], $response['authenticator'])) {
        return $response['result'];
      }
    }
    catch (ConnectorException $e) {
      $this->getLogger('acquia connector')->error('Error: ' . $e->getCustomMessage());
    }
    return FALSE;
  }

  /**
   * Get SPI definition.
   *
   * @param string $apiEndpoint
   *   API endpoint.
   *
   * @return array|bool
   *   Definition array or FALSE.
   */
  public function getDefinition($apiEndpoint) {
    try {
      return $this->request('GET', $apiEndpoint, []);
    }
    catch (ConnectorException $e) {
      $this->getLogger('acquia connector')->error($e->getCustomMessage());
    }
    return FALSE;
  }

  /**
   * Validate the response authenticator.
   *
   * @param string $key
   *   Acquia Subscription key.
   * @param array $response
   *   Response.
   * @param array $requestAuthenticator
   *   Authenticator array.
   *
   * @return bool
   *   TRUE if valid response, FALSE otherwise.
   */
  protected function validateResponse($key, array $response, array $requestAuthenticator) {
    $responseAuthenticator = $response['authenticator'];
    if (!($requestAuthenticator['nonce'] === $responseAuthenticator['nonce'] && $requestAuthenticator['time'] < $responseAuthenticator['time'])) {
      return FALSE;
    }
    $hash = $this->hash($key, $responseAuthenticator['time'], $responseAuthenticator['nonce']);
    return ($hash === $responseAuthenticator['hash']);
  }

  /**
   * Create and send a request.
   *
   * @param string $method
   *   Method to call.
   * @param string $path
   *   Path to call.
   * @param array $data
   *   Data to send.
   *
   * @return array|false
   *   Response array or FALSE.
   *
   * @throws \Drupal\acquia_connector\ConnectorException
   */
  protected function request($method, $path, array $data) {
    $uri = $this->server . $path;
    $options = [
      'headers' => $this->headers,
      'body' => Json::encode($data),
    ];

    try {
      switch ($method) {
        case 'GET':
          $response = $this->client->get($uri);
          $status_code = $response->getStatusCode();
          $stream_size = $response->getBody()->getSize();
          $data = Json::decode($response->getBody()->read($stream_size));

          if ($status_code < 200 || $status_code > 299) {
            throw new ConnectorException($data['message'], $data['code'], $data);
          }

          return $data;

        case 'POST':
          $response = $this->client->post($uri, $options);
          $status_code = $response->getStatusCode();
          $stream_size = $response->getBody()->getSize();
          $data = Json::decode($response->getBody()->read($stream_size));

          if ($status_code < 200 || $status_code > 299) {
            throw new ConnectorException($data['message'], $data['code'], $data);
          }

          return $data;

      }
    }
    catch (RequestException $e) {
      throw new ConnectorException($e->getMessage(), $e->getCode());
    }

    return FALSE;
  }

  /**
   * Build authenticator to sign requests to the Acquia.
   *
   * @param string $key
   *   Secret key to use for signing the request.
   * @param int $request_time
   *   Such as from \Drupal::time()->getRequestTime().
   * @param array $params
   *   Optional parameters to include.
   *   'identifier' - Network Identifier.
   *
   * @return array
   *   Authenticator array.
   */
  protected function buildAuthenticator($key, int $request_time, array $params = []) {
    $authenticator = [];
    if (isset($params['identifier'])) {
      // Put Subscription ID in authenticator but do not use in hash.
      $authenticator['identifier'] = $params['identifier'];
      unset($params['identifier']);
    }
    $nonce = $this->getNonce();
    $authenticator['time'] = $request_time;
    $authenticator['hash'] = $this->hash($key, $request_time, $nonce);
    $authenticator['nonce'] = $nonce;

    return $authenticator;
  }

  /**
   * Calculates a HMAC-SHA1 according to RFC2104.
   *
   * @param string $key
   *   Key.
   * @param int $time
   *   Timestamp.
   * @param string $nonce
   *   Nonce.
   *
   * @return string
   *   HMAC-SHA1 hash.
   *
   * @see http://www.ietf.org/rfc/rfc2104.txt
   */
  protected function hash($key, $time, $nonce) {
    $string = $time . ':' . $nonce;
    return CryptConnector::acquiaHash($key, $string);
  }

  /**
   * Get a random base 64 encoded string.
   *
   * @return string
   *   Random base 64 encoded string.
   */
  protected function getNonce() {
    return Crypt::hashBase64(uniqid(mt_rand(), TRUE) . random_bytes(55));
  }

  /**
   * Prepare and send a REST request to Acquia with an authenticator.
   *
   * @param string $method
   *   HTTP method.
   * @param array $params
   *   Parameters to pass to the NSPI.
   * @param string $key
   *   Acquia Key or NULL.
   *
   * @return array
   *   NSPI response.
   *
   * @throws \Drupal\acquia_connector\ConnectorException
   */
  public function nspiCall(string $method, array $params, string $key = NULL) {
    // Used in HMAC validation.
    $params['rpc_version'] = ACQUIA_CONNECTOR_ACQUIA_SPI_DATA_VERSION;
    $ip = \Drupal::request()->server->get('SERVER_ADDR', '');
    $host = \Drupal::request()->server->get('HTTP_HOST', '');
    $ssl = \Drupal::request()->isSecure();
    $data = [
      'authenticator' => $this->buildAuthenticator($key, $this->time, $params),
      'ip' => $ip,
      'host' => $host,
      'ssl' => $ssl,
      'body' => $params,
    ];
    $data['result'] = $this->request('POST', $method, $data);
    return $data;
  }

}
