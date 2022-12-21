<?php

namespace Drupal\acquia_purge\Plugin\Purge\Purger;

use Drupal\acquia_purge\AcquiaCloud\Hash;
use Drupal\acquia_purge\AcquiaCloud\PlatformInfoInterface;
use Drupal\acquia_purge\Plugin\Purge\TagsHeader\TagsHeaderValue;
use Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface;
use Drupal\purge\Plugin\Purge\Purger\PurgerBase;
use Drupal\purge\Plugin\Purge\Purger\PurgerInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Pool;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Acquia Cloud.
 *
 * @PurgePurger(
 *   id = "acquia_purge",
 *   label = @Translation("Acquia Cloud"),
 *   configform = "",
 *   cooldown_time = 0.2,
 *   description = @Translation("Invalidate content from Acquia Cloud."),
 *   multi_instance = FALSE,
 *   types = {"url", "wildcardurl", "tag", "everything"},
 * )
 */
class AcquiaCloudPurger extends PurgerBase implements DebuggerAwareInterface, PurgerInterface {
  use DebuggerAwareTrait;

  /**
   * Maximum number of requests to send concurrently.
   */
  const CONCURRENCY = 6;

  /**
   * Float: the number of seconds to wait while trying to connect to a server.
   */
  const CONNECT_TIMEOUT = 1.5;

  /**
   * Float: the timeout of the request in seconds.
   */
  const TIMEOUT = 3.0;

  /**
   * Groups of tags per request.
   *
   * Batches of cache tags are split up into multiple requests to prevent HTTP
   * request headers from growing too large or Varnish refusing to process them.
   */
  const TAGS_GROUPED_BY = 15;

  /**
   * Information object interfacing with the Acquia platform.
   *
   * @var \Drupal\acquia_purge\AcquiaCloud\PlatformInfoInterface
   */
  protected $platformInfo;

