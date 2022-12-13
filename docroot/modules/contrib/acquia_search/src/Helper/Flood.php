<?php

namespace Drupal\acquia_search\Helper;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Flood\FloodInterface;
use Psr\Log\LoggerInterface;

/**
 * Class Flood.
 *
 * A mechanism to limit the outgoing number of requests to the Acquia Search
 * Solr backend using the Drupal Flood API.
 *
 * This complements the Solr traffic limits embedded into on the Acquia
 * platform.
 *
 * This class will look at the recent requests during a time window, and
 * return a boolean value on whether to block/allow those requests. The actual
 * blocking happens elsewhere.
 *
 * The values in code are named 'window' and 'limit.
 *   window: the amount of seconds in the "sliding window" time
 *   limit: the maximum amount of requests that can be done during that window.
 *
 * So, to check whether to carry out or block the current request, we use look
 * at the window between T-[window] seconds up to the present, and if more
 * than [limit] requests of the same [type] have happened, we will deny that
 * request.
 *
 * Example: for limit=10 [requests] and window=10 [seconds] allows at most
 *  10 requests in that time period.
 *
 * See the Drupal Flood API documentation for more:
 * https://api.drupal.org/api/drupal/core%21lib%21Drupal%21Core%21Flood%21FloodInterface.php/interface/FloodInterface
 */
class Flood {

  const FLOOD_LIMIT_ARTICLE_URL = "https://support-acquia.force.com/s/article/1500008925761-The-Acquia-Search-flood-control-mechanism-has-blocked-a-Solr-query-due-to-API-usage-limits";

  /**
   * The core flood service.
   *
   * @var \Drupal\Core\Flood\FloodInterface
   */
  private $flood;

  /**
   * The config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  private $config;

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  private $logger;

  /**
   * Constructs a new Flood object.
   *
   * @param \Drupal\Core\Flood\FloodInterface $core_flood
   *   The core flood service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger.
   */
  public function __construct(FloodInterface $core_flood, ConfigFactoryInterface $config_factory, LoggerInterface $logger) {
    $this->flood = $core_flood;
    $this->config = $config_factory->get('acquia_search.settings');
    $this->logger = $logger;
  }

  /**
   * List of values by each Solarium request type.
   */
  private static function getFloodDefaults() {
    return [
      'select' => ['window' => 10, 'limit' => 50],
      'update' => ['window' => 60, 'limit' => 600],
      'update/extract' => ['window' => 60, 'limit' => 600],
      'autocomplete' => ['window' => 10, 'limit' => 100],
      'test' => ['window' => 2, 'limit' => 1],
    ];
  }

  /**
   * Return the integer value for the specified type and option.
   *
   * @param string $request_type
   *   The incoming request type.
   * @param string $value_name
   *   The name of the type value.
   *
   * @return int
   *   Integer value for specified type and option.
   */
  private function getConfigValue(string $request_type, string $value_name) {
    $defaults = self::getFloodDefaults();
    $escaped_request_type = str_replace('/', '_', $request_type);
    $config_id = 'flood_limit.' . $escaped_request_type . '.' . $value_name;
    return $this->config->get($config_id) ?? $defaults[$request_type][$value_name];
  }

  /**
   * Check that the given ID is a valid string from a list of defined values.
   *
   * @param string $request_type
   *   The incoming request type.
   *
   * @return bool
   *   If the request type is controlled
   */
  private static function isControlled(string $request_type): bool {
    $defaults = self::getFloodDefaults();
    return isset($defaults[$request_type]);
  }

  /**
   * Determines if a request can be sent via the flood control mechanism.
   *
   * @param string $request_type
   *   The incoming request type.
   *
   * @return bool
   *   If the request is allowed
   */
  public function isAllowed(string $request_type): bool {
    // Allow all requests from types that aren't controlled.
    if (!self::isControlled($request_type)) {
      return TRUE;
    }

    $limit = $this->getConfigValue($request_type, 'limit');
    $window = $this->getConfigValue($request_type, 'window');

    // Use the Drupal Flood service to check if we can run this request.
    $is_allowed = $this->flood->isAllowed(
      'acquia_search',
      $limit,
      $window,
      $request_type
    );

    // If this request should be blocked, log if needed and return.
    if (!$is_allowed) {
      if ($this->config->get('flood_logging') === TRUE) {
        $this->logger->warning(
          'Flood protection has blocked request of type @id. See more at <a href="@url">The Acquia Search flood control mechanism has blocked a Solr query due to API usage limits</a>',
          [
            '@id' => $request_type,
            '@url' => self::FLOOD_LIMIT_ARTICLE_URL,
          ]
        );
      }
      return FALSE;
    }

    // Log the allowed request into the Flood service.
    $this->flood->register(
      'acquia_search',
      $window,
      $request_type
    );
    return TRUE;
  }

}
