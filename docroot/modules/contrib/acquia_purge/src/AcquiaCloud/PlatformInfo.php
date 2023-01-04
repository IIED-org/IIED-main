<?php

namespace Drupal\acquia_purge\AcquiaCloud;

use Drupal\Core\Site\Settings;
use Drupal\Core\State\StateInterface;

/**
 * Provides an information object interfacing with the Acquia platform.
 */
class PlatformInfo implements PlatformInfoInterface {

  /**
   * Name of the PHP function that's available when on Acquia Cloud.
   *
   * @var string
   */
  const AH_INFO_FUNCTION = 'ah_site_info_keyed';

  /**
   * The load balancer IP adresses installed in front of this site.
   *
   * @var string[]
   */
  protected $balancerAddresses = [];

  /**
   * The token used to authenticate cache invalidations with.
   *
   * @var string
   */
  protected $balancerToken = '';

  /**
   * Acquia Platform CDN configuration settings.
   *
   * Associated array with configuration parameters for Acquia Platform CDN,
   * which has at minimum the following two keys:
   *  - config: Configuration source string, either 'settings' or 'cmi'.
   *  - vendor: The underlying CDN backend used by the platform.
   *  - ... other keys can be present depending on the used backend.
   *
   * @var string[]
   */
  protected $platformCdn = [];

  /**
   * Whether the current site is running on Acquia Cloud or not.
   *
   * @var bool
   */
  protected $isThisAcquiaCloud = FALSE;

  /**
   * The Acquia site environment.
   *
   * @var string
   */
  protected $siteEnvironment = '';

  /**
   * The Acquia site group.
   *
   * @var string
   */
  protected $siteGroup = '';

  /**
   * Unique identifier for this site.
   *
   * @var string
   */
  protected $siteIdentifier = '';

  /**
   * The Acquia site name.
   *
   * @var string
   */
  protected $siteName = '';

  /**
   * The Drupal site path.
   *
   * @var string
   */
  protected $sitePath = '';

  /**
   * Constructs a PlatformInfo object.
   *
   * @param string $site_path
   *   The site path.
   * @param \Drupal\Core\Site\Settings $settings
   *   Drupal site settings object.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state key value store.
   */
  public function __construct(string $site_path, Settings $settings, StateInterface $state) {

    // Generate the Drupal sitepath from service.
    $this->sitePath = $site_path;

    // Take the IP addresses from the 'reverse_proxies' setting.
    if (is_array($reverse_proxies = $settings->get('reverse_proxies'))) {
      foreach ($reverse_proxies as $reverse_proxy) {
        if ($reverse_proxy && strpos($reverse_proxy, '.')) {
          $this->balancerAddresses[] = $reverse_proxy;
        }
      }
    }

    // Call the AH_INFO_FUNCTION and take the keys 'sitename' and 'sitegroup'.
    $function = self::AH_INFO_FUNCTION;
    if (function_exists($function)) {
      // @phpstan-ignore-next-line
      if (is_array($info = $function())) {
        if (isset($info['environment'])) {
          if (is_string($info['environment']) && $info['environment']) {
            $this->siteEnvironment = $info['environment'];
          }
        }
        if (isset($info['sitename'])) {
          if (is_string($info['sitename']) && $info['sitename']) {
            $this->siteName = $info['sitename'];
          }
        }
        if (isset($info['sitegroup'])) {
          if (is_string($info['sitegroup']) && $info['sitegroup']) {
            $this->siteGroup = $info['sitegroup'];
          }
        }
      }
    }
    elseif (!empty($GLOBALS['gardens_site_settings'])) {
      $this->siteEnvironment = $GLOBALS['gardens_site_settings']['env'];
      $this->siteGroup = $GLOBALS['gardens_site_settings']['site'];
      $this->siteName = $this->siteGroup . '.' . $this->siteEnvironment;
    }

    // Determine the authentication token is going to be, usually the site name.
    $this->balancerToken = $this->siteName;
    if (is_string($token = $settings->get('acquia_purge_token'))) {
      if ($token) {
        $this->balancerToken = $token;
      }
    }

    // Retrieval of the Acquia Platform CDN configuration is implemented via
    // a *temporary* hybrid implementation. For as long as the Platform CDN
    // product is in beta, the configuration object can come via state, after
    // that it will come through platform settings (which takes priority).
    $cdn_asc = $settings->get('acquia_service_credentials');
    $cdn_state = (array) $state->get('acquia_purge.platform_cdn', []);
    if (isset($cdn_asc['platform_cdn']['vendor'])
        && isset($cdn_asc['platform_cdn']['configuration'])
        && strlen($cdn_asc['platform_cdn']['vendor'])
        && is_array($cdn_asc['platform_cdn']['configuration'])
        && count($cdn_asc['platform_cdn']['configuration'])) {
      $this->platformCdn['config'] = 'settings';
      $this->platformCdn['vendor'] = (string) $cdn_asc['platform_cdn']['vendor'];
      $this->platformCdn = array_merge($this->platformCdn, $cdn_asc['platform_cdn']['configuration']);
    }
    elseif (isset($cdn_state['vendor']) && strlen($cdn_state['vendor']) && (count($cdn_state) > 2)) {
      $this->platformCdn = $cdn_state;
      $this->platformCdn['config'] = 'state';
    }

    // Use the sitename and site path directory as site identifier.
    $this->siteIdentifier = Hash::siteIdentifier(
      $this->siteName,
      $this->sitePath
    );

    // Test the gathered information to determine if this is/isn't Acquia Cloud.
    $this->isThisAcquiaCloud =
      is_array($this->balancerAddresses)
      && $this->balancerToken
      && $this->siteEnvironment
      && $this->siteIdentifier
      && $this->siteName
      && $this->siteGroup;
  }

  /**
   * {@inheritdoc}
   */
  public function getBalancerAddresses() {
    return $this->balancerAddresses;
  }

  /**
   * {@inheritdoc}
   */
  public function getBalancerToken() {
    return $this->balancerToken;
  }

  /**
   * {@inheritdoc}
   */
  public function getPlatformCdnConfiguration() {
    if (empty($this->platformCdn)) {
      throw new \RuntimeException("No Platform CDN configuration available.");
    }
    if (!(isset($this->platformCdn['vendor']) && strlen($this->platformCdn['vendor']))) {
      throw new \RuntimeException("Platform CDN vendor not specified.");
    }
    if (!(isset($this->platformCdn['config']) && strlen($this->platformCdn['config']))) {
      throw new \RuntimeException("Platform CDN config has no 'config' key.");
    }
    return $this->platformCdn;
  }

  /**
   * {@inheritdoc}
   */
  public function getSiteEnvironment() {
    return $this->siteEnvironment;
  }

  /**
   * {@inheritdoc}
   */
  public function getSiteGroup() {
    return $this->siteGroup;
  }

  /**
   * {@inheritdoc}
   */
  public function getSiteIdentifier() {
    return $this->siteIdentifier;
  }

  /**
   * {@inheritdoc}
   */
  public function getSiteName() {
    return $this->siteName;
  }

  /**
   * {@inheritdoc}
   */
  public function getSitePath() {
    return $this->sitePath;
  }

  /**
   * {@inheritdoc}
   */
  public function isThisAcquiaCloud() {
    return $this->isThisAcquiaCloud;
  }

}
