<?php

namespace Drupal\acquia_search_solr\Plugin\SolrConnector;

use Drupal\acquia_search_solr\Helper\Runtime;
use Drupal\acquia_search_solr\Helper\Storage;
use Drupal\acquia_search_solr\PreferredSearchCore;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\search_api_solr\SolrConnector\SolrConnectorPluginBase;
use Solarium\Core\Client\Adapter\Http;
use Solarium\Core\Client\Client;
use Solarium\Core\Client\Endpoint;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class AcquiaSearchSolrConnector.
 *
 * Extends SolrConnectorPluginBase for Acquia Search Solr.
 *
 * @package Drupal\acquia_search_solr\Plugin\SolrConnector
 *
 * @SolrConnector(
 *   id = "solr_acquia_search_solr",
 *   label = @Translation("Acquia"),
 *   description = @Translation("Index items using an Acquia Apache Solr search server.")
 * )
 */
class AcquiaSearchSolrConnector extends SolrConnectorPluginBase {

  /**
   * Automatically selected the proper Solr connection based on the environment.
   */
  const OVERRIDE_AUTO_SET = 1;

  /**
   * Enforce read-only mode on this connection.
   */
  const READ_ONLY = 2;

  /**
   * Default endpoint key.
   */
  const ENDPOINT_KEY = 'search_api_solr';