  /**
   * The Guzzle HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * Constructs a AcquiaCloudPurger object.
   *
   * @param \Drupal\acquia_purge\AcquiaCloud\PlatformInfoInterface $acquia_purge_platforminfo
   *   Information object interfacing with the Acquia platform.
   * @param \GuzzleHttp\ClientInterface $http_client
   *   An HTTP client that can perform remote requests.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   */
  final public function __construct(PlatformInfoInterface $acquia_purge_platforminfo, ClientInterface $http_client, array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->platformInfo = $acquia_purge_platforminfo;
    $this->httpClient = $http_client;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('acquia_purge.platforminfo'),
      $container->get('http_client'),
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * Retrieve request options used for all of Acquia Purge's balancer requests.
   *
   * @param array[] $extra
   *   Associative array of options to merge onto the standard ones.
   *
   * @return mixed[]
   *   Guzzle option array.
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

      // Trigger \Drupal\acquia_purge\Http\AcquiaCloudBalancerMiddleware which
      // inspects Acquia Cloud responses and throws exceptions for failures.
      'acquia_purge_balancer_middleware' => TRUE,
    ];
    // Trigger the debugging middleware when Purge's debug mode is enabled.
    if ($this->debugger()->enabled()) {
      $opt['acquia_purge_debugger'] = $this->debugger();
    }
    return array_merge($opt, $extra);
  }

  /**
   * Concurrently execute the given requests.
   *
   * @param \Closure $requests
   *   Generator yielding requests which will be passed to \GuzzleHttp\Pool.
   */
  protected function getResultsConcurrently(\Closure $requests) {
    $this->debugger()->callerAdd(__METHOD__);
    $results = [];

    // Create a concurrently executed Pool which collects a boolean per request.
    $pool = new Pool($this->httpClient, $requests(), [
      'options' => $this->getGlobalOptions(),
      'concurrency' => self::CONCURRENCY,
      'fulfilled' => function ($response, $result_id) use (&$results) {
        $this->debugger()->callerAdd(__METHOD__ . '::fulfilled');
        $results[$result_id][] = TRUE;
        $this->debugger()->callerRemove(__METHOD__ . '::fulfilled');
      },
      'rejected' => function ($exception, $result_id) use (&$results) {
        $this->debugger()->callerAdd(__METHOD__ . '::rejected');
        $this->debugger()->logFailedRequest($exception);
        $results[$result_id][] = FALSE;
        $this->debugger()->callerRemove(__METHOD__ . '::rejected');
      },
    ]);

    // Initiate the transfers and create a promise.
    $promise = $pool->promise();

    // Force the pool of requests to complete.
    $promise->wait();

    $this->debugger()->callerRemove(__METHOD__);
    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function getIdealConditionsLimit() {
    // The max amount of outgoing HTTP requests that can be made during script
    // execution time. Although always respected as outer limit, it will be
    // lower in practice as PHP resource limits (max execution time) bring it
    // further down. However, the maximum amount of requests will be higher on
    // the CLI.
    $balancers = count($this->platformInfo->getBalancerAddresses());
    if ($balancers) {
      return intval(ceil(200 / $balancers));
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
   */
  public function invalidate(array $invalidations) {

    // Since we implemented ::routeTypeToMethod(), this Latin preciousness
    // shouldn't ever occur and when it does, will be easily recognized.
    throw new \Exception("Malum consilium quod mutari non potest!");
  }

  /**
   * Invalidate a set of tag invalidations.
   *
   * @see \Drupal\purge\Plugin\Purge\Purger\PurgerInterface::invalidate()
   * @see \Drupal\purge\Plugin\Purge\Purger\PurgerInterface::routeTypeToMethod()
   */
  public function invalidateTags(array $invalidations) {
    $this->debugger()->callerAdd(__METHOD__);

    // Set invalidation states to PROCESSING. Detect tags with spaces in them,
    // as space is the only character Drupal core explicitely forbids in tags.
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

    // Create grouped sets of 12 so that we can spread out the BAN load.
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
    $site = $this->platformInfo->getSiteIdentifier();
    $ipv4_addresses = $this->platformInfo->getBalancerAddresses();
    $requests = function () use ($groups, $ipv4_addresses, $site) {
      foreach ($groups as $group_id => $group) {
        $tags = new TagsHeaderValue(
          $group['tags'],
          Hash::cacheTags($group['tags'])
        );
        foreach ($ipv4_addresses as $ipv4) {
          yield $group_id => function ($poolopt) use ($site, $tags, $ipv4) {
            $opt = [
              'headers' => [
                'X-Acquia-Purge' => $site,
                'X-Acquia-Purge-Tags' => $tags->__toString(),
                'Accept-Encoding' => 'gzip',
                'User-Agent' => 'Acquia Purge',
              ],
            ];
            // Pass the TagsHeaderValue to DebuggerMiddleware (when loaded).
            if ($this->debugger()->enabled()) {
              $opt['acquia_purge_tags'] = $tags;
            }
            if (is_array($poolopt) && count($poolopt)) {
              $opt = array_merge($poolopt, $opt);
            }
            return $this->httpClient->requestAsync('BAN', "http://$ipv4/tags", $opt);
          };
        }
      }
    };

    // Execute the requests generator and retrieve the results.
    $results = $this->getResultsConcurrently($requests);

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

    $this->debugger()->callerRemove(__METHOD__);
  }

  /**
   * Invalidate a set of URL invalidations.
   *
   * @see \Drupal\purge\Plugin\Purge\Purger\PurgerInterface::invalidate()
   * @see \Drupal\purge\Plugin\Purge\Purger\PurgerInterface::routeTypeToMethod()
   */
  public function invalidateUrls(array $invalidations) {
    $this->debugger()->callerAdd(__METHOD__);

    // Change all invalidation objects into the PROCESS state before kickoff.
    foreach ($invalidations as $inv) {
      $inv->setState(InvalidationInterface::PROCESSING);
    }

    // Generate request objects for each balancer/invalidation combination.
    $ipv4_addresses = $this->platformInfo->getBalancerAddresses();
    $token = $this->platformInfo->getBalancerToken();
    $requests = function () use ($invalidations, $ipv4_addresses, $token) {
      foreach ($invalidations as $inv) {
        foreach ($ipv4_addresses as $ipv4) {
          yield $inv->getId() => function ($poolopt) use ($inv, $ipv4, $token) {
            $uri = $inv->getExpression();
            $host = parse_url($uri, PHP_URL_HOST);
            $uri = str_replace($host, $ipv4, $uri);
            $opt = [
              'headers' => [
                'X-Acquia-Purge' => $token,
                'Accept-Encoding' => 'gzip',
                'User-Agent' => 'Acquia Purge',
                'Host' => $host,
              ],
            ];
            if (is_array($poolopt) && count($poolopt)) {
              $opt = array_merge($poolopt, $opt);
            }
            return $this->httpClient->requestAsync('PURGE', $uri, $opt);
          };
        }
      }
    };

    // Execute the requests generator and retrieve the results.
    $results = $this->getResultsConcurrently($requests);

    // Triage the results and set all invalidation states correspondingly.
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

    $this->debugger()->callerRemove(__METHOD__);
  }

  /**
   * Invalidate URLs that contain the wildcard character "*".
   *
   * @see \Drupal\purge\Plugin\Purge\Purger\PurgerInterface::invalidate()
   * @see \Drupal\purge\Plugin\Purge\Purger\PurgerInterface::routeTypeToMethod()
   */
  public function invalidateWildcardUrls(array $invalidations) {
    $this->debugger()->callerAdd(__METHOD__);

    // Change all invalidation objects into the PROCESS state before kickoff.
    foreach ($invalidations as $inv) {
      $inv->setState(InvalidationInterface::PROCESSING);
    }

    // Generate request objects for each balancer/invalidation combination.
    $ipv4_addresses = $this->platformInfo->getBalancerAddresses();
    $token = $this->platformInfo->getBalancerToken();
    $requests = function () use ($invalidations, $ipv4_addresses, $token) {
      foreach ($invalidations as $inv) {
        foreach ($ipv4_addresses as $ipv4) {
          yield $inv->getId() => function ($poolopt) use ($inv, $ipv4, $token) {
            $uri = str_replace('https://', 'http://', $inv->getExpression());
            $host = parse_url($uri, PHP_URL_HOST);
            $uri = str_replace($host, $ipv4, $uri);
            $opt = [
              'headers' => [
                'X-Acquia-Purge' => $token,
                'Accept-Encoding' => 'gzip',
                'User-Agent' => 'Acquia Purge',
                'Host' => $host,
              ],
            ];
            if (is_array($poolopt) && count($poolopt)) {
              $opt = array_merge($poolopt, $opt);
            }
            return $this->httpClient->requestAsync('BAN', $uri, $opt);
          };
        }
      }
    };

    // Execute the requests generator and retrieve the results.
    $results = $this->getResultsConcurrently($requests);

    // Triage the results and set all invalidation states correspondingly.
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

    $this->debugger()->callerRemove(__METHOD__);
  }

  /**
   * Invalidate the entire website.
   *
   * This supports invalidation objects of the type 'everything'. Because many
   * load balancers on Acquia Cloud host multiple websites (e.g. sites in a
   * multisite) this will only affect the current site instance. This works
   * because all Varnish-cached resources are tagged with a unique identifier
   * coming from platformInfo::getSiteIdentifier().
   *
   * @see \Drupal\purge\Plugin\Purge\Purger\PurgerInterface::invalidate()
   * @see \Drupal\purge\Plugin\Purge\Purger\PurgerInterface::routeTypeToMethod()
   */
  public function invalidateEverything(array $invalidations) {
    $this->debugger()->callerAdd(__METHOD__);

    // Set the 'everything' object(s) into processing mode.
    foreach ($invalidations as $invalidation) {
      $invalidation->setState(InvalidationInterface::PROCESSING);
    }

    // Fetch the site identifier and start with a successive outcome.
    $overall_success = TRUE;

    // Synchronously request each balancer to wipe out everything for this site.
    $opt = $this->getGlobalOptions();
    $opt['headers'] = [
      'X-Acquia-Purge' => $this->platformInfo->getSiteIdentifier(),
      'Accept-Encoding' => 'gzip',
      'User-Agent' => 'Acquia Purge',
    ];
    foreach ($this->platformInfo->getBalancerAddresses() as $ip_address) {
      try {
        $this->httpClient->request(
          'BAN',
          'http://' . $ip_address . '/site',
          $opt
        );
      }
      catch (\Exception $e) {
        $this->debugger()->logFailedRequest($e);
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

    $this->debugger()->callerRemove(__METHOD__);
  }

  /**
   * {@inheritdoc}
   */
  public function routeTypeToMethod($type) {
    $methods = [
      'tag'         => 'invalidateTags',
      'url'         => 'invalidateUrls',
      'wildcardurl' => 'invalidateWildcardUrls',
      'everything'  => 'invalidateEverything',
    ];
    return isset($methods[$type]) ? $methods[$type] : 'invalidate';
  }

}
