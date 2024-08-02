<?php

namespace Drupal\acquia_search\Plugin\SolrConnector;

use Drupal\acquia_connector\Subscription;
use Drupal\acquia_search\AcquiaSearchApiClient;
use Drupal\acquia_search\Client\Solarium\AcquiaGuzzle;
use Drupal\acquia_search\Client\Solarium\Endpoint as AcquiaEndpoint;
use Drupal\acquia_search\Helper\Messages;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Url;
use Drupal\search_api_solr\SolrConnector\SolrConnectorPluginBase;
use Drupal\search_api_solr\SolrConnectorInterface;
use Http\Factory\Guzzle\RequestFactory;
use Http\Factory\Guzzle\StreamFactory;
use Solarium\Client;
use Solarium\Core\Client\Adapter\Psr18Adapter;
use Solarium\Core\Client\Endpoint;
use Solarium\Exception\UnexpectedValueException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Acquia Search Solr Connector.
 *
 * Extends SolrConnectorPluginBase for Acquia Search Solr.
 *
 * @package Drupal\acquia_search\Plugin\SolrConnector
 *
 * @SolrConnector(
 *   id = "solr_acquia_connector",
 *   label = @Translation("Acquia Search Connector"),
 *   description = @Translation("Index items using an Acquia Apache Solr search server.")
 * )
 */
class SearchApiSolrAcquiaConnector extends SolrConnectorPluginBase implements SolrConnectorInterface, PluginFormInterface, ContainerFactoryPluginInterface {

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
   * Acquia Connector Subscription.
   *
   * @var \Drupal\acquia_connector\Subscription
   */
  protected $subscription;

  /**
   * A cache backend interface.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * Acquia Search API Client Service.
   *
   * @var \Drupal\acquia_search\AcquiaSearchApiClient
   */
  protected $acquiaSearchApiClient;

