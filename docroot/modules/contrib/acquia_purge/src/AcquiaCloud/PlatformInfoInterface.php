<?php

namespace Drupal\acquia_purge\AcquiaCloud;

/**
 * Describes an information object interfacing with the Acquia platform.
 */
interface PlatformInfoInterface {

  /**
   * Get the load balancer IP adresses installed in front of this site.
   *
   * @return string[]
   *   Unassociative list of adresses in the form of 'I.P.V.4', or empty array.
   */
  public function getBalancerAddresses();

  /**
   * Get the token used to authenticate cache invalidations with.
   *
   * @return string
   *   Token string, e.g. 'oursecret' or 'sitedev'.
   */
  public function getBalancerToken();

  /**
   * Get the Acquia Platform CDN configuration.
   *
   * @throws \RuntimeException
   *   Thrown when either no configuration is available.
   *
   * @return mixed[]
   *   Associated array with configuration parameters for Acquia Platform CDN,
   *   which has at minimum the following two keys:
   *    - config: Configuration source string, either 'settings' or 'state'.
   *    - vendor: The underlying CDN backend used by the platform.
   *    - ... other keys can be present depending on the used backend.
   */
  public function getPlatformCdnConfiguration();

  /**
   * Get the Acquia site environment.
   *
   * @return string
   *   The site environment, e.g. 'dev'.
   */
  public function getSiteEnvironment();

  /**
   * Get the Acquia site group.
   *
   * @return string
   *   The site group, e.g. 'site' or '' when unavailable.
   */
  public function getSiteGroup();

  /**
   * Get a unique identifier for this Acquia site.
   *
   * @return string
   *   Unique string for this Drupal instance, even within multisites!
   */
  public function getSiteIdentifier();

  /**
   * Get the Acquia site name.
   *
   * @return string
   *   The site group, e.g. 'sitedev' or '' when unavailable.
   */
  public function getSiteName();

  /**
   * Get the Drupal site path.
   *
   * @return string
   *   The site path, e.g. 'site/default' or 'site/mysecondsite'.
   */
  public function getSitePath();

  /**
   * Determine whether the current site is running on Acquia Cloud.
   *
   * @return true|false
   *   Boolean expression where 'true' indicates Acquia Cloud or 'false'.
   */
  public function isThisAcquiaCloud();

}
