<?php

namespace Drupal\acquia_connector\Form;

use Drupal\acquia_connector\Subscription;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Cloud Defaults reset confirmation form.
 *
 * Called when a user attempts to override a subscription on AH and resets it.
 */
class ResetConfirmationForm extends ConfirmFormBase {

  /**
   * The Acquia Subscription.
   *
   * @var \Drupal\acquia_connector\Subscription
   */
  protected $subscription;

  /**
   * Drupal State Service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Acquia Connector Settings Form Constructor.
   *
   * @param \Drupal\acquia_connector\Subscription $subscription
   *   The Acquia subscription service.
   * @param \Drupal\Core\State\StateInterface $state
   *   The State handler.
   */
  public function __construct(Subscription $subscription, StateInterface $state) {
    $this->subscription = $subscription;
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('acquia_connector.subscription'),
      $container->get('state'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    $current_identifier = $this->subscription->getSettings()->getIdentifier();
    $default_identifier = $this->subscription->getSettings()->getMetadata('ah_network_identifier');

    return $this->t('Are you sure you want to reset the network id from @current to @default?',
      [
        '@current' => $current_identifier,
        '@default' => $default_identifier,
      ]
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('acquia_connector.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'connector_reset_confirmation_form';
  }

  /**
   * Reset's the Identifier by deleting the override from state.
   *
   * Note, this method is implemented in submitForm on the Settings Form.
   * See @Drupal\acquia_connector\Form\SettingsForm.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}
