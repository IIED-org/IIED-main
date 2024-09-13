<?php

namespace Drupal\varnish_purger\Plugin\Purge\Purger;

use Drupal\purge\Plugin\Purge\Purger\PurgerInterface;
use Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface;
use GuzzleHttp\Pool;

/**
 * HTTP Purger.
 *
 * @PurgePurger(
 *   id = "varnish",
 *   label = @Translation("Varnish Purger"),
 *   configform = "\Drupal\varnish_purger\Form\VarnishPurgerForm",
 *   cooldown_time = 0.0,
 *   description = @Translation("Configurable purger that makes HTTP requests for each given invalidation instruction."),
 *   multi_instance = TRUE,
 *   types = {},
 * )
 */
class VarnishPurger extends VarnishPurgerBase implements PurgerInterface {

  const VARNISH_PURGE_CONCURRENCY = 10;

  /**
   * {@inheritdoc}
   */
  public function invalidate(array $invalidations) {
    // Prepare a generator for the requests that we will be sending out. Use a
    // generator, as the pool implementation will request new item to pass
    // thorough the wire once any of the concurrency slots is free.
    $requests = function() use ($invalidations) {
      $client = $this->client;
      $method = $this->settings->request_method;
      $logger = $this->logger();

      /* @var $invalidation \Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface */
      foreach ($invalidations as $invalidation) {
        $token_data = ['invalidation' => $invalidation];
        $uri = $this->getUri($token_data);
        $options = $this->getOptions($token_data);

        yield function() use ($client, $uri, $method, $options, $invalidation, $logger) {
          return $client->requestAsync($method, $uri, $options)->then(
            // Handle the positive case.
            function ($response) use ($invalidation) {
              $invalidation->setState(InvalidationInterface::SUCCEEDED);
            },
            // Handle the negative case.
            function ($reason) use ($invalidation, $uri, $options, $logger) {
              $invalidation->setState(InvalidationInterface::FAILED);

              $message = $reason instanceof \Exception ? $reason->getMessage() : (string) $reason;

              // Log as much useful information as we can.
              $headers = $options['headers'];
              unset($options['headers']);
              $debug = json_encode([
                'msg' => str_replace("\n", ' ', $message),
                'uri' => $uri,
                'method' => $this->settings->request_method,
                'guzzle_opt' => $options,
                'headers' => $headers,
              ]);
              $logger->emergency("item failed due @e, details (JSON): @debug", [
                '@e' => is_object($reason) ? get_class($reason) : (string) $reason,
                '@debug' => $debug,
              ]);
            }
          );
        };
      }
    };

    // Prepare a POOL that will make the requests with a given concurrency.
    (new Pool($this->client, $requests(), ['concurrency' => self::VARNISH_PURGE_CONCURRENCY]))
      ->promise()
      ->wait();
  }

}
