<?php

namespace Drupal\cloudflarepurger\Plugin\Purge\Purger;

use Cloudflare\API\Adapter\Guzzle;
use Cloudflare\API\Auth\APIKey;
use Cloudflare\API\Auth\APIToken;
use Cloudflare\API\Endpoints\Zones;
use Drupal\cloudflare\CloudFlareComposerDependenciesCheckInterface;
use Drupal\cloudflare\CloudFlareStateInterface;
use Drupal\cloudflarepurger\EventSubscriber\CloudFlareCacheTagHeaderGenerator;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface;
use Drupal\purge\Plugin\Purge\Purger\PurgerBase;
use Drupal\purge\Plugin\Purge\Purger\PurgerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

// cspell:ignore Depencies
/**
 * CloudFlare purger.
 *
 * @PurgePurger(
 *   id = "cloudflare",
 *   label = @Translation("CloudFlare"),
 *   description = @Translation("Purger for CloudFlare."),
 *   types = {"tag", "url", "everything"},
 *   multi_instance = FALSE,
 * )
 */
class CloudFlarePurger extends PurgerBase implements PurgerInterface {

  // Max Number of tag purges.
  public const MAX_TAG_PURGES_PER_REQUEST = 30;

  /**
   * The settings configuration.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Tracks rate limits associated with CloudFlare Api.
   *
   * @var \Drupal\cloudflare\CloudFlareStateInterface
   */
  protected $state;

  /**
   * The current cloudflare ZoneId.
   *
   * @var string
   */
  protected $zone;

  /**
   * TRUE if composer dependencies are met.  False otherwise.
   *
   * @var bool
   */
  protected $areCloudflareComposerDepenciesMet;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
      $container->get('cloudflare.state'),
      $container->get('logger.factory')->get('cloudflare'),
      $container->get('cloudflare.composer_dependency_check')
    );
  }

  /**
   * Constructs a \Drupal\Component\Plugin\CloudFlarePurger.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\cloudflare\CloudFlareStateInterface $state
   *   Tracks limits associated with CloudFlare Api.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\cloudflare\CloudFlareComposerDependenciesCheckInterface $checker
   *   Tests that composer dependencies are met.
   *
   * @throws \LogicException
   *   Thrown if $configuration['id'] is missing, see Purger\Service::createId.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $config_factory, CloudFlareStateInterface $state, LoggerInterface $logger, CloudFlareComposerDependenciesCheckInterface $checker) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->config = $config_factory->get('cloudflare.settings');
    $this->state = $state;
    $this->logger = $logger;
    $this->areCloudflareComposerDepenciesMet = $checker->check();
  }

  /**
   * {@inheritdoc}
   */
  public function routeTypeToMethod($type) {
    $methods = [
      'everything' => 'invalidate',
      'tag'  => 'invalidate',
      'url'  => 'invalidate',
    ];

    return $methods[$type] ?? 'invalidate';
  }

  /**
   * {@inheritdoc}
   */
  public function invalidate(array $invalidations) {
    $chunks = array_chunk($invalidations, self::MAX_TAG_PURGES_PER_REQUEST);

    $has_invalidations = count($invalidations) > 0;
    if (!$has_invalidations) {
      return;
    }

    foreach ($chunks as $chunk) {
      $this->purgeChunk($chunk);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function hasRuntimeMeasurement() {
    return TRUE;
  }

  /**
   * Purges a chunk of tags.
   *
   * Integration point between purge and CloudFlareAPI.  Purge requires state
   * tracking on each item purged.  This function provides that accounting and
   * calls CloudflareApi.
   *
   * CloudFlare only allows us to purge 30 tags at once.
   *
   * @param array $invalidations
   *   Chunk of purge module invalidation objects to purge via CloudFlare.
   */
  private function purgeChunk(array &$invalidations) {
    // This is a unique case where the ApiSdk is being accessed directly and not
    // via a service.  Purging should only ever happen through the purge module
    // which is why this is NOT in a service.
    $auth_using = $this->config->get('auth_using');
    if ($auth_using === 'key') {
      $api_key = $this->config->get('apikey');
      $email = $this->config->get('email');
      $key = new APIKey($email, $api_key);
    }
    elseif ($auth_using === 'token') {
      $token = $this->config->get('api_token');
      $key = new APIToken($token);
    }
    $this->zone = $this->config->get('zone_id');
    $adapter = new Guzzle($key);
    $zoneApi = new Zones($adapter);

    $api_targets_to_purge = [];

    // This method is unfortunately a bit verbose due to the fact that we
    // need to update the purge states as we proceed.
    foreach ($invalidations as $invalidation) {
      $invalidation->setState(InvalidationInterface::PROCESSING);
      $api_targets_to_purge[] = $invalidation->getExpression();
    }

    if (!$this->areCloudflareComposerDepenciesMet) {
      foreach ($invalidations as $invalidation) {
        $invalidation->setState(InvalidationInterface::FAILED);
      }
    }

    try {
      // Interface with the Cloudflare SDK.
      $invalidation_type = $invalidations[0]->getPluginId();
      if ($invalidation_type == 'tag') {
        // @todo Remove this wrapper once CloudFlare supports 16k headers.
        // Also invalidate the cache tags as hashes, to automatically also work
        // for responses that exceed CloudFlare's Cache-Tag header limit.
        $hashes = CloudFlareCacheTagHeaderGenerator::cacheTagsToHashes($api_targets_to_purge);
        foreach ($this->zone as $zone_id) {
          $zoneApi->cachePurge($zone_id, NULL, $hashes);
        }
        $this->state->incrementTagPurgeDailyCount();
      }

      elseif ($invalidation_type == 'url') {
        $zone_name = $this->config->get('zone_name');
        $zones = $zoneApi->listZones($zone_name)->result ?? [];

        // Filter URLs based on specific zones.
        $purge_zone_urls = [];
        foreach ($zones as $zone) {
          foreach ($api_targets_to_purge as $item) {
            // Check if URL item belongs to available zone(s).
            // And group them based on their zone-ids.
            if (strpos($item, $zone->name) !== FALSE) {
              $purge_zone_urls[$zone->id][] = $item;
            }
          }
        }

        foreach ($this->zone as $zone_id) {
          if (!empty($purge_zone_urls[$zone_id])) {
            $zoneApi->cachePurge($zone_id, $purge_zone_urls[$zone_id]);
          }
        }
      }

      elseif ($invalidation_type == 'everything') {
        foreach ($this->zone as $zone_id) {
          $zoneApi->cachePurgeEverything($zone_id);
        }
      }

      foreach ($invalidations as $invalidation) {
        $invalidation->setState(InvalidationInterface::SUCCEEDED);
      }
    }

    catch (\Exception $e) {
      foreach ($invalidations as $invalidation) {
        $invalidation->setState(InvalidationInterface::FAILED);
      }

      // We only want to log a single watchdog error per request. This prevents
      // the log from being flooded.
      $this->logger->error($e->getMessage());
    }

    finally {
      $this->state->incrementApiRateCount();
    }
  }

}
