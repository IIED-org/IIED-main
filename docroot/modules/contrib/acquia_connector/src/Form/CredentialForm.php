<?php

namespace Drupal\acquia_connector\Form;

use Drupal\acquia_connector\Client\ClientFactory;
use Drupal\acquia_connector\ConnectorException;
use Drupal\acquia_connector\Settings;
use Drupal\acquia_connector\Subscription;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for Acquia Credentials.
 */
class CredentialForm extends ConfigFormBase {

  /**
   * The Acquia client.
   *
   * @var \Drupal\acquia_connector\Client\ClientFactory
   */
  protected $clientFactory;

  /**
   * Acquia Connector static config.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * Drupal State Service.
   *
   * @var \Drupal\Core\State\State
   */
  protected $state;

  /**
   * Constructs a \Drupal\system\ConfigFormBase object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\acquia_connector\Client\ClientFactory $client_factory
   *   The Acquia client.
   * @param \Drupal\Core\State\StateInterface $state
   *   Drupal State Service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ClientFactory $client_factory, StateInterface $state) {
    parent::__construct($config_factory);

    $this->config = $config_factory->getEditable('acquia_connector.settings');
    $this->clientFactory = $client_factory;
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('acquia_connector.client.factory'),
      $container->get('state')
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
    return 'acquia_connector_settings_credentials';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['#prefix'] = $this->t('Enter your product keys from your <a href=":net">application overview</a> or <a href=":url">log in</a> to connect your site to Acquia Insight.', [
      ':net' => Url::fromUri('https://cloud.acquia.com')->getUri(),
      ':url' => Url::fromRoute('acquia_connector.setup')->toString(),
    ]);

    $form['acquia_identifier'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Identifier'),
      '#default_value' => '',
      '#required' => TRUE,
    ];
    $form['acquia_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Network key'),
      '#default_value' => '',
      '#required' => TRUE,
    ];
    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Connect'),
    ];
    $form['actions']['signup'] = [
      '#markup' => $this->t('Need a subscription? <a href=":url">Get one</a>.', [
        ':url' => Url::fromUri('https://www.acquia.com/acquia-cloud-free')->getUri(),
      ]),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    try {
      // Manually create a temporary settings object.
      $settings = new Settings($this->config, $form_state->getValue('acquia_identifier'), trim($form_state->getValue('acquia_key')));
      $client = $this->clientFactory->getClient($settings);

      $response = $client->nspiCall(
        '/agent-api/subscription',
        ['identifier' => trim($form_state->getValue('acquia_identifier'))],
        trim($form_state->getValue('acquia_key')));
    }
    catch (ConnectorException $e) {
      // Set form error to prevent switching to the next page.
      if ($e->isCustomized()) {
        // Allow to connect with expired subscription.
        if ($e->getCustomMessage('code') == Subscription::EXPIRED) {
          $form_state->setValue('subscription', 'Expired subscription.');
          return;
        }
        $this->messenger()->addError($this->t('Error: @message (@errno)', ['@message' => $e->getCustomMessage(), '@errno' => $e->getCustomMessage('code')]));
        $form_state->setErrorByName('');
      }
      else {
        $form_state->setErrorByName('', $this->t('Server error, please submit again.'));
      }
      return;
    }

    $response = $response['result'];

    if (empty($response['body']['subscription_name'])) {
      $form_state->setErrorByName('acquia_identifier', $this->t('No subscriptions were found.'));
    }
    else {
      $form_state->setValue('subscription_data', $response['body']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->saveState($form_state->getValue('acquia_identifier'), $form_state->getValue('acquia_key'), $form_state->getValue('subscription_data'));
    // Check subscription and send a heartbeat to Acquia via XML-RPC.
    // Our status gets updated locally via the return data.
    // Don't use dependency injection here because we just created the sub.
    $subscription = \Drupal::service('acquia_connector.subscription');

    // Redirect to the path without the suffix.
    $form_state->setRedirect('acquia_connector.settings');

    drupal_flush_all_caches();

    if ($subscription->isActive()) {
      $this->messenger()->addStatus($this->t('<h3>Connection successful!</h3>You are now connected to Acquia Cloud. Please enter a name for your site to begin sending profile data.'));
    }
  }

  /**
   * Save subscription credentials to state.
   *
   * @param string $identifier
   *   Acquia Network ID.
   * @param string $key
   *   Acquia Subscription Secret Key.
   * @param array $subscription_data
   *   Raw Subscription Data Array.
   */
  protected function saveState(string $identifier, string $key, array $subscription_data) {
    // Setup form uses the state system, update state.
    $this->state->set('acquia_connector.identifier', $identifier);
    $this->state->set('acquia_connector.key', $key);
    $this->state->set('acquia_subscription_data', $subscription_data);
  }

}
