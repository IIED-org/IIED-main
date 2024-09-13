<?php

namespace Drupal\varnish_purger\Plugin\Purge\Purger;

use Drupal\Core\Site\Settings;
use Drupal\varnish_purger\DebugCallGraphTrait;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Uri;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\purge\Plugin\Purge\Purger\PurgerBase;
use Drupal\purge\Plugin\Purge\Purger\PurgerInterface;
use Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface;

/**
 * A purger with minimal configuration required.
 *
 * This purger requires that every Varnish server is configured as a reverse
 * proxy in Drupal's settings. As well, it expects to be used with the included
 * "zeroconfig.vcl" file, though it's expected that is modified as needed.
 *
 * This implementation is heavily inspired by the Acquia Cloud purger.
 *
 * @see \Drupal\acquia_purge\Plugin\Purge\Purger\AcquiaCloudPurger
 *
 * @PurgePurger(
 *   id = "varnish_zeroconfig_purger",
 *   label = @Translation("Varnish zero-configuration purger"),
 *   configform = "",
 *   cooldown_time = 0.2,
 *   description = @Translation("Invalidates Varnish powered load balancers."),
 *   multi_instance = FALSE,
 *   types = {"url", "wildcardurl", "tag", "everything"},
 * )
 */
class ZeroConfigPurger extends PurgerBase implements PurgerInterface {
  use DebugCallGraphTrait;

  /**
   * Maximum number of requests to send concurrently.
   */
  const CONCURRENCY = 6;

  /**
   * Float describing the number of seconds to wait while trying to connect to
   * a server.
   */
  const CONNECT_TIMEOUT = 1.5;

  /**
   * Float describing the timeout of the request in seconds.
   */
  const TIMEOUT = 3.0;

  /**
   * Batches of cache tags are split up into multiple requests to prevent HTTP
   * request headers from growing too large or Varnish refusing to process them.
   */
  const TAGS_GROUPED_BY = 15;

  /**
   * The Guzzle HTTP client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $client;

  /**
   * The reverse proxy IP addresses installed in front of this site.
   *
   * @var string[]
   */
  private $reverseProxies = [];

  /**
   * The port the reverse proxies are available on.
   *
   * @var ?int
   */
  private $proxyPort;

  /**
   * Constructs a ZeroConfigPurger object.
   *
   * @param \Drupal\Core\Site\Settings $settings
   *   The site settings to load reverse proxy addresses from.
   * @param \GuzzleHttp\ClientInterface $http_client
   *   An HTTP client that can perform remote requests.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(Settings $settings, ClientInterface $http_client, array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    // Take the IP addresses from the 'reverse_proxies' setting.
    if (is_array($reverse_proxies = $settings->get('reverse_proxy_addresses'))) {
      foreach ($reverse_proxies as $reverse_proxy) {
        if ($reverse_proxy && strpos($reverse_proxy, '.')) {
          $this->reverseProxies[] = $reverse_proxy;
        }
      }
    }

    // Drupal's reverse proxy addresses only supports addresses and not ports.
    // For tests, we need to run Apache on the same host as Varnish, so we need
    // a way to set an alternate port.
    if ($port = $settings->get('varnish_purge_zeroconfig_port')) {
      $this->proxyPort = $port;
    }

    $this->client = $http_client;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('settings'),
      $container->get('http_client'),
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * Retrieve request options used for all purge requests.
   *
   * @param array[] $extra
   *   Associative array of options to merge onto the standard ones.
   *
   * @return array
   */
  protected function getGlobalOptions(array $extra = []) {
    $opt = [
      // Disable exceptions for 4XX HTTP responses, those aren't failures to us.
      'http_errors' => FALSE,

      // Prevent inactive balancers from sucking all runtime up.
      'connect_timeout' => self::CONNECT_TIMEOUT,

      // Prevent unresponsive balancers from making Drupal slow.
      'timeout' => self::TIMEOUT,

      // Deliberately disable SSL verification to prevent unsigned certificates
      // from breaking down a website when purging a https:// URL!
      'verify' => FALSE,

      'User-Agent' => 'Zero Config Purger',
    ];
    return array_merge($opt, $extra);
  }

