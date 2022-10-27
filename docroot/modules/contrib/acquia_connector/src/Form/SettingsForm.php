<?php

namespace Drupal\acquia_connector\Form;

use Drupal\acquia_connector\AcquiaConnectorEvents;
use Drupal\acquia_connector\ConnectorException;
use Drupal\acquia_connector\Event\AcquiaProductSettingsEvent;
use Drupal\acquia_connector\SiteProfile\SiteProfile;
use Drupal\acquia_connector\Subscription;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\PrivateKey;
use Drupal\Core\State\StateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;


/**
 * Acquia Connector Settings.
 *
 * @package Drupal\acquia_connector\Form
 */
class SettingsForm extends ConfigFormBase {

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Contracts\EventDispatcher\EventDispatcherInterface
   */
  protected $dispatcher;

  /**
   * The config factory interface.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The private key.
   *
   * @var \Drupal\Core\PrivateKey
   */
  protected $privateKey;

  /**
   * The Acquia connector client.
   *
   * @var \Drupal\acquia_connector\Subscription
   */
  protected $subscription;

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Site Profile Service.
   *
   * @var \Drupal\acquia_connector\SiteProfile\SiteProfile
   */
  protected $siteProfile;

  /**
   * Acquia Connector Settings Form Constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\PrivateKey $private_key
   *   The private key.
   * @param \Drupal\acquia_connector\Subscription $subscription
   *   The Acquia subscription service.
   * @param \Drupal\Core\State\StateInterface $state
   *   The State handler.
   * @param \Drupal\acquia_connector\SiteProfile\SiteProfile $site_profile
   *   Connector Site Profile Service.
   * @param \Symfony\Contracts\EventDispatcher\EventDispatcherInterface $dispatcher
   *   Event Dispatcher Service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ModuleHandlerInterface $module_handler, PrivateKey $private_key, Subscription $subscription, StateInterface $state, SiteProfile $site_profile, EventDispatcherInterface $dispatcher) {
    parent::__construct($config_factory);

    $this->moduleHandler = $module_handler;
    $this->privateKey = $private_key;
    $this->subscription = $subscription;
    $this->state = $state;
    $this->siteProfile = $site_profile;
    $this->dispatcher = $dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('module_handler'),
      $container->get('private_key'),
      $container->get('acquia_connector.subscription'),
      $container->get('state'),
      $container->get('acquia_connector.site_profile'),
      $container->get('event_dispatcher')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['acquia_connector.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'acquia_connector_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $settings = $this->subscription->getSettings();
    // Fetch config from setting object, as the event can alter it.
    $config = $settings->getConfig();
    $identifier = $settings->getIdentifier();
    $key = $settings->getSecretKey();

    if (empty($identifier) || empty($key)) {
      return new RedirectResponse((string) \Drupal::service('url_generator')->generateFromRoute('acquia_connector.setup'));
    }

    // Start with an empty subscription.
    $subscription = [];
    // Check our connection to the Acquia and validate credentials.
    try {
      // Force a refresh of subscription data.
      $subscription = $this->subscription->getSubscription(TRUE);
    }
    catch (ConnectorException $e) {
      $error_message = $this->subscription->connectionErrorMessage($e->getCustomMessage('code', FALSE));
      $ssl_available = in_array('ssl', stream_get_transports(), TRUE) && !defined('ACQUIA_CONNECTOR_TEST_ACQUIA_DEVELOPMENT_NOSSL') && $config->get('spi.ssl_verify');
      if (empty($error_message) && $ssl_available) {
        $error_message = $this->t('There was an error in validating your subscription credentials. You may want to try disabling SSL peer verification by setting the variable acquia_connector.settings:spi.ssl_verify to false.');
      }
      $this->messenger()->addError($error_message);
    }

    $form['connected'] = [
      '#markup' => $this->t('<h3>Connected to Acquia</h3>'),
    ];

    if (!empty($subscription)) {
      $form['subscription'] = [
        '#markup' => $this->t('Subscription: @sub <a href=":url">change</a>', [
          '@sub' => $subscription['subscription_name'],
          ':url' => (string) \Drupal::service('url_generator')->generateFromRoute('acquia_connector.setup'),
        ]),
      ];
    }

    $form['identification'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Site Identification'),
      '#collapsible' => FALSE,
    ];

    $form['identification']['description']['#markup'] = $this->t('This is the unique string used to identify this site on Acquia Cloud.');
    $form['identification']['description']['#weight'] = -2;

    $form['identification']['site'] = [
      '#prefix' => '<div class="acquia-identification">',
      '#suffix' => '</div>',
      '#weight' => -1,
    ];

    $form['identification']['site']['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#maxlength' => 255,
      '#required' => TRUE,
      '#disabled' => TRUE,
      '#default_value' => $this->state->get('spi.site_name') ?? $this->siteProfile->getSiteName($subscription['subscription_name']),
    ];

    if (!empty($form['identification']['site']['name']['#default_value']) && $this->siteProfile->checkAcquiaHosted()) {
      $form['identification']['site']['name']['#disabled'] = TRUE;
    }

    if ($this->siteProfile->checkAcquiaHosted()) {
      $form['identification']['#description'] = $this->t('Acquia hosted sites are automatically provided with a machine name.');
    }

    $form['identification']['site']['machine_name'] = [
      '#type' => 'machine_name',
      '#title' => $this->t('Machine name'),
      '#maxlength' => 255,
      '#required' => TRUE,
      '#machine_name' => [
        'exists' => [$this, 'exists'],
        'source' => ['identification', 'site', 'name'],
      ],
      '#default_value' => $this->siteProfile->getMachineName($subscription),
    ];

    $form['identification']['site']['machine_name']['#disabled'] = TRUE;

    // Get product settings
    // Refresh the subscription from Acquia
    // Allow other modules to add metadata to the subscription.
    $event = new AcquiaProductSettingsEvent($form, $form_state, $this->subscription);
    $this->dispatcher->dispatch($event, AcquiaConnectorEvents::ACQUIA_PRODUCT_SETTINGS);
    $form = $event->getForm();
    if (isset($form['product_settings'])) {
      $form['product_settings']['#type'] = 'fieldset';
      $form['product_settings']['#title'] = $this->t("Product Specific Settings");
      $form['product_settings']['#collapsible'] = FALSE;
      $form['product_settings']['#tree'] = TRUE;
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * Determines if the machine name already exists.
   *
   * @return bool
   *   FALSE.
   */
  public function exists() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $event = new AcquiaProductSettingsEvent($form, $form_state, $this->subscription);
    $this->dispatcher->dispatch($event, AcquiaConnectorEvents::ALTER_PRODUCT_SETTINGS_SUBMIT);

    $values = $form_state->getValues();
    $this->state->set('spi.site_name', $values['name']);

    // Save individual product settings within connector config.
    if (!empty($values['product_settings'])) {
      // Loop through each product.
      foreach ($values['product_settings'] as $product_name => $settings) {
        // Only set the setting if it changed.
        foreach ($settings['settings'] as $key => $value) {
          // Don't change the settings if the existing value matches.
          if ($form['product_settings'][$product_name]['settings'][$key]['#default_value'] === $value) {
            continue;
          }
          // Delete the setting if the value is null.
          if (empty($value)) {
            $this->config('acquia_connector.settings')->clear('third_party_settings.' . $product_name . '.' . $key);
            continue;
          }
          // Save the setting if its not empty.
          $this->config('acquia_connector.settings')->set('third_party_settings.' . $product_name . '.' . $key, $value);
        }
      }
    }
    $this->config('acquia_connector.settings')->save();

    parent::submitForm($form, $form_state);
  }

}
