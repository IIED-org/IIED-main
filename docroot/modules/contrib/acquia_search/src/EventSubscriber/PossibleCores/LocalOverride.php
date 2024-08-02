<?php

namespace Drupal\acquia_search\EventSubscriber\PossibleCores;

use Drupal\acquia_connector\Subscription;
use Drupal\acquia_search\AcquiaSearchEvents;
use Drupal\acquia_search\Event\AcquiaPossibleCoresEvent;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Site\Settings as CoreSettings;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Gets the Acquia Search Server settings from Drupal's settings.
 */
class LocalOverride implements EventSubscriberInterface {

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Acquia subscription service.
   *
   * @var \Drupal\acquia_connector\Subscription
   */
  protected $subscription;

  /**
   * Get Possible Cores from local settings file.
   *
   * @param \Drupal\acquia_connector\Subscription $subscription
   *   Acquia Subscription Service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config Factory.
   */
  public function __construct(Subscription $subscription, ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
    $this->subscription = $subscription;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    // Overridden fires first and supersedes all other possible cores.
    // phpcs:ignore
    $events[AcquiaSearchEvents::GET_POSSIBLE_CORES][] = ['onGetPossibleCores', 9999];
    return $events;
  }

  /**
   * Gets a preset possible core from settings.
   *
   * @param \Drupal\acquia_search\Event\AcquiaPossibleCoresEvent $event
   *   The dispatched event.
   *
   * @see \Drupal\acquia_connector\Settings
   */
  public function onGetPossibleCores(AcquiaPossibleCoresEvent $event) {
    $acquia_search_core = $this->configFactory->get('acquia_search.settings')->get('override_search_core') ?? '';
    if (is_string($acquia_search_core) && $acquia_search_core !== '') {
      $event->addPossibleCore($acquia_search_core);
      $event->setReadOnly(FALSE);
      // @phpstan-ignore-next-line
      $event->stopPropagation();
    }

    $acquia_search_solr_core = $this->configFactory->get('acquia_search_solr.settings')->get('override_search_core') ?? '';
    if (is_string($acquia_search_solr_core) && $acquia_search_solr_core !== '') {
      $event->addPossibleCore($acquia_search_solr_core);
      $event->setReadOnly(FALSE);
      // @phpstan-ignore-next-line
      $event->stopPropagation();
    }

    $search_settings = CoreSettings::get('acquia_search');
    if (is_array($search_settings)) {
      $readonly = $search_settings['read_only'] ?? FALSE;
      $event->setReadOnly($readonly);

      // New core override format that allows overriding the Solr index used by
      // specific Search API servers.
      if (isset($search_settings['server_overrides']) && is_array($search_settings['server_overrides'])) {
        $server_id = $event->getServerId();
        if (isset($search_settings['server_overrides'][$server_id])) {
          $event->addPossibleCore($search_settings['server_overrides'][$server_id]);
          // @phpstan-ignore-next-line
          $event->stopPropagation();
        }
      }
      elseif (isset($search_settings['override_search_core'])) {
        $event->addPossibleCore($search_settings['override_search_core']);
        // @phpstan-ignore-next-line
        $event->stopPropagation();
      }
    }
  }

}
