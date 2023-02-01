<?php

namespace Drupal\acquia_search\EventSubscriber\PossibleCores;

use Drupal\acquia_connector\Subscription;
use Drupal\acquia_search\AcquiaSearchEvents;
use Drupal\acquia_search\Event\AcquiaPossibleCoresEvent;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Site\Settings as CoreSettings;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Gets the Acquia Search Server settings from Drupal's settings.
 */
class LocalOverride implements EventSubscriberInterface {

  /**
   * Acquia subscription service.
   *
   * @var \Drupal\acquia_connector\Subscription
   */
  protected $subscription;

  /**
   * Indicates if the local override is using deprecated settings.
   *
   * @var bool
   */
  protected $deprecated;

  /**
   * Messenger Service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Old Acquia Search v3 config override.
   *
   * @var string
   */
  protected $acquiaSearchConfig;

  /**
   * Old Acquia Search Solr config override.
   *
   * @var string
   */
  protected $acquiaSearchSolrConfig;

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Get Possible Cores from local settings file.
   *
   * @param \Drupal\acquia_connector\Subscription $subscription
   *   Acquia Subscription Service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config Factory.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   Drupal Messenger Service.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   */
  public function __construct(Subscription $subscription, ConfigFactoryInterface $config_factory, MessengerInterface $messenger, RouteMatchInterface $route_match) {
    $this->deprecated = $config_factory->get('acquia_search.settings')->get('override_search_core') || $config_factory->get('acquia_search_solr.settings')->get('override_search_core');
    $this->acquiaSearchConfig = $config_factory->get('acquia_search.settings')->get('override_search_core') ?? '';
    $this->acquiaSearchSolrConfig = $config_factory->get('acquia_search_solr.settings')->get('override_search_core') ?? '';
    $this->messenger = $messenger;
    $this->subscription = $subscription;
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
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

    // Show deprecated config message on Search config pages.
    if ($this->deprecated && str_contains($this->routeMatch->getRouteName() ?? '', 'search_api')) {
      $this->messenger->addWarning("Acquia Search detected deprecated config settings. Please review README.txt and update 'override_search_core' setting to override.");
    }

    if ($this->acquiaSearchConfig) {
      $core = $this->acquiaSearchConfig;
      $event->addPossibleCore($core);
      $event->setReadOnly(FALSE);
      // @phpstan-ignore-next-line
      $event->stopPropagation();
    }

    if ($this->acquiaSearchSolrConfig) {
      $core = $this->acquiaSearchSolrConfig;
      $event->addPossibleCore($core);
      $event->setReadOnly(FALSE);
      // @phpstan-ignore-next-line
      $event->stopPropagation();
    }

    $search_settings = CoreSettings::get('acquia_search');
    if (is_array($search_settings)) {
      $readonly = $search_settings['read_only'] ?? FALSE;
      $event->setReadOnly($readonly);

      if (isset($search_settings['override_search_core'])) {
        $event->addPossibleCore($search_settings['override_search_core']);
        // @phpstan-ignore-next-line
        $event->stopPropagation();
      }
    }
  }

}
