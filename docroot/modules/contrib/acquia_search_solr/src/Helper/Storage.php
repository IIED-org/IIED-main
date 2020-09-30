<?php

namespace Drupal\acquia_search_solr\Helper;

/**
 * Class Storage.
 *
 * Centralized place for accessing and updating Acquia Search Solr settings.
 */
class Storage {

  /**
   * Returns Acquia Search API host.
   *
   * To manage Acquia Search.
   *
   * @return string
   *   Acquia Search API host.
   */
  public static function getApiHost() {
    return \Drupal::config('acquia_search_solr.settings')->get('api_host') ?? 'https://api.sr-prod02.acquia.com';
  }

  /**
   * Returns Acquia Connector key.
   *
   * @return string
   *   Acquia Connector key.
   */
  public static function getApiKey() {
    return \Drupal::state()->get('acquia_search_solr.api_key', '');
  }

  /**
   * Returns Acquia Subscription identifier.
   *
   * @return string
   *   Acquia Subscription identifier.
   */
  public static function getIdentifier(): string {
    return \Drupal::state()->get('acquia_search_solr.identifier', '');
  }

  /**
   * Returns Acquia Application UUID.
   *
   * @return string
   *   Acquia Application UUID.
   */
  public static function getUuid(): string {
    return \Drupal::state()->get('acquia_search_solr.uuid', '');
  }

  /**
   * Get Acquia Search Solr module version.
   *
   * @return string
   *   Acquia Search Solr module version.
   */
  public static function getVersion(): string {

    if (!$version = \Drupal::state()->get('acquia_search_solr.version')) {
      $info = \Drupal::service('extension.list.module')->getExtensionInfo('acquia_search_solr');
      // Send the version, or at least the core compatibility as a fallback.
      $version = (string) ($info['version'] ?? \Drupal::VERSION);
      \Drupal::state()->set('acquia_search_solr.version', $version);
    }

    return $version;

  }

  /**
   * Get a search core override.
   *
   * The Acquia Search Solr module tries to use this core before any of auto
   * detected cores in case if it's set in the site configuration.
   *
   * @return string|null
   *   Acquia Search Solr search core id.
   */
  public static function getSearchCoreOverride(): ?string {

    return \Drupal::config('acquia_search_solr.settings')->get('override_search_core');

  }

  /**
   * Get extract query handler option.
   *
   * @return string
   *   Extract query handler option.
   *
   * @see \Drupal\acquia_search_solr\Plugin\SolrConnector\AcquiaSearchSolrConnector::getExtractQuery()
   */
  public static function getExtractQueryHandlerOption(): string {

    return \Drupal::config('acquia_search_solr.settings')->get('extract_query_handler_option') ?? 'update/extract';

  }

  /**
   * Determine if the read-only mode is enabled.
   *
   * @return bool
   *   TRUE if the read-only mode forced by the site configuration.
   */
  public static function isReadOnly(): bool {

    return !empty(\Drupal::config('acquia_search_solr.settings')->get('read_only'));

  }

  /**
   * Updates Acquia Search API host.
   *
   * @param string $value
   *   Acquia Search API host.
   */
  public function setApiHost($value) {
    \Drupal::configFactory()->getEditable('acquia_search_solr.settings')->set('api_host', $value)->save();
  }

  /**
   * Updates Acquia Search API key.
   *
   * @param string $value
   *   Acquia Search API key.
   */
  public function setApiKey($value) {
    \Drupal::state()->set('acquia_search_solr.api_key', $value);
  }

  /**
   * Updates Acquia Subscription identifier.
   *
   * @param string $value
   *   Acquia Subscription identifier.
   */
  public function setIdentifier($value) {
    \Drupal::state()->set('acquia_search_solr.identifier', $value);
  }

  /**
   * Updates Acquia Application UUID.
   *
   * @param string $value
   *   Acquia Application UUID.
   */
  public function setUuid($value) {
    \Drupal::state()->set('acquia_search_solr.uuid', $value);
  }

  /**
   * Deletes all data stored in State.
   */
  public function deleteAllData() {
    \Drupal::state()->deleteMultiple([
      'acquia_search_solr.api_key',
      'acquia_search_solr.identifier',
      'acquia_search_solr.uuid',
      'acquia_search_solr.version',
    ]);
  }

}