  /**
   * Acquia specific Guzzle instance for Solarium.
   *
   * @var \Drupal\acquia_search\Client\Solarium\AcquiaGuzzle
   */
  private $acquiaGuzzle;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, LoggerChannelFactoryInterface $logger_factory, AcquiaGuzzle $acquia_guzzle, MessengerInterface $messenger, CacheBackendInterface $cache, Subscription $subscription, AcquiaSearchApiClient $acquia_search_api_client) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->acquiaGuzzle = $acquia_guzzle;
    $this->acquiaSearchApiClient = $acquia_search_api_client;
    $this->messenger = $messenger;
    $this->subscription = $subscription;
    $this->cache = $cache;
    $this->setLogger($logger_factory->get('acquia_search'));
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    // Our schema (8.1.7) is newer than Solr's version, 4.1.1.
    $configuration['skip_schema_check'] = TRUE;
    // Ensure platform config is always used.
    $instance = new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('logger.factory'),
      $container->get('acquia_search.solarium.guzzle'),
      $container->get('messenger'),
      $container->get('cache.default'),
      $container->get('acquia_connector.subscription'),
      $container->get('acquia_search.api_client')
    );
    $instance->setEventDispatcher($container->get('event_dispatcher'));
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array_merge(
      parent::defaultConfiguration(),
      [
        // Our schema (8.1.7) is newer than Solr's version, 4.1.1.
        'skip_schema_check' => TRUE,
      ]
    );
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
   * {@inheritdoc}
   *
   * Acquia-specific: 'admin/info/system' path is protected by Acquia.
   * Use admin/system instead.
   */
  public function pingServer() {
    // Cache the ping during the request so it only happens once.
    static $ping;
    if (!isset($ping)) {
      $ping = $this->pingCore(['handler' => 'admin/system']);
    }
    return $ping;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $use_fields = [
      'timeout',
      SolrConnectorInterface::INDEX_TIMEOUT,
      SolrConnectorInterface::OPTIMIZE_TIMEOUT,
      SolrConnectorInterface::FINALIZE_TIMEOUT,
      'commit_within',
    ];
    foreach ($form as $k => $v) {
      if (!in_array($k, $use_fields, TRUE)) {
        $form[$k]['#access'] = FALSE;
      }
    }

    // Bail early if the subscription doesn't exist.
    if (!isset($this->subscription)) {
      $form['manual']['#markup'] = $this->t('Acquia Search requires Acquia Connector 3.1 and higher. Please <a href=":connector">update</a> and try to connect again.', [
        ':connector' => Url::fromUri('https://drupal.org/project/acquia_connector')->getUri(),
      ]);
      return $form;
    }
    elseif (!$this->subscription->isActive()) {
      $form['manual']['#markup'] = $this->t('An active Acquia Subscription is required to use search. Please <a href=":cloud">contact Acquia support</a> to renew your subscription.', [
        ':cloud' => Url::fromUri('https://docs.acquia.com/cloud-platform/subs/')->getUri(),
      ]);
      return $form;
    }

    if ($this->subscription->isActive()) {
      $form['connector']['#markup'] = $this->t('Search settings are being automatically set by your <a href=":connector">Acquia</a> subscription.',
        [':connector' => base_path() . Url::fromRoute('acquia_connector.settings')->getInternalPath()]);
      $form['acquia_search_cores'] = [
        '#title' => $this->t('Solr core(s) currently available for your application'),
        '#type' => 'fieldset',
        '#tree' => FALSE,
        'cores' => $this->getAcquiaSearchCores(),
      ];
    }

    $subdata = $this->subscription->getSubscription();
    if (isset($subdata['acquia_search']['read_only']) && $subdata['acquia_search']['read_only']) {
      $form['readonly']['#markup'] = Messages::getReadOnlyModeWarning();
    }
    return $form;

  }

  /**
   * Empty form validate handler. Without this, the endpoint will crash.
   *
   * @param array $form
   *   Form render array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state object.
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    // Exclude Acquia Specific settings.
    $dynamic_config_keys = [
      'scheme',
      'host',
      'port',
      'path',
      'core',
      'overridden_by_acquia_search',
    ];
    foreach ($dynamic_config_keys as $key) {
      unset($this->configuration[$key]);
    }
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function adjustTimeout(int $seconds, string $timeout = self::QUERY_TIMEOUT, ?Endpoint &$endpoint = NULL): int {
    $this->connect();

    if (!$endpoint) {
      $endpoint = $this->solr->getEndpoint();
    }

    $previous_timeout = $endpoint->getOption($timeout);
    $options = $endpoint->getOptions();
    $options[$timeout] = $seconds;
    $endpoint = new AcquiaEndpoint($options);

    return $previous_timeout;
  }

  /**
   * Returns the default endpoint name.
   *
   * @return string
   *   The endpoint name.
   */
  public static function getDefaultEndpoint() {
    return AcquiaEndpoint::DEFAULT_NAME;
  }

  /**
   * {@inheritdoc}
   */
  public function useTimeout(string $timeout = self::QUERY_TIMEOUT, ?Endpoint $endpoint = NULL) {}

  /**
   * {@inheritdoc}
   */
  protected function connect() {
    if (!$this->solr instanceof Client) {
      $configuration = $this->configuration;
      $this->solr = $this->createClient($configuration);
    }
    return $this->solr;
  }

  /**
   * Solarium Client Creation.
   *
   * @param array $configuration
   *   Ignored in favor of the default Acquia Configuration.
   *
   * @return object|\Solarium\Client|null
   *   Solarium Client.
   */
  protected function createClient(array &$configuration) {
    return new Client(
      new Psr18Adapter($this->acquiaGuzzle, new RequestFactory(), new StreamFactory()),
      $this->eventDispatcher,
      [
        'endpoint' => [
          'search_api_solr' => new AcquiaEndpoint($configuration),
        ],
      ],
    );
  }

  /**
   * Outputs list of Acquia Search cores.
   *
   * @return array
   *   Renderable array.
   */
  protected function getAcquiaSearchCores(): array {

    $cores = $this->acquiaSearchApiClient->getSearchIndexes();
    if ($cores === FALSE) {
      return [
        '#markup' => $this->t('Unable to connect to Acquia Search API.'),
      ];
    }

    // We use core id as a key.
    $cores = array_keys($cores);
    foreach ($cores as $key => $core) {
      if ($core === $this->configuration['core']) {
        $cores[$key] = $core . ' --> Currently Selected';
      }
    }

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
   * Override any other endpoints by getting the Acquia Default endpoint.
   *
   * @param string $key
   *   The endpoint name (ignored).
   *
   * @return \Solarium\Core\Client\Endpoint
   *   The endpoint in question.
   */
  public function getEndpoint($key = 'search_api_solr') {
    $this->connect();
    return $this->solr->getEndpoint();
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
      $this->getLogger()->error($message);
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
    $subscription_data = $this->subscription->getSubscription();
    $query->setHandler($subscription_data['acquia_search']['extract_query_handler_option'] ?? 'update/extract');
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

    // If connection settings are empty, direct users to Acquia Connector.
    if (!$this->subscription->hasCredentials()) {
      $uri = Url::fromRoute('acquia_connector.setup_oauth');
      $link = Link::fromTextAndUrl($this->t('Setup Acquia Connector'), $uri);
      $this->messenger->addWarning($this->t('Cannot connect to Search due to missing credentials. @acquia_connector.', ['@acquia_connector' => $link->toString()]));
    }
    else {
      $uri = Url::fromUri('https://www.acquia.com/products-services/acquia-search', ['absolute' => TRUE]);
      $link = Link::fromTextAndUrl($this->t('Acquia Search'), $uri);
      $this->messenger->addMessage($this->t('Search is provided by @acquia_search.', ['@acquia_search' => $link->toString()]));
    }
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
      $this->getLogger()->error($this->t('Unavailable: @message', ['@message' => $exception->getMessage()]));
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
