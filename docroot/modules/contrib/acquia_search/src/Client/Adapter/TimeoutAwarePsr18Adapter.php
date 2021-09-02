<?php

namespace Drupal\acquia_search\Client\Adapter;

use Http\Factory\Guzzle\RequestFactory;
use Http\Factory\Guzzle\StreamFactory;
use Psr\Http\Client\ClientInterface;
use Solarium\Core\Client\Adapter\AdapterInterface;
use Solarium\Core\Client\Adapter\Psr18Adapter;
use Solarium\Core\Client\Adapter\TimeoutAwareInterface;
use Solarium\Core\Client\Endpoint;
use Solarium\Core\Client\Request;
use Solarium\Core\Client\Response;

/**
 * Psr18 Adapter that implements TimeoutAwareInterface.
 */
class TimeoutAwarePsr18Adapter implements AdapterInterface, TimeoutAwareInterface {

  /**
   * Timeout in seconds.
   *
   * @var int
   */
  protected $timeout;

  /**
   * Solarium Psr18 Adapter.
   *
   * @var \Solarium\Core\Client\Adapter\Psr18Adapter
   */
  protected $psr18Adapter;

  /**
   * Constructor of TimeoutAwarePsr18Adapter.
   *
   * @param \Psr\Http\Client\ClientInterface $httpClient
   *   Guzzle HTTP Client.
   */
  public function __construct(ClientInterface $httpClient) {
    $this->psr18Adapter = new Psr18Adapter(
      $httpClient,
      new RequestFactory(),
      new StreamFactory()
    );
  }

  /**
   * Retrieves adapter timeout.
   *
   * @return int
   *   Timeout in seconds.
   */
  public function getTimeout(): int {
    return $this->timeout;
  }

  /**
   * Sets adapter timeout.
   *
   * @param int $timeoutInSeconds
   *   Timeout in seconds.
   */
  public function setTimeout(int $timeoutInSeconds): void {
    $this->timeout = $timeoutInSeconds;
  }

  /**
   * Executes request.
   *
   * @param \Solarium\Core\Client\Request $request
   *   Solarium Request.
   * @param \Solarium\Core\Client\Endpoint $endpoint
   *   Solarium Endpoint.
   *
   * @return \Solarium\Core\Client\Response
   *   Solarium response object.
   */
  public function execute(Request $request, Endpoint $endpoint): Response {
    $request->setOptions([
      'timeout' => $this->timeout,
    ]);

    return $this->psr18Adapter->execute($request, $endpoint);
  }

}
