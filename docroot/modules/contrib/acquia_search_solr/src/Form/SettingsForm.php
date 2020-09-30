<?php

namespace Drupal\acquia_search_solr\Form;

use Drupal\acquia_search_solr\Helper\Messages;
use Drupal\acquia_search_solr\Helper\Runtime;
use Drupal\acquia_search_solr\Helper\Storage;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class SettingsForm.
 *
 * @package Drupal\acquia_search_solr\Form
 */
class SettingsForm extends ConfigFormBase {

  /**
   * Centralized place for accessing and updating Acquia Search Solr settings.
   *
   * @var \Drupal\acquia_search_solr\Helper\Storage
   */
  protected $storage;

  /**
   * A cache backend interface.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   A cache backend interface.
   */
  public function __construct(ConfigFactoryInterface $config_factory, CacheBackendInterface $cache) {

    parent::__construct($config_factory);
    $this->cache = $cache;
    $this->storage = new Storage();

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('cache.default')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['acquia_search_solr.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'acquia_search_solr_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['#prefix'] = $this->t('Enter your product keys from the "Product Keys" section of the <a href=":cloud">Acquia Cloud UI</a> to connect your site to Acquia Search.', [
      ':cloud' => Url::fromUri('https://cloud.acquia.com')->getUri(),
    ]);

    if ($this->storage->isReadOnly()) {
      $form['readonly']['#markup'] = Messages::getReadOnlyModeWarning();
    }

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

    $form['acquia_search_api'] = [
      '#title' => $this->t('Acquia Search API'),
      '#type' => 'fieldset',
      '#tree' => FALSE,
    ];
    $form['acquia_search_api']['api_host'] = [
      '#title' => $this->t('Acquia Search API hostname'),
      '#type' => 'textfield',
      '#description' => $this->t('API endpoint domain or URL. Default value is "https://api.sr-prod02.acquia.com".'),
      '#default_value' => $this->storage->getApiHost(),
      '#required' => TRUE,
    ];
    $form['acquia_search_api']['uuid'] = [
      '#title' => $this->t('Acquia Application UUID'),
      '#type' => 'textfield',
      '#default_value' => $this->storage->getUuid(),
      '#required' => TRUE,
      '#description' => $this->t('Obtain this from the "Product Keys" section of the Acquia Cloud UI.'),
    ];

    $form['acquia_search_cores'] = [
      '#title' => $this->t('Solr core(s) currently available for your application'),
      '#type' => 'fieldset',
      '#tree' => FALSE,
      'cores' => $this->getAcquiaSearchCores(),
    ];

    $form['configure_solr_api'] = [
      '#markup' => Link::createFromRoute($this->t('Search API configuration'), 'search_api.overview')->toString(),
    ];

    return parent::buildForm($form, $form_state);

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
  public function validateForm(array &$form, FormStateInterface $form_state) {

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
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $values = $form_state->getValues();

    // Clear Acquia Search Solr indexes cache.
    if (!empty(Storage::getIdentifier())) {
      $cid = 'acquia_search_solr.indexes.' . Storage::getIdentifier();
      $this->cache->delete($cid);
    }
    $this->storage->setApiHost($values['api_host']);
    if (!empty($values['api_key'])) {
      $this->storage->setApiKey($values['api_key']);
    }

    $this->storage->setIdentifier($values['identifier']);
    $this->storage->setUuid($values['uuid']);

    parent::submitForm($form, $form_state);

  }

}
