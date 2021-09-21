<?php

namespace Drupal\acquia_search\Helper;

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

  /**
   * List of values by each Solarium request type.
   */
  public static function getFloodDefaults() {
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
  public static function getConfigValue(string $request_type, string $value_name) {
    $defaults = self::getFloodDefaults();
    $escaped_request_type = str_replace('/', '_', $request_type);
    $config_id = 'flood_limit.' . $escaped_request_type . '.' . $value_name;
    return \Drupal::config('acquia_search.settings')->get($config_id) ?? $defaults[$request_type][$value_name];
  }

  /**
   * Return boolean value stating if logging is enabled.
   *
   * @return bool
   *   If logging is enabled or not.
   */
  public static function isLoggingEnabled(): bool {
    return \Drupal::config('acquia_search.settings')->get('flood_logging') ?? TRUE;
  }

  /**
   * Return the window for the given request type.
   *
   * @param string $request_type
   *   The incoming request type.
   */
  public static function logFloodLimit(string $request_type) {
    if (self::isLoggingEnabled()) {
      \Drupal::logger('acquia_search')->warning(
        'Flood protection has blocked request of type @id.',
        ['@id' => $request_type]
      );
    }
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
  public static function isControlled(string $request_type): bool {
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
   *
   * @throws \Exception
   */
  public static function isAllowed(string $request_type): bool {

    // Allow all requests from types that aren't controlled.
    if (!self::isControlled($request_type)) {
      return TRUE;
    }

    // Use the Drupal Flood service to check if we can run this request.
    $is_allowed = \Drupal::flood()->isAllowed(
      'acquia_search',
      self::getConfigValue($request_type, 'limit'),
      self::getConfigValue($request_type, 'window'),
      $request_type
    );

    // If this request should be blocked, log if needed and return.
    if (!$is_allowed) {
      self::logFloodLimit($request_type);
      return FALSE;
    }

    // Log the allowed request into the Flood service.
    \Drupal::flood()->register(
      'acquia_search',
      self::getConfigValue($request_type, 'window'),
      $request_type
    );
    return TRUE;
  }

}
