<?php

namespace Drupal\acquia_search;

use Drupal\acquia_search\Helper\Storage;

/**
 * Return the Preferred search core for Solr.
 *
 * @package Drupal\acquia_search
 */
class PreferredSearchCore {

  /**
   * Acquia Subscription Identifier.
   *
   * @var string
   */
  protected $acquiaIdentifier;

  /**
   * Acquia environment.
   *
   * @var string
   */
  protected $ahEnv;

  /**
   * Acquia database name.
   *
   * @var string
   */
  protected $ahDbRole;

  /**
   * Sites folder name.
   *
   * @var string
   */
  protected $sitesFolderName;

  /**
   * Available search cores.
   *
   * @var array
   */
  protected $availableCores;

  /**
   * Preferred search core.
   *
   * @var array
   */
  protected $preferredCore;

  /**
   * ExpectedCoreService constructor.
   *
   * @param string $acquia_identifier
   *   E.g. 'WXYZ-12345'.
   * @param string $ah_env
   *   E.g. 'dev', 'stage' or 'prod'.
   * @param string $sites_folder_name
   *   E.g. 'default'.
   * @param string $ah_db_role
   *   E.g. 'my_site_db'.
   * @param array $available_cores
   *   E.g.
   *     [
   *       [
   *         'balancer' => 'useast11-c4.acquia-search.com',
   *         'core_id' => 'WXYZ-12345.dev.mysitedev',
   *       ],
   *     ].
   */
  public function __construct($acquia_identifier, $ah_env, $sites_folder_name, $ah_db_role, array $available_cores) {

    $this->acquiaIdentifier = $acquia_identifier;
    $this->ahEnv = $ah_env;
    $this->sitesFolderName = $sites_folder_name;
    $this->ahDbRole = $ah_db_role;
    $this->availableCores = $available_cores;

  }

  /**
   * Returns core IDs available in subscription.
   */
  public function getListOfAvailableCores() {

    // We user core id as a key.
    return array_keys($this->availableCores);

  }

  /**
   * Returns expected core ID based on the current site configs.
   *
   * @return string
   *   Core ID.
   */
  public function getPreferredCoreId() {

    $core = $this->getPreferredCore();

    return $core['core_id'];

  }

  /**
   * Returns expected core host based on the current site configs.
   *
   * @return string
   *   Hostname.
   */
  public function getPreferredCoreHostname() {

    $core = $this->getPreferredCore();

    return $core['balancer'];

  }

  /**
   * Determines whether the expected core ID matches any available core IDs.
   *
   * The list of available core IDs is set by Acquia and comes within the
   * Acquia Subscription information.
   *
   * @return bool
   *   True if the expected core ID available to use with Acquia.
   */
  public function isPreferredCoreAvailable() {

    return (bool) $this->getPreferredCore();

  }

  /**
   * Returns the preferred core from the list of available cores.
   *
   * @return array|null
   *   NULL or
   *     [
   *       'balancer' => 'useast11-c4.acquia-search.com',
   *       'core_id' => 'WXYZ-12345.dev.mysitedev',
   *     ].
   */
  public function getPreferredCore(): ?array {

    if (!empty($this->preferredCore)) {
      return $this->preferredCore;
    }

    $expected_cores = $this->getListOfPossibleCores();
    $available_cores = $this->availableCores;

    foreach ($expected_cores as $expected_core) {
      foreach ($available_cores as $available_core) {
        if ($expected_core === $available_core['core_id']) {
          $this->preferredCore = $available_core;
          return $this->preferredCore;
        }
      }
    }

    return NULL;

  }

  /**
   * Returns a list of all possible search core IDs.
   *
   * The core IDs are generated based on the current site configuration.
   *
   * @return array
   *   E.g.
   *     [
   *       'WXYZ-12345.dev.mysitedev_db',
   *       'WXYZ-12345.dev.mysitedev_folder1',
   *     ]
   */
  public function getListOfPossibleCores() {

    $possible_core_ids = [];

    // In index naming, we only accept alphanumeric chars.
    $sites_foldername = preg_replace('/[^a-zA-Z0-9]+/', '', $this->sitesFolderName);
    $ah_env = preg_replace('/[^a-zA-Z0-9]+/', '', $this->ahEnv);

    $context = [
      'ah_env' => $ah_env,
      'ah_db_role' => $this->ahDbRole,
      'identifier' => Storage::getIdentifier(),
      'sites_foldername' => $sites_foldername,
    ];

    // The Acquia Search module isn't configured properly.
    if (!Storage::getIdentifier()) {
      // Let other modules arbitrary alter the list possible cores.
      \Drupal::moduleHandler()->alter('acquia_search_get_list_of_possible_cores', $possible_core_ids, $context);
      return $possible_core_ids;
    }

    // The Acquia Search Solr module tries to use this core before any auto
    // detected core in case if it's set in the site configuration.
    if ($override_search_core = Storage::getSearchCoreOverride()) {
      $possible_core_ids[] = $override_search_core;
    }

    if ($ah_env) {
      // When there is an Acquia DB role defined, priority is to pick
      // WXYZ-12345.[env].[db_role], then WXYZ-12345.[env].[site_foldername].
      if ($this->ahDbRole) {
        $possible_core_ids[] = $this->acquiaIdentifier . '.' . $ah_env . '.' . $this->ahDbRole;
      }

      $possible_core_ids[] = $this->acquiaIdentifier . '.' . $ah_env . '.' . $sites_foldername;
    }

    // Let other modules arbitrary alter the list possible cores.
    \Drupal::moduleHandler()->alter('acquia_search_get_list_of_possible_cores', $possible_core_ids, $context);

    return $possible_core_ids;

  }

}
