<?php

namespace Drupal\acquia_search\EventSubscriber\PossibleCores;

use Drupal\acquia_connector\Subscription;
use Drupal\acquia_search\AcquiaSearchEvents;
use Drupal\acquia_search\Event\AcquiaPossibleCoresEvent;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Database;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Gets the Acquia Search Server settings from Cloud environment variables.
 *
 * @phpcs:disable SlevomatCodingStandard.Variables.DisallowSuperGlobalVariable
 */
class AcquiaHosting implements EventSubscriberInterface {

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
   * Drupal database connection.
   *
   * Used for fetching the dbrole for a core.
   *
   * @var \Drupal\Core\Database\Database
   */
  protected $database;

  /**
   * Get Possible Cores from Cloud Constructor.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   Drupal Database Service.
   * @param \Drupal\acquia_connector\Subscription $subscription
   *   Acquia subscription service.
   * @param \Drupal\Core\SitePathFactory|string $site_path
   *   Drupal Site Path.
   */
  public function __construct(Connection $database, Subscription $subscription, $site_path) {
    $this->database = $database;
    $sites_foldername = substr($site_path, strrpos($site_path, '/') + 1);
    $this->sitesFolderName = preg_replace('/[^a-zA-Z0-9]+/', '', $sites_foldername);
    $this->subscription = $subscription;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    // phpcs:ignore
    $events[AcquiaSearchEvents::GET_POSSIBLE_CORES][] = ['onGetPossibleCores', 100];
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
    // Return if the settings provider isn't acquia_cloud.
    if ($this->subscription->getProvider() !== 'acquia_cloud') {
      return;
    }

    $ahEnv = $this->subscription->getSettings()->getMetadata('AH_SITE_ENVIRONMENT');
    $ahEnv = preg_replace('/[^a-zA-Z0-9]+/', '', $ahEnv);

    $options = $this->database->getConnectionOptions();
    $connection_info = Database::getAllConnectionInfo();
    $ahDbRole = $this->getAhDatabaseRole($options, $connection_info);

    // ACSF Sites should use the pre-configured env and db roles instead.
    if (isset($GLOBALS['gardens_site_settings'])) {
      $ahEnv = $GLOBALS['gardens_site_settings']['env'];
      $ahDbRole = $GLOBALS['gardens_site_settings']['conf']['acsf_db_name'];
    }

    $acquiaIdentifier = $this->subscription->getSettings()->getIdentifier();

    if ($ahEnv) {
      // When there is an Acquia DB role defined, priority is to pick
      // WXYZ-12345.[env].[db_role], then WXYZ-12345.[env].[site_foldername].
      if ($ahDbRole) {
        $event->addPossibleCore($acquiaIdentifier . '.' . $ahEnv . '.' . $ahDbRole);
      }

      // If there is a default core defined (eg: WXYZ-12345.[env]) use it next.
      $event->addPossibleCore($acquiaIdentifier . '.' . $ahEnv);

      // Last chance, search for folder if dbrole and default are missing.
      $event->addPossibleCore($acquiaIdentifier . '.' . $ahEnv . '.' . $this->sitesFolderName);
      // Backward compatibility with dbName based indices.
      $event->addPossibleCore($acquiaIdentifier . '.' . $ahEnv . '.' . $options['database']);
      $event->setReadOnly(FALSE);
    }
  }

  /**
   * Return the name of the Acquia "DB Role".
   *
   * Acquia "DB Role" is in use when running inside an Acquia environment.
   *
   * @param array $options
   *   Current connection options.
   * @param array $connection_info
   *   ALl databases list.
   *
   * @return string
   *   Database role.
   */
  protected function getAhDatabaseRole(array $options, array $connection_info): string {
    $ah_db_name = $options['database'];
    // Scan all the available Databases and look for the currently-used DB name.
    foreach ($connection_info as $db_role => $db_array) {
      // Ignore the "default" connection, because even though it may match the
      // currently-used DB connection, this entry always exists and its key
      // won't match the AH "DB Role".
      if ($db_role == 'default') {
        continue;
      }
      if ($db_array['default']['database'] == $ah_db_name) {
        // In database role naming, we only accept alphanumeric chars.
        $pattern = '/[^a-zA-Z0-9_]+/';
        $db_role = preg_replace($pattern, '', $db_role);
        return $db_role;
      }
    }
    return '';
  }

}
