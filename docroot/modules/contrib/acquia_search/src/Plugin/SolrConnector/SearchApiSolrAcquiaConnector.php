<?php

namespace Drupal\acquia_search\Plugin\SolrConnector;

use Drupal\acquia_search\Client\Adapter\TimeoutAwarePsr18Adapter;
use Drupal\acquia_search\Helper\Messages;
use Drupal\acquia_search\Helper\Runtime;
use Drupal\acquia_search\Helper\Storage;
use Drupal\acquia_search\PreferredSearchCore;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\search_api_solr\SolrConnector\SolrConnectorPluginBase;
use Http\Adapter\Guzzle6\Client as Guzzle6Client;
use Solarium\Core\Client\Client;
use Solarium\Core\Client\Endpoint;
use Solarium\Exception\UnexpectedValueException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class SearchApiSolrAcquiaConnector.
 *
 * Extends SolrConnectorPluginBase for Acquia Search Solr.
 *
 * @package Drupal\acquia_search\Plugin\SolrConnector
 *
 * @SolrConnector(
 *   id = "solr_acquia_connector",
 *   label = @Translation("Acquia"),
 *   description = @Translation("Index items using an Acquia Apache Solr search server.")
 * )
 */
class SearchApiSolrAcquiaConnector extends SolrConnectorPluginBase {

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
   * Centralized place for accessing and updating Acquia Search Solr settings.
   *
   * @var \Drupal\acquia_search\Helper\Storage
   */
  protected $storage;

  /**
   * Event subscriber.
   *
   * @var \Drupal\acquia_search\EventSubscriber\SearchSubscriber
   */
  protected $searchSubscriber;

  /**
   * A cache backend interface.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition) {
    $this->storage = new Storage();
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    // Our schema (8.1.7) is newer than Solr's version, 4.1.1.
    $configuration['skip_schema_check'] = TRUE;

    $plugin = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $plugin->searchSubscriber = $container->get('acquia_search.search_subscriber');
    $plugin->logger = $container->get('logger.factory')->get('acquia_search');
    $plugin->cache = $container->get('cache.default');

    return $plugin;

  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {

    $configuration = parent::defaultConfiguration();

    unset($configuration['overridden_by_acquia_search']);

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
   * @param \Drupal\acquia_search\PreferredSearchCore $preferred_core_service
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
    $configuration['overridden_by_acquia_search'] = SearchApiSolrAcquiaConnector::OVERRIDE_AUTO_SET;

    return $configuration;

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
  public function getCoreInfo($reset = FALSE) {
    if (isset($this->configuration['core'])) {
      return parent::getCoreInfo($reset);
    }
    return NULL;
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

    $configuration['overridden_by_acquia_search'] = SearchApiSolrAcquiaConnector::READ_ONLY;

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

    if ($this->storage->isReadOnly()) {
      $form['readonly']['#markup'] = Messages::getReadOnlyModeWarning();
    }

    // If acquia connector is enabled, use the settings from there instead.
    $connector = \Drupal::moduleHandler()->moduleExists('acquia_connector');
    if ($connector) {
      $form['connector']['#markup'] = $this->t('Search settings are being automatically set by your <a href=":connector">Acquia Connector</a> subscription.', [
        ':connector' => base_path() . Url::fromRoute('acquia_connector.settings')->getInternalPath(),
      ]);

      $subscription = \Drupal::state()->get('acquia_subscription_data');
      $form['identifier'] = [
        '#type' => 'value',
        '#value' => \Drupal::state()->get('acquia_connector.identifier') ?? '',
      ];
      $form['api_key'] = [
        '#type' => 'value',
        '#value' => \Drupal::state()->get('acquia_connector.key') ?? '',
      ];
      $form['uuid'] = [
        '#type' => 'value',
        '#value' => $subscription['uuid'] ?? '',
      ];
    }
    else {
      $form['manual']['#markup'] = $this->t('Enter your product keys from the "Product Keys" section of the <a href=":cloud">Acquia Cloud UI</a> to connect your site to Acquia Search. You can also automatically set these details by enabling the Acquia Connector.', [
        ':cloud' => Url::fromUri('https://cloud.acquia.com')->getUri(),
      ]);

      $form['identifier'] = [
        '#title' => $this->t('Acquia Subscription identifier'),
        '#type' => 'textfield',
        '#default_value' => $this->storage->getIdentifier(),
        '#required' => TRUE,
        '#description' => $this->t('Obtain this from the "Product Keys" section of the Acquia Cloud UI. Example: ABCD-12345'),
      ];
      $form['api_key'] = [
        '#title' => $this->t('Acquia Connector key'),
        '#type' => 'password',
        '#description' => !empty($this->storage->getApiKey()) ? $this->t('Value already provided.') : $this->t('Obtain this from the "Product Keys" section of the Acquia Cloud UI.'),
        '#required' => empty($this->storage->getApiKey()),
      ];
      $form['uuid'] = [
        '#title' => $this->t('Acquia Application UUID'),
        '#type' => 'textfield',
        '#default_value' => $this->storage->getUuid(),
        '#required' => TRUE,
        '#description' => $this->t('Obtain this from the "Product Keys" section of the Acquia Cloud UI.'),
      ];
    }

    $form['api_host'] = [
      '#title' => $this->t('Acquia Search API hostname'),
      '#type' => 'textfield',
      '#description' => $this->t('API endpoint domain or URL. Default value is "https://api.sr-prod02.acquia.com".'),
      '#default_value' => $this->storage->getApiHost(),
      '#required' => TRUE,
    ];

    $form['acquia_search_cores'] = [
      '#title' => $this->t('Solr core(s) currently available for your application'),
      '#type' => 'fieldset',
      '#tree' => FALSE,
      'cores' => $this->getAcquiaSearchCores(),
    ];

    return $form;

  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Remove whitespaces.
    foreach (['identifier', 'uuid', 'api_key', 'api_host'] as $key) {
      $form_state->setValue($key, trim($form_state->getValue($key)));
    }
    // No trailing slash allowed for a API host.
    $form_state->setValue('api_host', rtrim($form_state->getValue('api_host'), '/'));

    $values = $form_state->getValues();

    if (!preg_match('@^[A-Z]{4,5}-[0-9]{5,6}$@', $values['identifier'])) {
      $form_state->setErrorByName('identifier', $this->t('Enter a valid identifier.'));
    }

    if (!preg_match('@^(https?://|)[a-z0-9\.-]*$@', $values['api_host'])) {
      $form_state->setErrorByName('api_host', $this->t('Enter a valid domain.'));
    }

    if (!preg_match('@^[0-9a-f-]*$@', $values['uuid'])) {
      $form_state->setErrorByName('uuid', $this->t('Enter a valid UUID.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    // Clear Acquia Search Solr indexes cache.
    if (!empty(Storage::getIdentifier())) {
      $cid = 'acquia_search.indexes.' . Storage::getIdentifier();
      $this->cache->delete($cid);
    }
    $this->storage->setApiHost($values['api_host']);
    if (!empty($values['api_key'])) {
      $this->storage->setApiKey($values['api_key']);
    }

    $this->storage->setIdentifier($values['identifier']);
    $this->storage->setUuid($values['uuid']);
  }

  /**
   * {@inheritdoc}
   */
  protected function connect() {

    if ($this->solr) {
      return;
    }

    // Create a PSR-18 adapter instance, since Solarium's HTTP adapter is
    // incompatible with remote_stream_wrapper.
    // See https://www.drupal.org/project/acquia_search/issues/3209704
    // And https://www.drupal.org/project/acquia_search_solr/issues/3171407
    $httpClient = new Guzzle6Client();
    $adapter = new TimeoutAwarePsr18Adapter($httpClient);

    $this->solr = new Client($adapter, $this->eventDispatcher);

    // Scheme should always be https and port 443.
    $this->configuration['scheme'] = 'https';
    $this->configuration['port'] = 443;
    $this->configuration['key'] = self::ENDPOINT_KEY;
    $this->configuration['path'] = '/';
    $this->configuration[self::QUERY_TIMEOUT] = $this->configuration['timeout'];

    $this->solr->createEndpoint($this->configuration, TRUE);
    $this->solr->registerPlugin('acquia_solr_search_subscriber', $this->searchSubscriber);
  }

