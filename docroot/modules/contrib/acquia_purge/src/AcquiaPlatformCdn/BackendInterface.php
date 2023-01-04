<?php

namespace Drupal\acquia_purge\AcquiaPlatformCdn;

use Drupal\acquia_purge\AcquiaCloud\PlatformInfoInterface;
use Drupal\acquia_purge\Plugin\Purge\Purger\DebuggerAwareInterface;
use Drupal\acquia_purge\Plugin\Purge\Purger\DebuggerInterface;
use Drupal\purge\Logger\LoggerChannelPartInterface;
use Drupal\purge\Logger\PurgeLoggerAwareInterface;
use GuzzleHttp\ClientInterface;

/**
 * Describes a vendor backend for the Platform CDN purger.
 */
interface BackendInterface extends PurgeLoggerAwareInterface, DebuggerAwareInterface {

  /**
   * Construct a vendor backend for the Platform CDN purger.
   *
   * @param array $config
   *   Acquia Platform CDN configuration settings.
   * @param \Drupal\acquia_purge\AcquiaCloud\PlatformInfoInterface $acquia_purge_platforminfo
   *   Information object interfacing with the Acquia platform.
   * @param \Drupal\purge\Logger\LoggerChannelPartInterface $logger
   *   The logger passed to the Platform CDN purger.
   * @param \Drupal\acquia_purge\Plugin\Purge\Purger\DebuggerInterface $debugger
   *   The centralized debugger for Acquia purger plugins.
   * @param \GuzzleHttp\ClientInterface $http_client
   *   An HTTP client that can perform remote requests.
   */
  public function __construct(array $config, PlatformInfoInterface $acquia_purge_platforminfo, LoggerChannelPartInterface $logger, DebuggerInterface $debugger, ClientInterface $http_client);

  /**
   * Check if a temporary runtime error has been set.
   *
   * @return string
   *   Empty string when no runtime error is present.
   */
  public static function getTemporaryRuntimeError();

  /**
   * Get the information object interfacing with the Acquia platform.
   *
   * @param null|\Drupal\acquia_purge\AcquiaCloud\PlatformInfoInterface $set
   *   Set the object initially.
   *
   * @throws \RuntimeException
   *   Thrown when the object hasn't been set yet.
   *
   * @return \Drupal\acquia_purge\AcquiaCloud\PlatformInfoInterface
   *   Information object interfacing with the Acquia platform.
   */
  public static function platformInfo(PlatformInfoInterface $set = NULL);

  /**
   * Invalidate all 'tag' invalidations.
   *
   * @see \Drupal\purge\Plugin\Purge\Purger\invalidate
   * @see \Drupal\purge\Plugin\Purge\Purger\routeTypeToMethod
   */
  public function invalidateTags(array $invalidations);

  /**
   * Invalidate all 'url' invalidations.
   *
   * @see \Drupal\purge\Plugin\Purge\Purger\invalidate
   * @see \Drupal\purge\Plugin\Purge\Purger\routeTypeToMethod
   */
  public function invalidateUrls(array $invalidations);

  /**
   * Invalidate all 'everything' invalidations.
   *
   * @see \Drupal\purge\Plugin\Purge\Purger\invalidate
   * @see \Drupal\purge\Plugin\Purge\Purger\routeTypeToMethod
   */
  public function invalidateEverything(array $invalidations);

  /**
   * Fetch the HTTP response header name that the CDN vendor needs.
   *
   * @see \Drupal\purge\Plugin\Purge\TagsHeader::getHeaderName
   */
  public static function tagsHeaderName();

  /**
   * Format the given cache tags for the header value representation.
   *
   * @param string[] $tags
   *   Non-associative array cache tags.
   *
   * @see \Drupal\purge\Plugin\Purge\TagsHeader::getValue
   */
  public static function tagsHeaderValue(array $tags);

  /**
   * Set a temporary runtime error.
   *
   * CDN backends have the ability to temporarily halt all activity for the
   * AcquiaPlatformCdnPurger, for instance for hitting an API rate limit. The
   * runtime error expires automatically after the specified timeout.
   *
   * @param string $message
   *   Translated user interface message, presentable to users.
   * @param int $timeout
   *   Number of seconds after which the runtime error will disappear.
   */
  public static function setTemporaryRuntimeError($message, $timeout = 300);

  /**
   * Validate the configuration array given.
   *
   * @param array $config
   *   Associative array with arbitrary settings coming from:
   *   \Drupal\acquia_purge\AcquiaCloud\PlatformInfoInterface::getPlatformCdnConfiguration.
   *
   * @return bool
   *   Boolean TRUE if the configuration array is valid. When returning FALSE
   *   the AcquiaPlatformCdnPurger will not load as a result and cache
   *   invalidation will be stopped until the configuration issue got fixed.
   */
  public static function validateConfiguration(array $config);

}
