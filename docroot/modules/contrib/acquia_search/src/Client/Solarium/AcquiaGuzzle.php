<?php

namespace Drupal\acquia_search\Client\Solarium;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\HandlerStack;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Acquia-specific extension of the Guzzle http query class.
 *
 * @package \Drupal\acquia_search
 */
class AcquiaGuzzle extends Client implements ClientInterface {

  /**
   * Class Constructor.
   */
  public function __construct(?HandlerStack $stack = NULL) {
    if ($stack === NULL) {
      $stack = new HandlerStack();
      $stack->setHandler(new CurlHandler());
    }

    $config = [
      'http_errors' => FALSE,
      // Putting `?debug=true` at the end of any Solr url will show you the
      // low-level debugging from guzzle.
      // phpcs:ignore
      'debug' => (getenv('ACQUIA_GUZZLE_DEBUG') !== FALSE) || isset($_GET['debug']),
      'verify' => FALSE,
      'handler' => $stack,
      'allow_redirects' => FALSE,
    ];
    parent::__construct($config);
  }

  /**
   * Send a guzzle request.
   *
   * @param \Psr\Http\Message\RequestInterface $request
   *   A PSR 7 request.
   *
   * @return \Psr\Http\Message\ResponseInterface
   *   Response from the guzzle send.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function sendRequest(RequestInterface $request): ResponseInterface {
    return $this->send($request);
  }

}