  /**
   * Outputs list of Acquia Search cores.
   *
   * @return array
   *   Renderable array.
   */
  protected function getAcquiaSearchCores(): array {

    if (!$this->storage->getApiKey() || !$this->storage->getIdentifier() || !$this->storage->getUuid() || !$this->storage->getApiHost()) {
      return [
        '#markup' => $this->t('Please provide API credentials for Acquia Search.'),
      ];
    }

    if (!$cores = Runtime::getAcquiaSearchApiClient()->getSearchIndexes($this->storage->getIdentifier())) {
      return [
        '#markup' => $this->t('Unable to connect to Acquia Search API.'),
      ];
    }

    // We use core id as a key.
    $cores = array_keys($cores);

    if (empty($cores)) {
      $cores[] = $this->t('Your subscription contains no cores.');
    }

    return [
      '#theme' => 'item_list',
      '#items' => $cores,
    ];

  }

  /**
   * {@inheritdoc}
   */
  protected function getServerUri() {

    $this->connect();

    return $this->getEndpointUri($this->solr->getEndpoint(self::ENDPOINT_KEY));

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
    $overridden = $this->solr->getEndpoint(self::ENDPOINT_KEY)->getOption('overridden_by_acquia_search');
    if ($overridden === SearchApiSolrAcquiaConnector::READ_ONLY) {
      $message = 'The Search API Server serving this index is currently in read-only mode.';
      \Drupal::logger('acquia_search')->error($message);
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
    $query->setHandler('mlt');
    $query->addParam('qt', 'mlt');

    return $query;

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
    try {
      return $endpoint->getCoreBaseUri();
    }
    catch (UnexpectedValueException $exception) {
      $this->logger->error($this->t('Unavailable: @message', ['@message' => $exception->getMessage()]));
      return $endpoint->getServerUri();
    }

  }

  /**
   * {@inheritdoc}
   */
  public function reloadCore() {
    return FALSE;
  }

}
