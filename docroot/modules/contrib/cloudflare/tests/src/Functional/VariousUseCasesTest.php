<?php

namespace Drupal\Tests\cloudflare\Functional;

use Drupal\Tests\BrowserTestBase;
use GuzzleHttp\Cookie\CookieJar;
use Symfony\Component\HttpFoundation\Request;

/**
 * Test authentication support and intermediaries.
 *
 * Based on Drupal's functional SessionHttpsTest::testHttpsSession().
 *
 * @group cloudflare
 */
class VariousUseCasesTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['cloudflare'];

  /**
   * The name of the session cookie when using HTTPS.
   *
   * @var string
   */
  protected $secureSessionName;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $request = Request::createFromGlobals();
    if ($request->isSecure()) {
      $this->secureSessionName = $this->getSessionName();
    }
    else {
      $this->secureSessionName = 'S' . $this->getSessionName();
    }
    $this->container->get('config.factory')->getEditable('cloudflare.settings')
      ->set('client_ip_restore_enabled', TRUE)
      ->set('remote_addr_validate', FALSE)
      ->save();
  }

  /**
   * Test user login for intermediaries and HTTPS over Cloudflare.
   *
   * Test that authenticated user will receive session cookie with secure flag
   * set and will be redirected to the HTTPS website version when connected over
   * CloudFlare in flexible encryption mode or when there are intermediaries
   * between CloudFlare and the origin server.
   */
  public function testAuthenticationSupport() {
    $this->assertTrue($this->config('cloudflare.settings')->get('client_ip_restore_enabled'), 'Restore client IP address function is enabled');
    $this->assertFalse($this->config('cloudflare.settings')->get('remote_addr_validate'), 'Validation of remote IP address is disabled');

    $account = $this->drupalCreateUser(['access administration pages']);
    $guzzle_cookie_jar = $this->getGuzzleCookieJar();
    $post = [
      'form_id' => 'user_login_form',
      'form_build_id' => $this->getUserLoginFormBuildId(),
      'name' => $account->getAccountName(),
      'pass' => $account->passRaw,
      'op' => 'Log in',
    ];
    $url = $this->buildUrl($this->httpUrl('user/login'));
    // When posting directly to the HTTP or http mock front controller, the
    // location header on the returned response is an absolute URL. That URL
    // needs to be converted into a request to the respective mock front
    // controller in order to retrieve the target page. Because the URL in the
    // location header needs to be modified, it is necessary to disable the
    // automatic redirects normally performed by the Guzzle CurlHandler.
    /** @var \Psr\Http\Message\ResponseInterface $response */
    $response = $this->getHttpClient()->post($url, [
      'form_params' => $post,
      'http_errors' => FALSE,
      'cookies' => $guzzle_cookie_jar,
      'allow_redirects' => FALSE,
      // Mock CloudFlare headers.
      'headers' => [
        'CF-Connecting-IP' => '127.0.0.11',
        'CF-Visitor' => '{"scheme":"https"}',
      ],
    ]);

    $this->assertEquals(303, $response->getStatusCode(), 'User is redirected to the profile page');
    $this->assertStringStartsWith('https', $response->getHeader('location')[0], 'Location header contains expected HTTPS scheme');

    $cookie = $guzzle_cookie_jar->getCookieByName($this->secureSessionName);
    $this->assertTrue(is_a($cookie, 'GuzzleHttp\Cookie\SetCookie'), 'The secure cookie exists');
    $this->assertTrue($cookie->getSecure(), 'The secure cookie has the secure attribute');
  }

  /**
   * Creates a new Guzzle CookieJar with a Xdebug cookie if necessary.
   *
   * @return \GuzzleHttp\Cookie\CookieJar
   *   The Guzzle CookieJar.
   */
  protected function getGuzzleCookieJar() {
    $cookies = $this->extractCookiesFromRequest(\Drupal::request());
    foreach ($cookies as $cookie_name => $values) {
      $cookies[$cookie_name] = $values[0];
    }
    return CookieJar::fromArray($cookies, $this->baseUrl);
  }

  /**
   * Gets the form build ID for the user login form.
   *
   * @return string
   *   The form build ID for the user login form.
   */
  protected function getUserLoginFormBuildId(): string {
    $this->drupalGet('user/login');
    return (string) $this->getSession()->getPage()->findField('form_build_id');
  }

  /**
   * Builds a URL for submitting a mock HTTPS request to HTTP test environments.
   *
   * @param string $url
   *   A Drupal path such as 'user/login'.
   *
   * @return string
   *   URL prepared for the https.php mock front controller.
   */
  protected function httpsUrl(string $url): string {
    return 'core/modules/system/tests/https.php/' . $url;
  }

  /**
   * Builds a URL for submitting a mock HTTP request to HTTPS test environments.
   *
   * @param string $url
   *   A Drupal path such as 'user/login'.
   *
   * @return string
   *   URL prepared for the http.php mock front controller.
   */
  protected function httpUrl(string $url): string {
    return 'core/modules/system/tests/http.php/' . $url;
  }

}
