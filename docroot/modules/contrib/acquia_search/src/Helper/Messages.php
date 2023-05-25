<?php

namespace Drupal\acquia_search\Helper;

use Drupal\acquia_search\Plugin\search_api\backend\AcquiaSearchSolrBackend;
use Drupal\acquia_search\Plugin\SolrConnector\SearchApiSolrAcquiaConnector;
use Drupal\Core\Render\Markup;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Class Messages.
 *
 * Contains methods related to the UI messages.
 *
 * @todo make a service
 * @todo test directly.
 */
class Messages {

  /**
   * Generates DSM with read-only message warning.
   */
  public static function showReadOnlyModeWarning() {

    $message = Messages::getReadOnlyModeWarning();

    \Drupal::messenger()->addWarning($message);

  }

  /**
   * Generates DSM with "could not find preferred core" message warning.
   *
   * @param \Drupal\acquia_search\Plugin\search_api\backend\AcquiaSearchSolrBackend $backend
   *   The backend.
   */
  public static function showNoPreferredCoreError(AcquiaSearchSolrBackend $backend) {

    $message = Messages::getNoPreferredCoreError($backend);

    \Drupal::messenger()->addWarning(Markup::create($message));

  }

  /**
   * Returns formatted message if preferred search core is unavailable.
   *
   * @param \Drupal\acquia_search\Plugin\search_api\backend\AcquiaSearchSolrBackend $backend
   *   The backend.
   *
   * @return string
   *   Formatted message.
   */
  public static function getNoPreferredCoreError(AcquiaSearchSolrBackend $backend): string {
    $possible_cores = $backend->getListOfPossibleCores();

    $messages[] = t('Could not find a Solr core corresponding to your website and environment.');

    if (!empty($possible_cores)) {
      $messages[] = t(
        'These cores were expected but not found in your subscription: @list.',
        ['@list' => implode(', ', $possible_cores)]
      );
    }

    $available_cores = $backend->getListOfAvailableCores();
    if (!empty($available_cores)) {
      $messages[] = t(
        'Your subscription contains these cores: @list.',
        ['@list' => implode(', ', $available_cores)]
      );
    }
    else {
      $messages[] = t('Your subscription contains no cores.');
    }

    $messages[] = t(
      'To fix this problem, please read <a href="@url">our documentation</a>.',
      ['@url' => 'https://docs.acquia.com/acquia-search/multiple-cores/']
    );

    return implode(' ', $messages);

  }

  /**
   * Returns formatted message about read-only mode.
   *
   * @return string
   *   Formatted message about read-only mode.
   */
  public static function getReadOnlyModeWarning(): string {

    return (string) t('The read-only mode is set in the configuration of the Acquia Search Solr module.');

  }

  /**
   * Returns formatted message about Acquia Search connection details.
   *
   * @param \Drupal\acquia_search\Plugin\search_api\backend\AcquiaSearchSolrBackend $backend
   *   The backend.
   *
   * @return \Drupal\Component\Render\MarkupInterface|string
   *   Formatted message about Acquia Search connection details.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Drupal\search_api\SearchApiException
   */
  public static function getSearchStatusMessage(AcquiaSearchSolrBackend $backend) {

    /** @var \Drupal\acquia_search\Plugin\search_api\backend\AcquiaSearchSolrBackend $backend */
    $configuration = $backend->getSolrConnector()->getConfiguration();

    $items = [
      self::getServerIdMessage($backend->getServer()->id()),
    ];

    if ($backend->isPreferredCoreAvailable()) {
      $items[] = self::getServerUrlMessage($configuration);

      // Report on the behavior chosen.
      if (isset($configuration['overridden_by_acquia_search'])) {
        $items[] = self::getOverriddenModeMessage($configuration['overridden_by_acquia_search']);
      }

      $items[] = self::getServerAvailabilityMessage($backend);
      $items[] = self::getServerAuthCheckMessage($backend);
    }
    else {
      $items[] = ['#markup' => '<span class="color-error">' . self::getNoPreferredCoreError($backend) . '</span>'];
    }

    $list = ['#theme' => 'item_list', '#items' => $items];
    $list = \Drupal::service('renderer')->renderPlain($list);
    return t('Connection managed by Acquia Search Solr module. @list', [
      '@list' => $list,
    ]);

  }

  /**
   * Get text describing the current override mode.
   *
   * @param int $override
   *   Override mode. Read-only or core auto selected.
   *
   * @return array|\Drupal\Core\StringTranslation\TranslatableMarkup
   *   Text describing the current override mode.
   */
  public static function getOverriddenModeMessage(int $override) {

    switch ($override) {
      case SearchApiSolrAcquiaConnector::READ_ONLY:
        return ['#markup' => '<span class="color-warning">' . t('Acquia Search Solr module automatically enforced read-only mode on this connection.') . '</span>'];

      case SearchApiSolrAcquiaConnector::OVERRIDE_AUTO_SET:
        return t('Acquia Search Solr module automatically selected the proper Solr connection based on the detected environment and configuration.');

    }

  }

  /**
   * Get text showing the current URL based on configuration.
   *
   * @param array $configuration
   *   A configuration array containing scheme, host, port and path.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   Translatable markup showing the current URL based on configuration.
   */
  public static function getServerUrlMessage(array $configuration): TranslatableMarkup {

    if (empty($configuration['host'])) {
      $url = t('N/A');
    }
    else {
      $url = $configuration['scheme'] . '://' . $configuration['host'] . ':' . $configuration['port'] . '/' . $configuration['path'] . '/' . $configuration['core'];
    }

    return t('URL: @url', ['@url' => $url]);

  }

  /**
   * Get text describing current server ID.
   *
   * @param string|int|null $server_id
   *   Server ID.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   Translatable markup describing current server.
   */
  public static function getServerIdMessage($server_id): TranslatableMarkup {
    return t('search_api_solr.module server ID: @id', ['@id' => $server_id]);
  }

  /**
   * Get text describing availability for the given server.
   *
   * @param \Drupal\acquia_search\Plugin\search_api\backend\AcquiaSearchSolrBackend $backend
   *   The backend.
   *
   * @return array|\Drupal\Core\StringTranslation\TranslatableMarkup
   *   Solr server availability message.
   */
  public static function getServerAvailabilityMessage(AcquiaSearchSolrBackend $backend) {

    if ($backend->getSolrConnector()->pingCore()) {
      return t('Solr core is currently reachable and up.');
    }

    return [
      '#markup' => '<span class="color-error">' . t('Solr core is currently unreachable.') . '</span>',
    ];

  }

  /**
   * Get message describing authentication status for the given server.
   *
   * @param \Drupal\acquia_search\Plugin\search_api\backend\AcquiaSearchSolrBackend $backend
   *   The backend.
   *
   * @return array|\Drupal\Core\StringTranslation\TranslatableMarkup
   *   Solr server authentication status message.
   */
  public static function getServerAuthCheckMessage(AcquiaSearchSolrBackend $backend) {

    if ($backend->getSolrConnector()->pingServer()) {
      return t('Requests to Solr core are passing authentication checks.');
    }

    return [
      '#markup' => '<span class="color-error">' . t('Solr core authentication check fails.') . '</span>',
    ];

  }

}
