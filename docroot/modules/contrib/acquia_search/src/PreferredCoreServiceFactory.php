<?php

declare(strict_types=1);

namespace Drupal\acquia_search;

use Drupal\acquia_connector\Subscription;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Factory for creating preferred core service instances.
 */
class PreferredCoreServiceFactory {

  /**
   * Event Dispatcher Service.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  private $dispatcher;

  /**
   * Module Handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  private $moduleHandler;

  /**
   * The subscription.
   *
   * @var \Drupal\acquia_connector\Subscription
   */
  private $subscription;

  /**
   * The API client for Acquia Search.
   *
   * @var \Drupal\acquia_search\AcquiaSearchApiClient
   */
  private $acquiaSearchApiClient;

  /**
   * Instantiated existing instances for server IDs.
   *
   * @var \Drupal\acquia_search\PreferredCoreService[]
   */
  private $instances = [];

  /**
   * Constructs a new PreferredCoreServiceFactory object.
   *
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher
   *   The event dispatcher.
   * @param \Drupal\acquia_connector\Subscription $subscription
   *   Acquia Subscription Service.
   * @param \Drupal\acquia_search\AcquiaSearchApiClient $acquia_search_api_client
   *   Acquia Search API Client.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler (for deprecated alter hook).
   */
  public function __construct(EventDispatcherInterface $dispatcher, Subscription $subscription, AcquiaSearchApiClient $acquia_search_api_client, ModuleHandlerInterface $module_handler) {
    $this->dispatcher = $dispatcher;
    $this->subscription = $subscription;
    $this->acquiaSearchApiClient = $acquia_search_api_client;
    $this->moduleHandler = $module_handler;
  }

  /**
   * Gets a preferred core service instance for a Search API server.
   *
   * @param string $server_id
   *   The Search API server ID.
   *
   * @return \Drupal\acquia_search\PreferredCoreService
   *   The preferred core service instance.
   */
  public function get(string $server_id): PreferredCoreService {
    if (!isset($this->instances[$server_id])) {
      $this->instances[$server_id] = new PreferredCoreService(
        $server_id,
        $this->dispatcher,
        $this->subscription,
        $this->acquiaSearchApiClient,
        $this->moduleHandler
      );
    }
    return $this->instances[$server_id];
  }

}
