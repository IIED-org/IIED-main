<?php

namespace Drupal\acquia_connector;

use Drupal\acquia_connector\Client\ClientFactory;
use Drupal\acquia_connector\Event\AcquiaSubscriptionDataEvent;
use Drupal\acquia_connector\Event\AcquiaSubscriptionSettingsEvent;
use Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\State\StateInterface;

/**
 * Acquia Subscription service.
 *
 * The Acquia Subscription service is the public way other items can access
 * Acquia's services via connector. There is a settings object that is invoked
 * via an Event Subscriber, to fetch settings from envvars, settings.php or the
 * state system.
 *
 * Acquia Subscription data is always stored in state, and is not part of the
 * settings object.
 *
 * @package Drupal\acquia_connector.
 */
class Subscription {

  /**
   * Errors defined by Acquia.
   */
  const NOT_FOUND = 1000;
  const KEY_MISMATCH = 1100;
  const EXPIRED = 1200;
  const REPLAY_ATTACK = 1300;
  const KEY_NOT_FOUND = 1400;
  const MESSAGE_FUTURE = 1500;
  const MESSAGE_EXPIRED = 1600;
  const MESSAGE_INVALID = 1700;
  const VALIDATION_ERROR = 1800;
  const PROVISION_ERROR = 9000;

  /**
   * Subscription message lifetime defined by Acquia.
   */
  // 15 * 60.
  const MESSAGE_LIFETIME = 900;

  /**
   * Event Dispatcher Service.
   *
   * @var \Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher
   */
  protected $dispatcher;

  /**
   * Cache.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * Settings Provider.
   *
   * @var string
   */
  protected $settingsProvider;

  /**
   * Settings object.
   *
   * @var \Drupal\acquia_connector\Settings
   */
  protected $settings;

  /**
   * Raw Acquia subscription data.
   *
   * @var array
   */
  protected $subscriptionData;

  /**
   * Connector Client.
   *
   * @var \Drupal\acquia_connector\Client\ClientFactory
   */
  protected $clientFactory;

  /**
   * Drupal config.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Drupal State Service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Acquia Subscription Constructor.
   *
   * @param \Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher $dispatcher
   *   The event dispatcher.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   Cache Backend Service.
   * @param \Drupal\acquia_connector\Client\ClientFactory $client_factory
   *   The acquia connector client factory.
   * @param \Drupal\Core\State\StateInterface $state
   *   Drupal State System.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config Factory.
   */
  public function __construct(ContainerAwareEventDispatcher $dispatcher, CacheBackendInterface $cache, ClientFactory $client_factory, StateInterface $state, ConfigFactoryInterface $config_factory) {
    $this->dispatcher = $dispatcher;
    $this->cache = $cache;
    $this->state = $state;
    $this->clientFactory = $client_factory;
    $this->configFactory = $config_factory;
    $this->populateSettings();
  }

  /**
   * Call the event to populate Acquia Connector settings.
   */
  public function populateSettings() {
    $event = new AcquiaSubscriptionSettingsEvent($this->configFactory);
    $this->dispatcher->dispatch($event, AcquiaConnectorEvents::GET_SETTINGS);
    $this->settings = $event->getSettings();
    $this->settingsProvider = $event->getProvider();
  }

  /**
   * Retreives the stored subscription.
   *
   * @return \Drupal\acquia_connector\Settings|false
   *   The Connector Settings Object.
   */
  public function getSettings() {
    return $this->settings ?? FALSE;
  }

  /**
   * Gets the subscription provider from the subscription event for settings.
   *
   * @return string
   *   The name of settings' provider.
   */
  public function getProvider() {
    return $this->settingsProvider;
  }

  /**
   * Retrieve the Acquia Subscription.
   *
   * @return array
   *   The Raw Subscription Data.
   */
  public function getSubscription($refresh = NULL, $body = []) {
    // If Settings do not exist, we have no subscription to fetch.
    if (!$this->hasCredentials()) {
      // Ensure subscription data is scrubbed.
      $this->cache->delete('acquia_connector.subscription_data');
      return ['active' => FALSE];
    }
    // Used the cached data if refresh is NULL or FALSE.
    if (isset($this->subscriptionData) && $refresh !== TRUE) {
      return $this->subscriptionData;
    }
    $cache = $this->cache->get('acquia_connector.subscription_data');
    if (!empty($cache->data) && $refresh !== TRUE) {
      return $cache->data;
    }
    // If refresh is TRUE or NULL get subscription data from Acquia.
    $client = $this->clientFactory->getClient($this->settings);
    try {
      $subscriptionData = $client->getSubscription($this->settings->getIdentifier(), $this->settings->getSecretKey(), $body);
    }
    catch (ConnectorException $e) {
      return ['active' => FALSE];
    }
    // Refresh the subscription from Acquia
    // Allow other modules to add metadata to the subscription.
    $event = new AcquiaSubscriptionDataEvent($this->configFactory, $subscriptionData);
    $this->dispatcher->dispatch($event, AcquiaConnectorEvents::GET_SUBSCRIPTION);
    $this->subscriptionData = $event->getData();
    $this->cache->set('acquia_connector.subscription_data', $this->subscriptionData);

    return $this->subscriptionData;
  }

