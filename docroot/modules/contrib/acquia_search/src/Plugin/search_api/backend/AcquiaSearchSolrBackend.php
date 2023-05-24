<?php

declare(strict_types=1);

namespace Drupal\acquia_search\Plugin\search_api\backend;

use Drupal\acquia_search\Helper\Messages;
use Drupal\acquia_search\Helper\Runtime;
use Drupal\acquia_search\Plugin\SolrConnector\SearchApiSolrAcquiaConnector;
use Drupal\acquia_search\PreferredCoreService;
use Drupal\Core\Form\FormStateInterface;
use Drupal\search_api_solr\Plugin\search_api\backend\SearchApiSolrBackend;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Apache Solr backend for Acquia Search.
 *
 * @SearchApiBackend(
 *   id = "acquia_search_solr",
 *   label = @Translation("Acquia Search Solr"),
 *   description = @Translation("Index items using an Apache Solr search server provided by Acquia Search.")
 * )
 */
class AcquiaSearchSolrBackend extends SearchApiSolrBackend {

  /**
   * The preferred core service factory.
   *
   * @var \Drupal\acquia_search\PreferredCoreServiceFactory
   */
  protected $preferredCoreServiceFactory;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->preferredCoreServiceFactory = $container->get('acquia_search.preferred_core_factory');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getSolrConnector() {
    $preferred_core_service = $this->getPreferredCoreService();
    $this->configuration['connector_config'] = array_merge(
      $this->configuration['connector_config'],
      [
        'scheme' => 'https',
        'host' => $preferred_core_service->getPreferredCoreHostname() ?? 'localhost',
        'port' => '443',
        'path' => 'solr',
        'core' => $preferred_core_service->getPreferredCoreId(),
        'overridden_by_acquia_search' => SearchApiSolrAcquiaConnector::OVERRIDE_AUTO_SET,
      ]
    );
    return parent::getSolrConnector();
  }

  /**
   * Determines whether the expected core ID is available.
   *
   * @return bool
   *   True if the expected core ID available to use with Acquia.
   */
  public function isPreferredCoreAvailable(): bool {
    return $this->getPreferredCoreService()->isPreferredCoreAvailable();
  }

  /**
   * Checks if the core is read-only.
   *
   * @return bool
   *   Read Only Status.
   */
  public function isReadOnly(): bool {
    return $this->getPreferredCoreService()->isReadOnly();
  }

  /**
   * Gets a list of all possible search core IDs.
   *
   * @return array
   *   The array of core IDs.
   */
  public function getListOfPossibleCores(): array {
    return $this->getPreferredCoreService()->getListOfPossibleCores();
  }

  /**
   * Returns a formatted list of Available cores.
   *
   * @return array
   *   The array of core IDs.
   */
  public function getListOfAvailableCores(): array {
    return $this->getPreferredCoreService()->getListOfAvailableCores();
  }

  /**
   * Gets an instance of the preferred core service for this backend.
   *
   * @return \Drupal\acquia_search\PreferredCoreService
   *   The preferred core service for this backend.
   */
  private function getPreferredCoreService(): PreferredCoreService {
    return $this->preferredCoreServiceFactory->get($this->server->id());
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $defaults = parent::defaultConfiguration();
    $defaults['connector'] = 'solr_acquia_connector';
    return $defaults;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['connector']['#options'] = array_filter(
      $form['connector']['#options'],
      static fn (string $plugin_id) => $plugin_id === 'solr_acquia_connector',
      ARRAY_FILTER_USE_KEY
    );
    $form['connector']['#disabled'] = TRUE;

    if (Runtime::shouldEnforceReadOnlyMode()) {
      Messages::showReadOnlyModeWarning();
    }
    if (!$this->server->isNew() && !$this->isPreferredCoreAvailable()) {
      // Show "could not find preferred core" message.
      Messages::showNoPreferredCoreError($this);
    }
    return $form;
  }

}
