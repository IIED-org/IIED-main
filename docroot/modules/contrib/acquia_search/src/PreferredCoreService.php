<?php

namespace Drupal\acquia_search;

use Drupal\acquia_connector\Subscription;
use Drupal\acquia_search\Event\AcquiaPossibleCoresEvent;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Preferred Core Service.
 *
 * Fetches the available and preferred cores based on env and subscription.
 *
 * @package Drupal\acquia_search
 */
class PreferredCoreService {

  /**
   * Event Dispatcher Service.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $dispatcher;

  /**
   * Preferred search core.
   *
   * @var array
   */
  protected $preferredCore;

  /**
   * Readonly Status of Acquia Search.
   *
   * @var bool
   */
  protected $coreReadonly = TRUE;

  /**
   * Module Handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The Search API server ID.
   *
   * @var string
   */
  private $serverId;

  /**
   * Acquia connector subscription.
   *
   * @var \Drupal\acquia_connector\Subscription
   */
  protected $subscription;

  /**
   * Acquia search api client.
   *
   * @var \Drupal\acquia_search\AcquiaSearchApiClient
   */
  protected $acquiaSearchApiClient;

  /**
   * Preferred Search Core Service constructor.
   *
   *   E.g.
   *     [
   *       [
   *         'balancer' => 'useast11-c4.acquia-search.com',
   *         'core_id' => 'WXYZ-12345.dev.mysitedev',
   *       ],
   *     ].
   *
   * @param string $server_id
   *   The Search API server ID.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher
   *   The event dispatcher.
   * @param \Drupal\acquia_connector\Subscription $subscription
   *   Acquia Subscription Service.
   * @param \Drupal\acquia_search\AcquiaSearchApiClient $acquia_search_api_client
   *   Acquia Search API Client.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler (for deprecated alter hook).
   */
  public function __construct(string $server_id, EventDispatcherInterface $dispatcher, Subscription $subscription, AcquiaSearchApiClient $acquia_search_api_client, ModuleHandlerInterface $module_handler) {
    $this->serverId = $server_id;
    $this->dispatcher = $dispatcher;
    $this->subscription = $subscription;
    $this->acquiaSearchApiClient = $acquia_search_api_client;
    $this->moduleHandler = $module_handler;
  }

  /**
   * Returns a formatted list of Available cores.
   *
   * @return array
   *   The Available Cores.
   */
  public function getListOfAvailableCores() {
    $cores = $this->getAvailableCores() ?? [];
    // We use core id as a key.
    return array_keys($cores);
  }

  /**
   * Returns core IDs available in subscription.
   */
  public function getAvailableCores() {
    // When there was an Acquia Search API failure, or we are able to connect,
    // however no cores were found return an empty array.
    $cores = drupal_static('acquia_search_available_cores');
    if ($cores === NULL) {
      $cores = $this->acquiaSearchApiClient->getSearchIndexes();
    }
    if (empty($cores)) {
      return [];
    }

    // We use core id as a key.
    return $cores;
  }

  /**
   * Returns expected core ID based on the current site configs.
   *
   * @return string
   *   Core ID.
   */
  public function getPreferredCoreId() {
    $core = $this->getPreferredCore();
    return $core['core_id'] ?? NULL;
  }

  /**
   * Returns expected core host based on the current site configs.
   *
   * @return string|null
   *   Hostname.
   */
  public function getPreferredCoreHostname() {
    $core = $this->getPreferredCore();
    return $core['balancer'] ?? NULL;
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
    // If we don't have a subscription, stop.
    if (!$this->subscription->isActive()) {
      return NULL;
    }

    // Force refresh of subscription data if Acquia Search isn't here.
    if (!isset($this->subscription->getSubscription()['acquia_search'])) {
      $this->subscription->getSubscription(TRUE);
    }

    $possible_cores = $this->getListOfPossibleCores();
    $available_cores = $this->getAvailableCores();

    foreach ($possible_cores as $possible_core) {
      foreach ($available_cores as $available_core) {
        if ($possible_core === $available_core['core_id']) {
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
    $event = new AcquiaPossibleCoresEvent($this->serverId, $possible_core_ids);
    $this->dispatcher->dispatch($event, AcquiaSearchEvents::GET_POSSIBLE_CORES);
    $this->coreReadonly = $event->isReadOnly();
    $possible_cores = $original_possible_cores = $event->getPossibleCores();
    $deprecated_message = 'This hook is deprecated in acquia_search:3.1.0 and is removed from acquia_search:4.0.0. Please use the "acquia_search.acquia_search_get_possible_cores" event instead.';
    $this->moduleHandler->alterDeprecated($deprecated_message, 'acquia_search_get_list_of_possible_cores', $possible_cores);
    // Override readonly if the possible cores changed in the alter hook.
    if (!empty(array_diff($possible_cores, $original_possible_cores))) {
      $this->coreReadonly = FALSE;
    }
    return $possible_cores;
  }

  /**
   * Returns Read Only Status for a preferred core.
   *
   * @return bool
   *   Read Only Status.
   */
  public function isReadOnly() {
    return $this->coreReadonly;
  }

}