  /**
   * Delete any subscription data held in the database.
   */
  public function delete() {
    $this->cache->set('acquia_connector.subscription_data', ['active' => FALSE]);
    $this->state->delete('acquia_subscription_data');
    $this->state->delete('spi.site_name');
    $this->state->delete('spi.site_machine_name');
  }

  /**
   * Helper function to check if an identifier and key exist.
   */
  public function hasCredentials() {
    return $this->settings->getIdentifier() && $this->settings->getSecretKey();
  }

  /**
   * Function to check if an $cache is initialised.
   */
  protected function initialiseCacheBin() {
    if (empty($this->cache)) {
      $cache = \Drupal::cache();
      $this->cache = $cache;
    }
  }

  /**
   * Helper function to check if the site has an active subscription.
   */
  public function isActive() {
    $this->initialiseCacheBin();
    $active = FALSE;
    // Subscription cannot be active if we have no credentials.
    if (self::hasCredentials()) {
      if ($cache = $this->cache->get('acquia_connector.subscription_data')) {
        if (is_array($cache->data) && $cache->expire > time()) {
          return !empty($cache->data['active']);
        }
      }
      // Only retrieve cached subscription at this time.
      $subscription = $this->getSubscription(FALSE);

      // If we don't have a timestamp, or timestamp is less than a day, fetch.
      if (!isset($subscription['timestamp']) || (isset($subscription['timestamp']) && (time() - $subscription['timestamp'] > 60 * 60 * 24))) {
        try {
          $subscription = $this->getSubscription(TRUE, ['no_heartbeat' => 1]);
          $this->cache->set('acquia_connector.subscription_data', $subscription, time() + (60 * 60));
        }
        catch (ConnectorException $e) {
        }
      }
      $active = !empty($subscription['active']);
    }
    return $active;
  }

  /**
   * Return an error message by the error code.
   *
   * Returns an error message for the most recent (failed) attempt to connect
   * to the Acquia during the current page request. If there were no failed
   * attempts, returns FALSE.
   *
   * This function assumes that the most recent error came from the Acquia;
   * otherwise, it will not work correctly.
   *
   * @param int $errno
   *   Error code defined by the module.
   *
   * @return mixed
   *   The error message string or FALSE.
   */
  public function connectionErrorMessage($errno) {
    if ($errno) {
      switch ($errno) {
        case self::NOT_FOUND:
          return t('The identifier you have provided does not exist at Acquia or is expired. Please make sure you have used the correct value and try again.');

        case self::EXPIRED:
          return t('Your Acquia Subscription subscription has expired. Please renew your subscription so that you can resume using Acquia services.');

        case self::MESSAGE_FUTURE:
          return t('Your server is unable to communicate with Acquia due to a problem with your clock settings. For security reasons, we reject messages that are more than @time ahead of the actual time recorded by our servers. Please fix the clock on your server and try again.', ['@time' => \Drupal::service('date.formatter')->formatInterval(Subscription::MESSAGE_LIFETIME)]);

        case self::MESSAGE_EXPIRED:
          return t('Your server is unable to communicate with Acquia due to a problem with your clock settings. For security reasons, we reject messages that are more than @time older than the actual time recorded by our servers. Please fix the clock on your server and try again.', ['@time' => \Drupal::service('date.formatter')->formatInterval(Subscription::MESSAGE_LIFETIME)]);

        case self::VALIDATION_ERROR:
          return t('The identifier and key you have provided for the Acquia Subscription do not match. Please make sure you have used the correct values and try again.');

        default:
          return t('There is an error communicating with the Acquia Subscription at this time. Please check your identifier and key and try again.');
      }
    }
    return FALSE;
  }

}
