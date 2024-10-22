<?php

namespace Drupal\cloudflare;

/**
 * Zone methods for CloudFlare.
 */
interface CloudFlareZoneInterface {

  /**
   * Retrieves a listing of zones in the current CloudFlare account.
   *
   * @return array
   *   A array of CloudFlareZones objects from the current CloudFlare account.
   *
   * @throws \GuzzleHttp\Exception\RequestException
   *   Application level error returned from the API.
   */
  public function listZones();

  /**
   * Asserts that credentials are valid. Does NOT pull settings from CMI.
   *
   * @param string $api_token
   *   The secret Api token used to authenticate against CloudFlare.
   * @param \Drupal\cloudflare\CloudFlareComposerDependenciesCheckInterface $composer_dependency_check
   *   Checks that composer dependencies are met.
   * @param \Drupal\cloudflare\CloudFlareStateInterface $state
   *   Tracks rate limits associated with CloudFlare Api.
   * @param string $zone_name
   *   Zone name to limit the results for.
   */
  public static function assertValidToken($api_token, CloudFlareComposerDependenciesCheckInterface $composer_dependency_check, CloudFlareStateInterface $state, $zone_name);

  /**
   * Asserts that credentials are valid. Does NOT pull settings from CMI.
   *
   * @param string $apikey
   *   The secret Api key used to authenticate against CloudFlare.
   * @param string $email
   *   Email of the account used to authenticate against CloudFlare.
   * @param \Drupal\cloudflare\CloudFlareComposerDependenciesCheckInterface $composer_dependency_check
   *   Checks that composer dependencies are met.
   * @param \Drupal\cloudflare\CloudFlareStateInterface $state
   *   Tracks rate limits associated with CloudFlare Api.
   *
   * @throws \GuzzleHttp\Exception\ClientException
   *   Thrown if $apikey and $email fail to authenticate against the Api.
   * @throws \GuzzleHttp\Exception\RequestException
   *   Thrown if an unknown exception occurs when connecting to the Api.
   */
  public static function assertValidCredentials($apikey, $email, CloudFlareComposerDependenciesCheckInterface $composer_dependency_check, CloudFlareStateInterface $state);

}
