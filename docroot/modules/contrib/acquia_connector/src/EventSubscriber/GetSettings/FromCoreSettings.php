<?php

namespace Drupal\acquia_connector\EventSubscriber\GetSettings;

use Drupal\acquia_connector\AcquiaConnectorEvents;
use Drupal\acquia_connector\Event\AcquiaSubscriptionSettingsEvent;
use Drupal\acquia_connector\Settings;
use Drupal\Core\Site\Settings as CoreSettings;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Gets the Acquia Connector Server settings from Drupal's settings.
 */
class FromCoreSettings implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[AcquiaConnectorEvents::GET_SETTINGS][] = ['onGetSettings', 100];
    return $events;
  }

  /**
   * Gets a prebuilt Settings object from Drupal's settings file.
   *
   * @param \Drupal\acquia_connector\Event\AcquiaSubscriptionSettingsEvent $event
   *   The dispatched event.
   *
   * @see \Drupal\acquia_connector\Settings
   */
  public function onGetSettings(AcquiaSubscriptionSettingsEvent $event) {
    $settings = CoreSettings::get('acquia_connector.settings');
    if ($settings instanceof Settings) {
      $event->setSettings($settings);
      $event->setProvider('core_settings');
      $event->stopPropagation();
    }
  }

}