  /**
   * Concurrently execute the given requests.
   *
   * @param string $caller
   *   Name of the PHP method that is executing the requests.
   * @param \Closure $requests
   *   Generator yielding requests which will be passed to \GuzzleHttp\Pool.
   *
   * @return array
   *   An array of invalidation response statuses. Each key is a result ID,
   *   containing an array of booleans representing if each request succeeded or
   *   failed.
   */
  protected function getResultsConcurrently($caller, $requests) {
    $this->debug(__METHOD__);
    $results = [];

    // Create a concurrently executed Pool which collects a boolean per request.
    $pool = new Pool($this->client, $requests(), [
      'options' => $this->getGlobalOptions(),
      'concurrency' => self::CONCURRENCY,
      'fulfilled' => function($response, $result_id) use (&$results) {
        /** @var \Drupal\purge\Logger\LoggerChannelPartInterface|null $logger */
        $logger = $this->logger();
        if ($logger->isDebuggingEnabled()) {
          $this->debug(__METHOD__ . '::fulfilled');
          $this->logDebugTable($this->debugInfoForResponse($response));
        }
        $results[$result_id][] = TRUE;
      },
      'rejected' => function($reason, $result_id) use (&$results, $caller) {
        $this->debug(__METHOD__ . '::rejected');
        $this->logFailedRequest($caller, $reason);
        $results[$result_id][] = FALSE;
      },
    ]);

    // Initiate the transfers and create a promise.
    $promise = $pool->promise();

    // Force the pool of requests to complete.
    $promise->wait();

    $this->debug(__METHOD__);
    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function getIdealConditionsLimit() {
    // The max amount of outgoing HTTP requests that can be made during script
    // execution time. Although always respected as outer limit, it will be lower
    // in practice as PHP resource limits (max execution time) bring it further
    // down. However, the maximum amount of requests will be higher on the CLI.
    $proxies = count($this->getReverseProxies());
    if ($proxies) {
      return intval(ceil(200 / $proxies));
    }
    return 100;
  }

  /**
   * {@inheritdoc}
   */
  public function hasRuntimeMeasurement() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \LogicException
   *   This method should not be used.
   */
  public function invalidate(array $invalidations) {

    // Since we implemented ::routeTypeToMethod(), this Latin preciousness
    // shouldn't ever occur and when it does, will be easily recognized.
    throw new \LogicException("Malum consilium quod mutari non potest!");
  }

  /**
   * {@inheritdoc}
   */
  public function routeTypeToMethod($type) {
    $methods = [
      'tag'         => 'invalidateTags',
      'url'         => 'invalidateUrls',
      'wildcardurl' => 'invalidateWildcardUrls',
      'everything'  => 'invalidateEverything'
    ];
    return isset($methods[$type]) ? $methods[$type] : 'invalidate';
  }

  /**
   * Invalidate a set of tag invalidations.
   *
   * @see \Drupal\purge\Plugin\Purge\Purger\PurgerInterface::invalidate()
   * @see \Drupal\purge\Plugin\Purge\Purger\PurgerInterface::routeTypeToMethod()
   */
  public function invalidateTags(array $invalidations) {
    $this->debug(__METHOD__);

    // Set invalidation states to PROCESSING. Detect tags with spaces in them,
    // as space is the only character Drupal core explicitly forbids in tags.
    foreach ($invalidations as $invalidation) {
      $tag = $invalidation->getExpression();
      if (strpos($tag, ' ') !== FALSE) {
        $invalidation->setState(InvalidationInterface::FAILED);
        $this->logger->error(
          "Tag '%tag' contains a space, this is forbidden.", ['%tag' => $tag]
        );
      }
      else {
        $invalidation->setState(InvalidationInterface::PROCESSING);
      }
    }

    // Create grouped sets of 15 so that we can spread out the BAN load.
    $group = 0;
    $groups = [];
    foreach ($invalidations as $invalidation) {
      if ($invalidation->getState() !== InvalidationInterface::PROCESSING) {
        continue;
      }
      if (!isset($groups[$group])) {
        $groups[$group] = ['tags' => [], ['objects' => []]];
      }
      if (count($groups[$group]['tags']) >= self::TAGS_GROUPED_BY) {
        $group++;
      }
      $groups[$group]['objects'][] = $invalidation;
      $groups[$group]['tags'][] = $invalidation->getExpression();
    }

    // Test if we have at least one group of tag(s) to purge, if not, bail.
    if (!count($groups)) {
      foreach ($invalidations as $invalidation) {
        $invalidation->setState(InvalidationInterface::FAILED);
      }
      return;
    }

    // Now create requests for all groups of tags.
    $ipv4_addresses = $this->getReverseProxies();
    $requests = function() use ($groups, $ipv4_addresses) {
      foreach ($groups as $group_id => $group) {
        $tags = implode(' ', $group['tags']);
        foreach ($ipv4_addresses as $ipv4) {
          yield $group_id => function($poolopt) use ($tags, $ipv4) {
            $opt = [
              'headers' => [
                'Cache-Tags' => $tags,
                'Accept-Encoding' => 'gzip',
              ]
            ];
            if (is_array($poolopt) && count($poolopt)) {
              $opt = array_merge($poolopt, $opt);
            }
            $uri = $this->baseUri($ipv4)
              ->withPath('/tags');
            return $this->client->requestAsync('BAN', $uri, $opt);
          };
        }
      }
    };

    // Execute the requests generator and retrieve the results.
    $results = $this->getResultsConcurrently('invalidateTags', $requests);

    // Triage the results and set all invalidation states correspondingly.
    foreach ($groups as $group_id => $group) {
      if ((!isset($results[$group_id])) || (!count($results[$group_id]))) {
        foreach ($group['objects'] as $invalidation) {
          $invalidation->setState(InvalidationInterface::FAILED);
        }
      }
      else {
        if (in_array(FALSE, $results[$group_id])) {
          foreach ($group['objects'] as $invalidation) {
            $invalidation->setState(InvalidationInterface::FAILED);
          }
        }
        else {
          foreach ($group['objects'] as $invalidation) {
            $invalidation->setState(InvalidationInterface::SUCCEEDED);
          }
        }
      }
    }

    $this->debug(__METHOD__);
  }

  /**
   * Invalidate a set of URL invalidations.
   *
   * @param \Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface[] $invalidations
   *
   * @see \Drupal\purge\Plugin\Purge\Purger\PurgerInterface::invalidate()
   * @see \Drupal\purge\Plugin\Purge\Purger\PurgerInterface::routeTypeToMethod()
   */
  public function invalidateUrls(array $invalidations) {
    $this->debug(__METHOD__);

    // Change all invalidation objects into the PROCESS state before kickoff.
    foreach ($invalidations as $inv) {
      $inv->setState(InvalidationInterface::PROCESSING);
    }

    // Generate request objects for each balancer/invalidation combination.
    $ipv4_addresses = $this->getReverseProxies();
    $requests = function() use ($invalidations, $ipv4_addresses) {
      foreach ($invalidations as $inv) {
        foreach ($ipv4_addresses as $ipv4) {
          yield $inv->getId() => function($poolopt) use ($inv, $ipv4) {
            $expression = new Uri($inv->getExpression());
            $host = $expression->getHost();
            if ($port = $expression->getPort()) {
              $host .= ':' . $port;
            }
            $uri = $this->baseUri($ipv4)
              ->withPath($expression->getPath());
            $opt = [
              'headers' => [
                'Accept-Encoding' => 'gzip',
                'Host' => $host,
              ]
            ];
            if (is_array($poolopt) && count($poolopt)) {
              $opt = array_merge($poolopt, $opt);
            }
            return $this->client->requestAsync('PURGE', $uri, $opt);
          };
        }
      }
    };

    // Execute the requests generator and retrieve the results.
    $results = $this->getResultsConcurrently('invalidateUrls', $requests);

    // Triage the results and set all invalidation states correspondingly.
    $this->triageResults($invalidations, $results);

    $this->debug(__METHOD__);
  }

  /**
   * Invalidate URLs that contain the wildcard character "*".
   *
   * @param \Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface[] $invalidations
   *
   * @see \Drupal\purge\Plugin\Purge\Purger\PurgerInterface::routeTypeToMethod()
   * @see \Drupal\purge\Plugin\Purge\Purger\PurgerInterface::invalidate()
   */
  public function invalidateWildcardUrls(array $invalidations) {
    $this->debug(__METHOD__);

    // Change all invalidation objects into the PROCESS state before kickoff.
    foreach ($invalidations as $inv) {
      $inv->setState(InvalidationInterface::PROCESSING);
    }

    // Generate request objects for each balancer/invalidation combination.
    $ipv4_addresses = $this->getReverseProxies();
    $requests = function() use ($invalidations, $ipv4_addresses) {
      foreach ($invalidations as $inv) {
        foreach ($ipv4_addresses as $ipv4) {
          yield $inv->getId() => function($poolopt) use ($inv, $ipv4) {
            $uri = (new Uri($inv->getExpression()))
              ->withScheme('http');
            $host = $uri->getHost();
            $uri = $uri->withHost($ipv4);
            $opt = [
              'headers' => [
                'Accept-Encoding' => 'gzip',
                'Host' => $host,
              ]
            ];
            if (is_array($poolopt) && count($poolopt)) {
              $opt = array_merge($poolopt, $opt);
            }
            return $this->client->requestAsync('BAN', $uri, $opt);
          };
        }
      }
    };

    // Execute the requests generator and retrieve the results.
    $results = $this->getResultsConcurrently('invalidateWildcardUrls', $requests);

    // Triage the results and set all invalidation states correspondingly.
    $this->triageResults($invalidations, $results);

    $this->debug(__METHOD__);
  }

  /**
   * Invalidate the entire website.
   *
   * This supports invalidation objects of the type 'everything'. Because many
   * load balancers on Acquia Cloud host multiple websites (e.g. sites in a
   * multisite) this will only affect the current site instance. This works
   * because all Varnish-cached resources are tagged with a unique identifier
   * coming from hostingInfo::getSiteIdentifier().
   *
   * @see \Drupal\purge\Plugin\Purge\Purger\PurgerInterface::invalidate()
   * @see \Drupal\purge\Plugin\Purge\Purger\PurgerInterface::routeTypeToMethod()
   */
  public function invalidateEverything(array $invalidations) {
    $this->debug(__METHOD__);

    // Set the 'everything' object(s) into processing mode.
    foreach ($invalidations as $invalidation) {
      $invalidation->setState(InvalidationInterface::PROCESSING);
    }

    // Fetch the site identifier and start with a successive outcome.
    $overall_success = TRUE;

    // Synchronously request each balancer to wipe out everything for this site.
    foreach ($this->getReverseProxies() as $ip_address) {
      try {
        $uri = $this->baseUri($ip_address);
        $uri = $uri->withPath('/.*');
        $options = [
          'headers' => [
            'Host' => \Drupal::request()->getHost(),
            'Accept-Encoding' => 'gzip',
          ]
        ];
        $this->client->request('BAN', $uri, $this->getGlobalOptions($options));
      }
      catch (\Exception $e) {
        $this->logFailedRequest('invalidateEverything', $e);
        $overall_success = FALSE;
      }
    }

    // Set the object states according to our overall result.
    foreach ($invalidations as $invalidation) {
      if ($overall_success) {
        $invalidation->setState(InvalidationInterface::SUCCEEDED);
      }
      else {
        $invalidation->setState(InvalidationInterface::FAILED);
      }
    }

    $this->debug(__METHOD__);
  }

  /**
   * Return the available reverse proxies.
   *
   * @return string[]
   */
  protected function getReverseProxies(): array {
    return $this->reverseProxies;
  }

  /**
   * Create a URI with the configured proxy port.
   *
   * @param string $ip_address
   *
   * @return \GuzzleHttp\Psr7\Uri
   */
  private function baseUri(string $ip_address) {
    $uri = new Uri('http://' . $ip_address);
    if ($this->proxyPort) {
      $uri = $uri->withPort($this->proxyPort);
    }
    return $uri;
  }

  /**
   * Set invalidation result states.
   *
   * @param \Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface[] $invalidations
   *   The array of invalidations.
   * @param array $results
   *   The array of result booleans, indexed by invalidation ID.
   */
  private function triageResults(array $invalidations, array $results) {
    foreach ($invalidations as $invalidation) {
      $inv_id = $invalidation->getId();
      if ((!isset($results[$inv_id])) || (!count($results[$inv_id]))) {
        $invalidation->setState(InvalidationInterface::FAILED);
      }
      else {
        if (in_array(FALSE, $results[$inv_id])) {
          $invalidation->setState(InvalidationInterface::FAILED);
        }
        else {
          $invalidation->setState(InvalidationInterface::SUCCEEDED);
        }
      }
    }
  }

}
