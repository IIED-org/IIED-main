<?php

namespace Drupal\acquia_search\EventSubscriber\AcquiaSubscriptionData;

use Drupal\acquia_connector\Event\AcquiaSubscriptionDataEvent;
use Drupal\Core\Extension\ModuleExtensionList;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Add metadata from Acquia Search to Acquia Connector's subscription.
 */
class AcquiaSearchData implements EventSubscriberInterface {

  /**
   * The module extension list.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $moduleExtensionList;

  /**
   * Add metadata constructor.
   *
   * @param \Drupal\Core\Extension\ModuleExtensionList $extension_list_module
   *   The module extension list.
   */
  public function __construct(ModuleExtensionList $extension_list_module) {
    $this->moduleExtensionList = $extension_list_module;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    // Don't use AcquiaConnectorEvents::GET_SUBSCRIPTION, due to a race
    // condition caused by the update system when the class may not exist yet.
    $events['acquia_connector_get_subscription'][] = [
      'onGetSubscriptionData', 100,
    ];
    return $events;
  }

  /**
   * Gets a prebuilt Settings object from Drupal's settings file.
   *
   * @param \Drupal\acquia_connector\Event\AcquiaSubscriptionDataEvent $event
   *   The dispatched event.
   *
   * @see \Drupal\acquia_connector\Settings
   */
  public function onGetSubscriptionData(AcquiaSubscriptionDataEvent $event) {
    $config = $event->getConfig('acquia_search.settings');
    $subscription_data = $event->getData();

    $subscription_data['acquia_search'] = array_diff_key($config->get(), ['_core' => TRUE]);
    // When updating to v3, the api_host may not exist, manually put it in.
    if (!isset($subscription_data['acquia_search']['api_host'])) {
      $subscription_data['acquia_search']['api_host'] = 'https://api.sr-prod02.acquia.com';
    }

    $info = $this->moduleExtensionList->getExtensionInfo('acquia_search');
    $subscription_data['acquia_search']['module_version'] = (string) ($info['version'] ?? \Drupal::VERSION);

    // Add Acquia Search module version to subscription data.
    $event->setData($subscription_data);
  }

}
