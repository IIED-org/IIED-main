<?php

declare(strict_types=1);

namespace Drupal\Tests\acquia_connector\Kernel;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Url;
use GuzzleHttp\Exception\ClientException;

/**
 * @coversDefaultClass \Drupal\acquia_connector\AuthService
 * @group acquia_connector
 */
final class AuthServiceTest extends AcquiaConnectorTestBase {

  /**
   * Tests getAuthUrl.
   *
   * @covers ::getAuthUrl
   * @covers ::getPkceCode
   * @covers ::getStateToken
   */
  public function testGetAuthUrl(): void {
    $sut = $this->container->get('acquia_connector.auth_service');
    $url = UrlHelper::parse($sut->getAuthUrl()->toString());
    self::assertEquals(
      'https://accounts.acquia.com/api/auth/oauth/authorize',
      $url['path']
    );
    self::assertEquals([
      'response_type',
      'client_id',
      'redirect_uri',
      'state',
      'code_challenge',
      'code_challenge_method',
    ], array_keys($url['query']));
    self::assertEquals('code', $url['query']['response_type']);
    self::assertEquals('38357830-bacd-4b4d-a356-f508c6ddecf8', $url['query']['client_id']);
    self::assertEquals(
      Url::fromRoute('acquia_connector.auth.return')
        ->setAbsolute()
        ->toString(),
      $url['query']['redirect_uri']);
    self::assertEquals('S256', $url['query']['code_challenge_method']);
  }

  /**
   * Tests finalize with a bad state value.
   *
   * @covers ::finalize
   * @covers ::getStateToken
   */
  public function testFinalizeBadState(): void {
    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('Could not verify state');
    $sut = $this->container->get('acquia_connector.auth_service');
    $sut->finalize('FOO', 'BAR');
  }

  /**
   * Tests finalize.
   *
   * @covers ::finalize
   * @covers ::getAccessToken
   * @covers ::getPkceCode
   * @covers ::getStateToken
   *
   * @dataProvider finalizeData
   */
  public function testFinalize(string $code, ?array $access_token): void {
    if ($access_token === NULL) {
      $this->expectException(ClientException::class);
      $this->expectExceptionMessage('Client error: `POST https://accounts.acquia.com/api/auth/oauth/token` resulted in a `400 Bad Request` response:
{"error":"invalid_grant","error_description":"Authorization code doesn\'t exist or is invalid for the client"}');
    }

    $sut = $this->container->get('acquia_connector.auth_service');
    $session_metadata = $this->container->get('session_manager.metadata_bag');
    $session_metadata->stampNew();
    $csrf_token_seed = $session_metadata->getCsrfTokenSeed();
    // Generating this URL clears out the session seed for some reason.
    $url = UrlHelper::parse($sut->getAuthUrl()->toString());
    $state = $url['query']['state'];

    $session_metadata->setCsrfTokenSeed($csrf_token_seed);
    $sut->finalize($code, $state);
    self::assertEquals(
      $access_token,
      $sut->getAccessToken()
    );
  }

  /**
   * Test data for ::finalize.
   */
  public function finalizeData() {
    yield 'success' => [
      'AUTHORIZATION_SUCCESSFUL',
      [
        'access_token' => 'ACCESS_TOKEN',
        'refresh_token' => 'REFRESH_TOKEN',
      ],
    ];
    yield 'error' => [
      'AUTHORIZATION_ERROR',
      NULL,
    ];
  }

  /**
   * Tests cron refresh of access token.
   *
   * @covers ::cronRefresh
   */
  public function testCron(): void {
    $this->container
      ->get('keyvalue.expirable')
      ->get('acquia_connector')
      ->setWithExpire(
        'oauth',
        [
          'access_token' => 'ACCESS_TOKEN',
          'refresh_token' => 'REFRESH_TOKEN',
        ],
        5400
      );

    $request_timestamp = $this->container->get('datetime.time')->getRequestTime();

    $last_refresh_timestamp = $this->container->get('state')->get('acquia_connector.oauth_refresh.timestamp', 0);
    self::assertEquals(0, $last_refresh_timestamp);
    $sut = $this->container->get('acquia_connector.auth_service');
    $sut->cronRefresh();
    $last_refresh_timestamp = $this->container->get('state')->get('acquia_connector.oauth_refresh.timestamp', 0);
    self::assertEquals($request_timestamp, $last_refresh_timestamp);

    self::assertEquals(
      [
        'access_token' => 'ACCESS_TOKEN_REFRESHED',
        'refresh_token' => 'REFRESH_TOKEN_REFRESHED',
      ],
      $sut->getAccessToken()
    );
  }

}
