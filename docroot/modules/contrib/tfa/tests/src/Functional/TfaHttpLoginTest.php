<?php

namespace Drupal\Tests\tfa\Functional;

use Drupal\Core\Url;
use GuzzleHttp\Cookie\CookieJar;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Serializer;

/**
 * Tests the tfa user.login.http process.
 *
 * @group Tfa
 */
class TfaHttpLoginTest extends TfaTestBase {

  /**
   * User doing the TFA Validation.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $webUser;

  /**
   * User without TFA enabled or required.
   *
   * @var \Drupal\user\Entity\User|false
   */
  protected $nonTfaUser;

  /**
   * Administrator to handle configurations.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $adminUser;

  /**
   * The serializer.
   *
   * @var \Symfony\Component\Serializer\Serializer
   */
  protected $serializer;

  /**
   * Cookie Storage.
   *
   * @var \GuzzleHttp\Cookie\CookieJar
   */
  protected $cookies;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    $this->webUser = $this->drupalCreateUser(['setup own tfa']);
    $this->nonTfaUser = $this->drupalCreateUser();
    $this->adminUser = $this->drupalCreateUser(['admin tfa settings']);

    $this->canEnableValidationPlugin('tfa_test_plugins_validation');

    $this->cookies = new CookieJar();
    $encoders = [new JsonEncoder(), new XmlEncoder()];
    $this->serializer = new Serializer([], $encoders);
  }

  /**
   * Tests the protection of the user.login.http route.
   */
  public function testHttpRouteLogin() {
    $format = 'json';
    $assert_session = $this->assertSession();

    // Enable TFA for the webUser role only.
    $this->drupalLogin($this->adminUser);
    $web_user_roles = $this->webUser->getRoles(TRUE);
    $edit = [
      'tfa_required_roles[' . $web_user_roles[0] . ']' => TRUE,
    ];
    $this->drupalGet('admin/config/people/tfa');
    $this->submitForm($edit, 'Save configuration');
    $assert_session->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('The configuration options have been saved.');

    $this->drupalLogout();

    /* Validate that abnormal requests are handled properly. */
    // No username.
    $result = $this->loginRequest(NULL, '12345');
    $this->assertHttpResponseWithMessage($result, 400, 'Missing credentials.name.', $format);
    // Non-existent user.
    $result = $this->loginRequest("DoesNotExist", '12345');
    $this->assertHttpResponseWithMessage($result, 400, 'Sorry, unrecognized username or password.', $format);

    // Non-TFA users can still log in.
    $result = $this->loginRequest($this->nonTfaUser->getAccountName(), $this->nonTfaUser->passRaw);
    $this->assertEquals(200, $result->getStatusCode());
    $result_data = $this->serializer->decode($result->getBody(), $format);
    $this->assertArrayHasKey('logout_token', $result_data);

    // Users that require TFA are rejected.
    $result = $this->loginRequest($this->webUser->getAccountName(), $this->webUser->passRaw);
    $this->assertHttpResponseWithMessage($result, 403, 'The user has not been activated or is blocked.', $format);

  }

  /**
   * Executes a login HTTP request for a given serialization format.
   *
   * Copied from Drupal Core UserLoginHttpTest.php.
   *
   * @param string|null $name
   *   The username.
   * @param string|null $pass
   *   The user password.
   * @param string $format
   *   The format to use to make the request.
   *
   * @return \Psr\Http\Message\ResponseInterface
   *   The HTTP response.
   */
  protected function loginRequest(?string $name, ?string $pass, $format = 'json') {
    $this->cookies = new CookieJar();

    $user_login_url = Url::fromRoute('user.login.http')
      ->setRouteParameter('_format', $format)
      ->setAbsolute();

    $request_body = [];
    if (isset($name) && $name != NULL) {
      $request_body['name'] = $name;
    }
    if (isset($pass) && $pass != NULL) {
      $request_body['pass'] = $pass;
    }

    $result = \Drupal::httpClient()->post($user_login_url->toString(), [
      'body' => $this->serializer->encode($request_body, $format),
      'headers' => [
        'Accept' => "application/$format",
      ],
      'http_errors' => FALSE,
      'cookies' => $this->cookies,
    ]);
    return $result;
  }

  /**
   * Checks a response for status code and message.
   *
   * @param \Psr\Http\Message\ResponseInterface $response
   *   The response object.
   * @param int $expected_code
   *   The expected status code.
   * @param string $expected_message
   *   The expected message encoded in response.
   * @param string $format
   *   The format that the response is encoded in.
   *
   * @internal
   */
  protected function assertHttpResponseWithMessage(ResponseInterface $response, int $expected_code, string $expected_message, string $format = 'json'): void {
    $this->assertEquals($expected_code, $response->getStatusCode());
    $this->assertEquals($expected_message, $this->getResultValue($response, 'message', $format));
  }

  /**
   * Gets a value for a given key from the response.
   *
   * @param \Psr\Http\Message\ResponseInterface $response
   *   The response object.
   * @param string $key
   *   The key for the value.
   * @param string $format
   *   The encoded format.
   *
   * @return mixed
   *   The value for the key.
   */
  protected function getResultValue(ResponseInterface $response, $key, $format) {
    $decoded = $this->serializer->decode((string) $response->getBody(), $format);
    if (is_array($decoded)) {
      return $decoded[$key];
    }
    else {
      return $decoded->{$key};
    }
  }

}
