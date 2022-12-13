<?php

declare(strict_types=1);

namespace Drupal\Tests\acquia_connector\Kernel\Form;

use Drupal\Core\Url;
use Drupal\Tests\acquia_connector\Kernel\AcquiaConnectorTestBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests configure application form.
 *
 * @coversDefaultClass \Drupal\acquia_connector\Form\ConfigureApplicationForm
 * @group acquia_connector
 */
final class ConfigureApplicationFormTest extends AcquiaConnectorTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->createUserWithSession();
  }

  public function testWithNoAuth(): void {
    $request = Request::create(
      Url::fromRoute('acquia_connector.setup_configure')->toString()
    );
    $response = $this->doRequest($request);
    self::assertEquals(302, $response->getStatusCode());
    self::assertEquals(
      Url::fromRoute('acquia_connector.setup_oauth')->toString(),
      $response->headers->get('Location')
    );
    self::assertEquals(
      ['We could not retrieve account data, please re-authorize with your Acquia Cloud account'],
      $this->container->get('messenger')->messagesByType('error')
    );
  }

  public function testWithErrorGettingApplicationKeys(): void {
    $this->setAccessToken('ACCESS_TOKEN_ERROR_GETTING_APPLICATION_KEYS');

    $request = Request::create(
      Url::fromRoute('acquia_connector.setup_configure')->toString()
    );
    $response = $this->doRequest($request);
    self::assertEquals(303, $response->getStatusCode());
    self::assertEquals(
      Url::fromRoute('acquia_connector.setup_oauth')->setAbsolute()->toString(),
      $response->headers->get('Location')
    );
    self::assertEquals(
      ['We encountered an error when retrieving information for the selected application. Try logging into Acquia Cloud again.'],
      $this->container->get('messenger')->messagesByType('error')
    );
  }

  public function testWithNoApplications(): void {
    $this->setAccessToken('ACCESS_TOKEN_NO_APPLICATIONS');

    $request = Request::create(
      Url::fromRoute('acquia_connector.setup_configure')->toString()
    );
    $response = $this->doRequest($request);
    self::assertEquals(302, $response->getStatusCode());
    self::assertEquals(
      Url::fromRoute('acquia_connector.setup_oauth')->toString(),
      $response->headers->get('Location')
    );
    self::assertEquals(
      ['No subscriptions were found for your account.'],
      $this->container->get('messenger')->messagesByType('error')
    );
  }

  public function testWithOneApplication(): void {
    $this->setAccessToken('ACCESS_TOKEN_ONE_APPLICATION');

    $request = Request::create(
      Url::fromRoute('acquia_connector.setup_configure')->toString()
    );
    $response = $this->doRequest($request);
    self::assertEquals(303, $response->getStatusCode());
    self::assertEquals(
      Url::fromRoute('acquia_connector.settings')->setAbsolute()->toString(),
      $response->headers->get('Location')
    );
    self::assertEquals(
      ['status' => ['<h3>Connection successful!</h3>You are now connected to Acquia Cloud.']],
      $this->container->get('messenger')->all()
    );

    self::assertEquals(
      [
        'active' => TRUE,
        'href' => '',
        'uuid' => 'a47ac10b-58cc-4372-a567-0e02b2c3d470',
        'subscription_name' => 'Sample subscription',
        'expiration_date' => [
          'value' => '2030-05-12T00:00:00',
        ],
        'product' => [
          'view' => 'Acquia Network',
        ],
        'search_service_enabled' => 1,
        'gratis' => FALSE,
      ],
      $this->container->get('acquia_connector.subscription')->getSubscription()
    );
  }

  public function testWithMultipleApplications(): void {
    $this->setAccessToken('ACCESS_TOKEN_MULTIPLE_APPLICATIONS');

    $request = Request::create(
      Url::fromRoute('acquia_connector.setup_configure')->toString()
    );
    $response = $this->doRequest($request);
    self::assertEquals(200, $response->getStatusCode());
    $request = Request::create(
      Url::fromRoute('acquia_connector.setup_configure')->toString(),
      'POST',
      [
        'application' => 'a47ac10b-58cc-4372-a567-0e02b2c3d470',
        // @phpstan-ignore-next-line
        'form_build_id' => (string) $this->cssSelect('input[name="form_build_id"]')[0]->attributes()->value[0],
        // @phpstan-ignore-next-line
        'form_token' => (string) $this->cssSelect('input[name="form_token"]')[0]->attributes()->value[0],
        // @phpstan-ignore-next-line
        'form_id' => (string) $this->cssSelect('input[name="form_id"]')[0]->attributes()->value[0],
        'op' => 'Save',
      ]);
    $response = $this->doRequest($request);
    self::assertEquals(303, $response->getStatusCode(), $this->content);
    self::assertEquals(
      Url::fromRoute('acquia_connector.settings')->setAbsolute()->toString(),
      $response->headers->get('Location')
    );
    self::assertEquals(
      ['status' => ['<h3>Connection successful!</h3>You are now connected to Acquia Cloud.']],
      $this->container->get('messenger')->all()
    );

    self::assertEquals(
      [
        'active' => TRUE,
        'href' => '',
        'uuid' => 'a47ac10b-58cc-4372-a567-0e02b2c3d470',
        'subscription_name' => 'Sample subscription',
        'expiration_date' => [
          'value' => '2030-05-12T00:00:00',
        ],
        'product' => [
          'view' => 'Acquia Network',
        ],
        'search_service_enabled' => 1,
        'gratis' => FALSE,
      ],
      $this->container->get('acquia_connector.subscription')->getSubscription()
    );
  }

  /**
   * Sets the access token.
   *
   * @param string $token
   *   The token.
   */
  private function setAccessToken(string $token): void {
    $this->container
      ->get('keyvalue.expirable')
      ->get('acquia_connector')
      ->setWithExpire(
        'oauth',
        [
          'access_token' => $token,
          'refresh_token' => 'REFRESH_TOKEN',
        ],
        5400
      );
  }

}
