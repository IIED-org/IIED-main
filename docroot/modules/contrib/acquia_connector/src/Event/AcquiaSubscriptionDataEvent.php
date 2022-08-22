<?php

namespace Drupal\acquia_connector\Event;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Component\EventDispatcher\Event;

/**
 * The event dispatched to find settings for Acquia Connector.
 */
class AcquiaSubscriptionDataEvent extends Event {

  /**
   * Raw subscription data to alter.
   *
   * @var array
   */
  protected $subscriptionData;

  /**
   * Config Factory for events to fetch their own configs.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Pass in connector config by default to all events.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Acquia Connector settings.
   * @param array $subscription_data
   *   Raw Subscription Data.
   */
  public function __construct(ConfigFactoryInterface $config_factory, array $subscription_data) {
    $this->configFactory = $config_factory;
    $this->subscriptionData = $subscription_data;
  }

  /**
   * Gets the Acquia Connector settings object.
   *
   * @return array
   *   The Acquia Subscription data.
   */
  public function getData() {
    return $this->subscriptionData;
  }

  /**
   * Return static config for an event subscriber.
   *
   * @return \Drupal\Core\Config\Config
   *   The Config Object.
   */
  public function getConfig($config_settings) {
    return $this->configFactory->get($config_settings);
  }

  /**
   * Set the subscription data.
   *
   * Event subscribers to this event should be mindful to use the
   * NestedArray::mergeDeepArray() method to merge data together and not
   * overwrite other event subscriber's data.
   *
   * @param array $data
   *   Data to set.
   */
  public function setData(array $data): void {
    $this->subscriptionData = $data;
  }

}
