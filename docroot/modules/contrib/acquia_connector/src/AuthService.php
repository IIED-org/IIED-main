<?php

declare(strict_types=1);

namespace Drupal\acquia_connector;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Crypt;
use Drupal\Core\Access\CsrfTokenGenerator;
use Drupal\Core\Http\ClientFactory;
use Drupal\Core\KeyValueStore\KeyValueExpirableFactoryInterface;
use Drupal\Core\Site\Settings as CoreSettings;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Url;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Uri;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Auth service for OAuth authentication with Acquia Cloud.
 */
final class AuthService {

  private const CLIENT_ID = '38357830-bacd-4b4d-a356-f508c6ddecf8';
  private const CSRF_TOKEN_KEY = 'acquia_connector_oauth_state';
  private const PKCE_KEY = 'acquia_connector_pkce_code';

  /**
   * The CSRF token generator.
   *
   * @var \Drupal\Core\Access\CsrfTokenGenerator
   */
  private CsrfTokenGenerator $csrfToken;

  /**
   * The HTTP client factory.
   *
   * @var \Drupal\Core\Http\ClientFactory
   */
  private ClientFactory $clientFactory;

  /**
   * The session.
   *
   * @var \Symfony\Component\HttpFoundation\Session\SessionInterface
   */
  private SessionInterface $session;

  /**
   * The factory for expirable key value stores.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueExpirableFactoryInterface
   */
  private KeyValueExpirableFactoryInterface $keyValueExpirableFactory;

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  private StateInterface $state;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  private TimeInterface $time;

  /**
   * Constructs a new AuthService object.
   *
   * @param \Drupal\Core\Access\CsrfTokenGenerator $csrf_token
   *   The CSRF token generator.
   * @param \Drupal\Core\Http\ClientFactory $client_factory
   *   The HTTP client factory.
   * @param \Symfony\Component\HttpFoundation\Session\SessionInterface $session
   *   The session.
   * @param \Drupal\Core\KeyValueStore\KeyValueExpirableFactoryInterface $key_value_expirable_factory
   *   The factory for expirable key value stores.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   */
  public function __construct(CsrfTokenGenerator $csrf_token, ClientFactory $client_factory, SessionInterface $session, KeyValueExpirableFactoryInterface $key_value_expirable_factory, StateInterface $state, TimeInterface $time) {
    $this->csrfToken = $csrf_token;
    $this->clientFactory = $client_factory;
    $this->session = $session;
    $this->keyValueExpirableFactory = $key_value_expirable_factory;
    $this->state = $state;
    $this->time = $time;
  }

  /**
   * Get the authorization URL.
   *
   * @return \Drupal\Core\Url
   *   The URL.
   */
  public function getAuthUrl(): Url {
    $params = [
      'response_type' => 'code',
      'client_id' => self::CLIENT_ID,
      'redirect_uri' => Url::fromRoute('acquia_connector.auth.return')->setAbsolute()->toString(),
      'state' => $this->getStateToken(),
      'code_challenge' => Crypt::hashBase64($this->getPkceCode()),
      'code_challenge_method' => 'S256',
    ];
    $uri = (new Uri())
      ->withScheme('https')
      ->withHost(self::getIdpHost())
      ->withPath('/api/auth/oauth/authorize');
    return Url::fromUri(
      (string) Uri::withQueryValues($uri, $params)
    );
  }

  /**
   * Finalizes the OAuth process.
   *
   * @param string $code
   *   The authorization code.
   * @param string $state
   *   The state token.
   */
  public function finalize(string $code, string $state): void {
    if ($state !== $this->getStateToken()) {
      throw new \RuntimeException('Could not verify state');
    }
    $client = $this->clientFactory->fromOptions([
      'base_uri' => (new Uri())
        ->withScheme('https')
        ->withHost(self::getIdpHost()),
    ]);
    $response = $client->post('/api/auth/oauth/token', [
      'json' => [
        'grant_type' => 'authorization_code',
        'code' => $code,
        'client_id' => self::CLIENT_ID,
        'redirect_uri' => Url::fromRoute('acquia_connector.auth.return')->setAbsolute()->toString(),
        'code_verifier' => $this->getPkceCode(),
      ],
    ]);
    $this->keyValueExpirableFactory->get('acquia_connector')->setWithExpire(
      'oauth',
      Json::decode((string) $response->getBody()),
      5400
    );
    $this->session->remove(self::PKCE_KEY);
  }

  /**
   * Refreshes the access token.
   */
  public function refreshAccessToken(): void {
    $access_data = $this->getAccessToken();
    $client = $this->clientFactory->fromOptions([
      'base_uri' => (new Uri())
        ->withScheme('https')
        ->withHost(self::getIdpHost()),
    ]);
    $response = $client->post('/api/auth/oauth/token', [
      'json' => [
        'grant_type' => 'refresh_token',
        'refresh_token' => $access_data['refresh_token'] ?? '',
        'client_id' => self::CLIENT_ID,
      ],
    ]);
    $access_data = Json::decode((string) $response->getBody());
    $this->keyValueExpirableFactory->get('acquia_connector')->setWithExpire(
      'oauth',
      $access_data,
      5400
    );
  }

  /**
   * Gets the access token data.
   *
   * @phpstan-return array{access_token: string, refresh_token: string, expires: int}
   *
   * @return array|null
   *   The access token data, or NULL if not set.
   */
  public function getAccessToken(): ?array {
    return $this->keyValueExpirableFactory->get('acquia_connector')->get('oauth');
  }

  /**
   * Cron refresh of the access token.
   */
  public function cronRefresh(): void {
    $last_refresh_timestamp = $this->state->get('acquia_connector.oauth_refresh.timestamp', 0);
    if ($this->time->getCurrentTime() - $last_refresh_timestamp > 1800) {
      try {
        $this->refreshAccessToken();
      }
      catch (RequestException $exception) {
      } finally {
        $this->state->set('acquia_connector.oauth_refresh.timestamp', $this->time->getRequestTime());
      }
    }
  }

  /**
   * Gets the state token value used in OAuth authorization.
   *
   * @return string
   *   The state token.
   */
  private function getStateToken(): string {
    return Crypt::hashBase64($this->csrfToken->get(self::CSRF_TOKEN_KEY));
  }

  /**
   * Get the PKCE code used in the OAuth authorization.
   *
   * @return string
   *   The PKCE code.
   */
  private function getPkceCode(): string {
    if (!$this->session->has(self::PKCE_KEY)) {
      $this->session->set(self::PKCE_KEY, Crypt::randomBytesBase64(64));
    }
    return $this->session->get(self::PKCE_KEY);
  }

  /**
   * Get the identity provider host.
   *
   * @return string
   *   The host.
   */
  private static function getIdpHost(): string {
    return CoreSettings::get('acquia_connector.idp_host', 'accounts.acquia.com');
  }

}
