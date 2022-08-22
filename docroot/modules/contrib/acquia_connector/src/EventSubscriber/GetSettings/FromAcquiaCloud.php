<?php

namespace Drupal\acquia_connector\EventSubscriber\GetSettings;

use Drupal\acquia_connector\AcquiaConnectorEvents;
use Drupal\acquia_connector\Event\AcquiaSubscriptionSettingsEvent;
use Drupal\acquia_connector\Settings;
use Drupal\acquia_connector\SiteProfile\SiteProfile;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Gets the ContentHub Server settings from environment variable.
 */
class FromAcquiaCloud implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * Array containing the necessary environment variable keys.
   */
  const ENVIRONMENT_VARIABLES = [
    'AH_SITE_ENVIRONMENT',
    'AH_SITE_NAME',
    'AH_SITE_GROUP',
    'AH_APPLICATION_UUID',
  ];

  /**
   * Acquia Connector logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Drupal messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Site Profile Service.
   *
   * @var \Drupal\acquia_connector\SiteProfile\SiteProfile
   */
  protected $siteProfile;

  /**
   * State Service.
   *
   * @var \Drupal\Core\State\State
   */
  protected $state;

  /**
   * Constructor for getting settings from Acquia Cloud.
   *
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   Acquia Connector logger channel.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   Drupal messenger interface.
   * @param \Drupal\acquia_connector\SiteProfile\SiteProfile $site_profile
   *   Site Profile service.
   * @param \Drupal\Core\State\StateInterface $state
   *   Drupal State.
   */
  public function __construct(LoggerChannelInterface $logger, MessengerInterface $messenger, SiteProfile $site_profile, StateInterface $state) {
    $this->logger = $logger;
    $this->messenger = $messenger;
    $this->siteProfile = $site_profile;
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[AcquiaConnectorEvents::GET_SETTINGS][] = ['onGetSettings', 100];
    return $events;
  }

  /**
   * Extract settings from environment and create a Settings object.
   *
   * @param \Drupal\acquia_connector\Event\AcquiaSubscriptionSettingsEvent $event
   *   The dispatched event.
   *
   * @see \Acquia\ContentHubClient\Settings
   */
  public function onGetSettings(AcquiaSubscriptionSettingsEvent $event) {
    $metadata = [];
    // Replace with getenv() once platform is updated in DIT-185.
    foreach (self::ENVIRONMENT_VARIABLES as $var) {
      if (!empty($_ENV[$var])) {
        $metadata[$var] = $_ENV[$var];
      }
    }
    // If the expected Acquia cloud environment variables are missing, return.
    if (count($metadata) !== count(self::ENVIRONMENT_VARIABLES)) {
      return;
    }

    global $config;
    // Mock up a subscription array to pass in the UUID.
    $sub['uuid'] = $metadata['AH_APPLICATION_UUID'];

    // Use the state service since customers can override subscription data.
    $state = $this->state->getMultiple([
      'acquia_connector.key',
      'acquia_connector.identifier',
      'spi.site_name',
      'spi.site_machine_name',
    ]);

    $settings = new Settings(
      $event->getConfig(),
      $state['acquia_connector.identifier'] ?? $config['ah_network_identifier'],
      $state['acquia_connector.key'] ?? $config['ah_network_key'],
      $state['spi.site_name'] ?? $this->siteProfile->checkAcquiaHosted() ? $metadata['AH_SITE_ENVIRONMENT'] . '_' . $metadata['AH_SITE_NAME'] : '',
      $state['spi.site_machine_name'] ?? $this->siteProfile->getMachineName($sub),
      $metadata
    );

    $event->setProvider('acquia_cloud');
    $event->setSettings($settings);
    $event->stopPropagation();
  }

}
