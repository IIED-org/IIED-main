<?php

namespace Drupal\acquia_search\EventSubscriber\PossibleCores;

use Drupal\acquia_connector\Subscription;
use Drupal\acquia_search\AcquiaSearchApiClient;
use Drupal\acquia_search\AcquiaSearchEvents;
use Drupal\acquia_search\Event\AcquiaPossibleCoresEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Fallback Default core to readonly production server.
 */
class DefaultCore implements EventSubscriberInterface {

  /**
   * Site Folder Name.
   *
   * @var false|string
   */
  protected $sitesFolderName;

  /**
   * Acquia subscription service.
   *
   * @var \Drupal\acquia_connector\Subscription
   */
  protected $subscription;

  /**
   * Acquia Search API Client.
   *
   * @var \Drupal\acquia_search\AcquiaSearchApiClient
   */
  protected $acquiaSearchApiClient;

  /**
   * Get Possible Cores from Cloud Constructor.
   *
   * @param \Drupal\acquia_connector\Subscription $subscription
   *   Acquia Subscription Service.
   * @param \Drupal\acquia_search\AcquiaSearchApiClient $search_api_client
   *   Acquia Search API Service.
   * @param \Drupal\Core\SitePathFactory|string $site_path
   *   Drupal Site Path.
   */
  public function __construct(Subscription $subscription, AcquiaSearchApiClient $search_api_client, $site_path) {
    $sites_foldername = substr($site_path, strrpos($site_path, '/') + 1);
    $this->sitesFolderName = preg_replace('/[^a-zA-Z0-9]+/', '', $sites_foldername);
    $this->subscription = $subscription;
    $this->acquiaSearchApiClient = $search_api_client;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    // phpcs:ignore
    $events[AcquiaSearchEvents::GET_POSSIBLE_CORES][] = ['onGetPossibleCores', -1000];
    return $events;
  }

  /**
   * Gets a prebuilt Settings object from Drupal's settings file.
   *
   * @param \Drupal\acquia_search\Event\AcquiaPossibleCoresEvent $event
   *   The dispatched event.
   *
   * @see \Drupal\acquia_connector\Settings
   */
  public function onGetPossibleCores(AcquiaPossibleCoresEvent $event) {
    // Return if the settings provider is acquia_cloud.
    if ($this->subscription->getProvider() === 'acquia_cloud') {
      return;
    }

    // Return if Acquia Connector is not setup yet.
    if (!$this->subscription->hasCredentials()) {
      return;
    }

    /*
     * First, this looks for a 'default' production core: WXYZ-12345.prod.
     *
     * Second, we look for a production core that matches the sitename, if that
     * sitename isn't 'default'. This is useful for non ACSF multisite.
     *
     * Third, if neither of these exist, we look for the first core in the
     * prod environment: WXYZ-12345.prod.dbname For customers with multiple
     * production databases, this may not return what you want.
     * Customers are encouraged to override in settings.php instead.
     */
    $possible_cores = $this->acquiaSearchApiClient->getSearchIndexes();
    if ($possible_cores === FALSE) {
      return;
    }

    $subscription_id = $this->subscription->getSettings()->getIdentifier();

    if (isset($possible_cores[$subscription_id . '.prod'])) {
      $event->addPossibleCore($subscription_id . '.prod');
      return;
    }

    if (isset($possible_cores[$subscription_id . '.prod.' . $this->sitesFolderName])) {
      $event->addPossibleCore($subscription_id . '.prod.' . $this->sitesFolderName);
      return;
    }

    foreach ($possible_cores as $possible_core) {
      if (strpos($subscription_id . '.' . $possible_core['core_id'], 'prod')) {
        $event->addPossibleCore($possible_core['core_id']);
        return;
      }
    }
  }

}
