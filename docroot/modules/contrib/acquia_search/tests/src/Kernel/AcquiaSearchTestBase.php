<?php

declare(strict_types=1);

namespace Drupal\Tests\acquia_search\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Test base class for acquia_search.
 */
abstract class AcquiaSearchTestBase extends KernelTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'text',
    'filter',
    'node',
    'acquia_connector',
    // @todo leverage the test middleware?
    // 'acquia_connector_test',
    'search_api',
    'search_api_solr',
    'acquia_search',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installEntitySchema('search_api_task');
    // Burn uid:1.
    $this->createUser();
    $this->installConfig([
      'system',
      'field',
      'node',
      'search_api',
      'acquia_connector',
      'acquia_search',
    ]);
  }

  /**
   * Populates Acquia Connector subscription data.
   */
  protected function populateSubscriptionSettings(): void {
    $this->container->get('state')->setMultiple([
      'acquia_connector.identifier' => 'ABC',
      'acquia_connector.key' => 'DEF',
      'acquia_connector.application_uuid' => 'a47ac10b-58cc-4372-a567-0e02b2c3d470',
    ]);
    $this->container->get('acquia_connector.subscription')->populateSettings();
  }

  /**
   * Passes a request to the HTTP kernel and returns a response.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response.
   *
   * @throws \Exception
   */
  protected function doRequest(Request $request): Response {
    $response = $this->container->get('http_kernel')->handle($request);
    $content = $response->getContent();
    self::assertNotFalse($content);
    $this->setRawContent($content);
    $this->parse();
    return $response;
  }

}