  /**
   * Event subscriber.
   *
   * @var \Drupal\acquia_search_solr\EventSubscriber\AcquiaSearchSolrSubscriber
   */
  protected $searchSubscriber;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {

    $plugin = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $plugin->searchSubscriber = $container->get('acquia_search_solr.search_subscriber');
    return $plugin;

  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {

    $configuration = parent::defaultConfiguration();

    $configuration['scheme'] = 'https';
    $configuration['port'] = $this->getSolrPort($configuration['scheme']);
    unset($configuration['overridden_by_acquia_search_solr']);

    // The Acquia Search Solr isn't configured.
    if (!Storage::getIdentifier()) {
      return [];
    }

    $preferred_core_service = Runtime::getPreferredSearchCoreService();

    if ($preferred_core_service->isPreferredCoreAvailable()) {
      $configuration = $this->setPreferredCore($configuration, $preferred_core_service);
      return $configuration;
    }

    return $configuration;

  }

  /**
   * Sets the preferred core in the given Solr config.
   *
   * @param array $configuration
   *   Solr connection configuration.
   * @param \Drupal\acquia_search_solr\PreferredSearchCore $preferred_core_service
   *   Service for determining the preferred search core.
   *
   * @return array
   *   Updated Solr connection configuration.
   */
  protected function setPreferredCore(array $configuration, PreferredSearchCore $preferred_core_service): array {
    $configuration['path'] = '/solr/' . $preferred_core_service->getPreferredCoreId();
    $configuration['host'] = $preferred_core_service->getPreferredCoreHostname();
    $configuration['core'] = $preferred_core_service->getPreferredCoreId();
    $configuration['key'] = self::ENDPOINT_KEY;
    $configuration['overridden_by_acquia_search_solr'] = AcquiaSearchSolrConnector::OVERRIDE_AUTO_SET;

    return $configuration;

  }

  /**
   * Sets read-only mode to the given Solr config.
   *
   * We enforce read-only mode in 2 ways:
   * - The module implements hook_search_api_index_load() and alters indexes'
   * read-only flag.
   * - In this plugin, we "emulate" read-only mode by overriding
   * $this->getUpdateQuery() and avoiding all updates just in case something
   * is still attempting to directly call a Solr update.
   *
   * @param array $configuration
   *   Solr connection configuration.
   *
   * @return array
   *   Updated Solr connection configuration.
   */
  protected function setReadOnlyMode(array $configuration): array {

    $configuration['overridden_by_acquia_search_solr'] = AcquiaSearchSolrConnector::READ_ONLY;

    return $configuration;

  }

  /**
   * {@inheritdoc}
   *
   * Acquia-specific: 'admin/info/system' path is protected by Acquia.
   * Use admin/system instead.
   */
  public function pingServer() {
    return $this->pingCore(['handler' => 'admin/system']);
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {

    $form = parent::buildConfigurationForm($form, $form_state);

    unset($form['host']);
    unset($form['port']);
    unset($form['path']);
    unset($form['core']);

    // Scheme should always be https.
    $form['scheme'] = [
      '#type' => 'value',
      '#value' => 'https',
    ];

    return $form;

  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {

    $configuration['port'] = $this->getSolrPort();

    parent::setConfiguration($configuration);

  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Override parent class: turn off connection check.
  }

  /**
   * {@inheritdoc}
   */
  protected function connect() {

    if ($this->solr) {
      return;
    }

    $this->solr = new Client(new Http(), $this->eventDispatcher);
    // Ensure that people don't specify the wrong port since the Search API Solr
    // class SolrConnectorPluginBase which we're extending does offer everything
    // up for configuration.
    $this->configuration['port'] = $this->getSolrPort($this->configuration['scheme']);
    $this->configuration['key'] = self::ENDPOINT_KEY;
    $this->configuration['path'] = '/';
    $this->configuration[self::QUERY_TIMEOUT] = $this->configuration['timeout'];

    $this->solr->createEndpoint($this->configuration, TRUE);
    $this->solr->registerPlugin('acquia_solr_search_subscriber', $this->searchSubscriber);
  }

  /**
   * {@inheritdoc}
   */
  protected function getServerUri() {

    $this->connect();

    return $this->solr->getEndpoint(self::ENDPOINT_KEY)->getCoreBaseUri();

  }

  /**
   * {@inheritdoc}
   *
   * Avoid providing an valid Update query if module determines this server
   * should be locked down (as indicated by the overridden_by_acquia_search
   * server option).
   *
   * @throws \Exception
   *   If this index in read-only mode.
   */
  public function getUpdateQuery() {

    $this->connect();
    $overridden = $this->solr->getEndpoint(self::ENDPOINT_KEY)->getOption('overridden_by_acquia_search_solr');
    if ($overridden === AcquiaSearchSolrConnector::READ_ONLY) {
      $message = 'The Search API Server serving this index is currently in read-only mode.';
      \Drupal::logger('acquia_search_solr')->error($message);
      throw new \Exception($message);
    }

    return $this->solr->createUpdate();

  }

  /**
   * {@inheritdoc}
   */
  public function getExtractQuery() {

    $this->connect();
    $query = $this->solr->createExtract();
    $query->setHandler(Storage::getExtractQueryHandlerOption());

    return $query;

  }

  /**
   * {@inheritdoc}
   */
  public function getMoreLikeThisQuery() {

    $this->connect();
    $query = $this->solr->createMoreLikeThis();
    $query->setHandler('select');
    $query->addParam('qt', 'mlt');

    return $query;

  }

  /**
   * {@inheritdoc}
   */
  public function getCoreLink() {
    return $this->getServerLink();
  }

  /**
   * {@inheritdoc}
   */
  public function getSolrVersion($force_auto_detect = FALSE) {
    try {
      return parent::getSolrVersion($force_auto_detect);
    }
    catch (\Exception $exception) {
      return $this->t('Unavailable: @message', ['@message' => $exception->getMessage()]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function viewSettings() {

    $uri = Url::fromUri('https://www.acquia.com/products-services/acquia-search', ['absolute' => TRUE]);
    $link = Link::fromTextAndUrl($this->t('Acquia Search'), $uri);
    $message = $this->t('Search is provided by @acquia_search.', ['@acquia_search' => $link->toString()]);

    \Drupal::messenger()->addMessage($message);

    return parent::viewSettings();

  }

  /**
   * {@inheritdoc}
   */
  protected function getEndpointUri(Endpoint $endpoint): string {
    return $endpoint->getCoreBaseUri();
  }

  /**
   * {@inheritdoc}
   */
  public function reloadCore() {
    return FALSE;
  }

  /**
   * Get port number of the Solr server.
   *
   * @param string $scheme
   *   The server scheme.
   *
   * @return int
   *   443 if using HTTPS, otherwise 80.
   */
  protected function getSolrPort($scheme = 'https') {
    return $scheme === 'https' ? 443 : 80;
  }

}
