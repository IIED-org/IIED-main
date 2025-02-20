<?php

declare(strict_types=1);

namespace Drupal\Tests\acquia_connector\Kernel\Controller;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Logger\RfcLoggerTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\Tests\acquia_connector\Kernel\AcquiaConnectorTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * @coversDefaultClass \Drupal\acquia_connector\Controller\AuthController
 * @group acquia_connector
 */
final class AuthControllerTest extends AcquiaConnectorTestBase implements LoggerInterface {

  use UserCreationTrait;
  use StringTranslationTrait;
  use RfcLoggerTrait;

  /**
   * Tracks logs during the test.
   *
   * @var string[]
   */
  private $logs = [];

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
    parent::register($container);
    $container
      ->register('testing.acquia_conector_logger', self::class)
      ->addTag('logger');
    $container->set('testing.acquia_conector_logger', $this);
  }

  /**
   * {@inheritdoc}
   */
  public function log($level, $message, array $context = []): void {
    $message_placeholders = $this->container
      ->get('logger.log_message_parser')
      ->parseMessagePlaceholders($message, $context);
    $message = empty($message_placeholders) ? $message : strtr($message, $message_placeholders);

    $entry = strtr('!severity|!type|!message', [
      '!type' => $context['channel'],
      '!request_uri' => $context['request_uri'],
      '!severity' => $level,
      '!uid' => $context['uid'],
      '!message' => strip_tags($message),
    ]);
    $this->logs[] = $entry;
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->createUserWithSession();
  }

  /**
   * Tests the ::setup method while oauth is valid.
   */
  public function testSetupWithoauth(): void {
    $request = Request::create(
      Url::fromRoute('acquia_connector.setup_oauth')->toString()
    );
    $response = $this->doRequest($request);
    self::assertEquals(200, $response->getStatusCode());
    // @note: cannot use generated URL to due generated CSRF token.
    $this->assertStringContainsString(
      $this->getCsrfUrlString(Url::fromRoute('acquia_connector.auth.begin')),
      $this->getRawContent()
    );
    $this->assertStringContainsString(
      Url::fromRoute('acquia_connector.setup_manual')->toString(),
      $this->getRawContent()
    );
  }

  /**
   * Tests the ::setup method with an API key.
   */
  public function testSetupWithApiKeys(): void {
    $sut = $this->container->get('acquia_connector.auth_service');
    $client_id_property = new \ReflectionProperty($sut, 'client_id');
    $client_id_property->setAccessible(true);
    $client_id_property->setValue($sut, '38357830-xxxx-4b4d-a356-f508c6ddecf8');

    $request = Request::create(
      Url::fromRoute('acquia_connector.setup_oauth')->toString()
    );
    $response = $this->doRequest($request);
    self::assertEquals(200, $response->getStatusCode());
    // @note: cannot use generated URL to due generated CSRF token.
    $this->assertStringNotContainsString(
      $this->getCsrfUrlString(Url::fromRoute('acquia_connector.auth.begin')),
      $this->getRawContent()
    );
    $this->assertStringContainsString(
      Url::fromRoute('acquia_connector.setup_manual')->toString(),
      $this->getRawContent()
    );

    // Submit the form
    $request = Request::create(
      Url::fromRoute('acquia_connector.setup_oauth')->toString(),
      'POST',
      [
        'api_key' => 'VALID_KEY',
        'api_secret' => 'VALID_SECRET',
        // @phpstan-ignore-next-line
        'form_build_id' => (string) $this->cssSelect('input[name="form_build_id"]')[0]->attributes()->value[0],
        // @phpstan-ignore-next-line
        'form_token' => (string) $this->cssSelect('input[name="form_token"]')[0]->attributes()->value[0],
        // @phpstan-ignore-next-line
        'form_id' => (string) $this->cssSelect('input[name="form_id"]')[0]->attributes()->value[0],
        'op' => 'Connect',
      ]);
    $response = $this->doRequest($request);
    self::assertEquals(303, $response->getStatusCode(), var_export($response->getContent(), TRUE));
    self::assertEquals(
      Url::fromRoute('acquia_connector.setup_configure')->setAbsolute()->toString(),
      $response->headers->get('Location')
    );
    // Ensure keys are being stored locally.
    $state = $this->container->get('state');
    $this->assertEquals('{"api_key":"VALID_KEY","api_secret":"VALID_SECRET"}', $state->get('acquia_connector.credentials', ''));
  }

  /**
   * Tests the ::begin method.
   */
  public function testBegin(): void {
    $request = Request::create(
      $this->getCsrfUrlString(Url::fromRoute('acquia_connector.auth.begin'))
    );
    $response = $this->doRequest($request);
    self::assertEquals(302, $response->getStatusCode());
    self::assertTrue($response->headers->has('Location'));
    $url = UrlHelper::parse($response->headers->get('Location'));
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
   * Tests the ::return method.
   *
   * @dataProvider authorizationReturnData
   */
  public function testReturn(string $code, string $error = ''): void {
    $request = Request::create(
      $this->getCsrfUrlString(Url::fromRoute('acquia_connector.auth.begin'))
    );
    $response = $this->doRequest($request);
    $location = UrlHelper::parse($response->headers->get('Location'));
    $state = $location['query']['state'] ?? '';

    $request = Request::create(
      Url::fromRoute('acquia_connector.auth.return')->toString(),
      'GET',
      ['code' => $code, 'state' => $state]
    );
    $response = $this->doRequest($request);
    self::assertEquals(302, $response->getStatusCode());
    self::assertTrue($response->headers->has('Location'));
    if ($error === '') {
      self::assertEquals(
        Url::fromRoute('acquia_connector.setup_configure')->toString(),
        $response->headers->get('Location')
      );
    }
    else {
      self::assertEquals(
        Url::fromRoute('acquia_connector.setup_oauth')->toString(),
        $response->headers->get('Location')
      );
      $error_msg = $this->container->get('messenger')->messagesByType('error');
      self::assertSame(
        'We could not retrieve account data, please re-authorize with your Acquia Cloud account. For more information check <a target="_blank" href="https://docs.acquia.com/cloud-platform/known-issues/#unable-to-log-in-through-acquia-connector">this link</a>.',
        (string) array_shift($error_msg)
      );
      self::assertEquals(
        [$error],
        $this->logs
      );
    }
  }

  /**
   * Return data for the authorization responses.
   *
   * @return \Generator
   *   The test data.
   */
  public static function authorizationReturnData(): \Generator {
    yield 'success' => ['AUTHORIZATION_SUCCESSFUL'];
    yield 'error' => [
      'AUTHORIZATION_ERROR',
      '3|acquia_connector|Unable to finalize OAuth handshake with Acquia Cloud: Client error: `POST https://accounts.acquia.com/api/auth/oauth/token` resulted in a `400 Bad Request` response:
{"error":"invalid_grant","error_description":"Authorization code doesn\'t exist or is invalid for the client"}',
    ];
  }

  /**
   * Tests that ::return fails if the state parameter does not match.
   */
  public function testReturnInvalidState(): void {
    $request = Request::create(
      Url::fromRoute('acquia_connector.auth.return')->toString(),
      'GET',
      ['code' => 'AUTHORIZATION_SUCCESSFUL', 'state' => 'foo']
    );
    $response = $this->doRequest($request);
    self::assertEquals(302, $response->getStatusCode());
    self::assertEquals(
      Url::fromRoute('acquia_connector.setup_oauth')->toString(),
      $response->headers->get('Location')
    );
    $error_msg = $this->container->get('messenger')->messagesByType('error');
    self::assertEquals(
      'We could not retrieve account data, please re-authorize with your Acquia Cloud account. For more information check <a target="_blank" href="https://docs.acquia.com/cloud-platform/known-issues/#unable-to-log-in-through-acquia-connector">this link</a>.',
      (string) array_shift($error_msg)
    );
    self::assertEquals(
      [
        '3|acquia_connector|Unable to finalize OAuth handshake with Acquia Cloud: Could not verify state',
      ],
      $this->logs
    );
  }

}
