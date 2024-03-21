<?php

namespace Drupal\give\Form\GiveForm;

use Drupal\Core\Url;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Implements the Give configuration form controller.
 *
 * @see \Drupal\Core\Form\FormBase
 */
class GiveSettings extends ConfigFormBase {

  /**
   * Build the Give settings form.
   *
   * @param array $form
   *   Default form array structure.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Object containing current form state.
   *
   * @return array
   *   The render array defining the elements of the form.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = \Drupal::config('give.settings');
    // @todo allow multiple currencies
    $form['currency_symbol'] = [
      '#type' => 'select',
      '#title' => $this->t('Currency symbol'),
      '#description' => $this->t('These are the currencies accepted by Stripe. You need to specify which you accept at :url', [':url' => Url::fromUri('https://dashboard.stripe.com/settings/settlement_currencies')->toString()]),
      '#default_value' => $config->get('currency_symbol'),
      '#options' => [// these should probably be translatable.
        'gbp' => $this->t('British Pound'),
        'eur' => $this->t('Euro'),
        'usd' => $this->t('US Dollar'),
        'sek' => $this->t('Swedish Kroner'),
        'nok' => $this->t('Norwegian Kroner'),
        'dkk' => $this->t('Danish Kroner'),
        'chf' => $this->t('Swiss Franc'),
        'aud' => $this->t('Australian Dollar'),
        'cad' => $this->t('Canadian Dollar'),
        'jpy' => $this->t('Japanese Yen'),
        'nzd' => $this->t('New Zealand Dollar'),
        'pln' => $this->t('Polish Zloty'),
        'hkd' => $this->t('Hong Kong Dollar'),
        'sgd' => $this->t('Singapore Dollar'),
        'zar' => $this->t('South African Rand'),
        'huf' => $this->t('Hungarian Florin'),
        'ron' => $this->t('Romanian New Leu'),
        'czk' => $this->t('Czech Republic Koruna')
      ],
      '#weight' => -1
    ];
    $form['min'] = [
      '#type' => 'number',
      '#title' => $this->t('Minimum donation amount'),
      '#default_value' => $this->config('give.settings')->get('min'),
      '#description' => $this->t('The minimum amount a person can enter into the donation form.  This means that if the minimum is $2 and someone enters $1 monthly that is still below the minimum and will not be allowed.  Setting a minimum of $2–$5 can greatly reduce fraudulent stolen credit card testing charges.'),
      '#step' => .01,
      '#min' => 0,
      '#max' => 1000,
      '#field_prefix' => STRIPE_CURRENCY_SYMBOLS[$this->config('give.settings')->get('currency_symbol')],
      '#required' => TRUE,
    ];

    $reply_to = $config->get('reply_to') ?: '';
    $system_mail = \Drupal::config('system.site')->get('mail');
    $form['reply_to'] = [
      '#type' => 'email',
      '#title' => $this->t('Reply to address'),
      '#default_value' => $reply_to,
      '#description' => $this->t('If left empty, defaults to the sitewide email address, <code>:system_mail</code>, set at <a href="/admin/config/system/site-information">Administration » Configuration » System » Basic site settings</a>.', [':system_mail' => $system_mail]),
    ];
    $form['stripe_publishable_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Stripe publishable API key'),
      '#default_value' => $config->get('stripe_publishable_key'),
      '#description' => $this->t('Enter the value for the "Publishable key" token from your <a href="https://dashboard.stripe.com/account/apikeys">Stripe dashboard</a>.  This is required to take donations via credit or debit card with Stripe.'),
    ];
    $form['stripe_secret_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Stripe secret API key'),
      '#default_value' => $config->get('stripe_secret_key'),
      '#description' => $this->t('Enter the value for the "Secret key" token from your <a href="https://dashboard.stripe.com/account/apikeys">Stripe dashboard</a>.  This is required to take donations via credit or debit card with Stripe.'),
    ];

    $form['advanced'] = [
      '#type' => 'details',
      '#title' => $this->t('Advanced settings'),
    ];
    $form['advanced']['log_problems'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable problem logging'),
      '#default_value' => $config->get('log_problems'),
      '#description' => $this->t('Some issues which people may run into trying to donate, such as their browser blocking the external stripe.com scripts, can be spotted and added to the information stored with donation attempts.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Getter method for Form ID.
   *
   * @return string
   *   The unique ID of the form defined by this class.
   */
  public function getFormId() {
    return 'give_settings';
  }

  /**
   * Return the editable config names.
   *
   * @return array
   *   The config names.
   */
  protected function getEditableConfigNames() {
    return [
      'give.settings',
    ];
  }

  /**
   * Implements a form submit handler.
   *
   * @param array $form
   *   The render array of the currently built form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Object describing the current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\Core\Config\Config $config */
    \Drupal::service('config.factory')->getEditable('give.settings')
      ->set('stripe_publishable_key', $form_state->getValue('stripe_publishable_key'))
      ->set('stripe_secret_key', $form_state->getValue('stripe_secret_key'))
      ->set('reply_to', $form_state->getValue('reply_to'))
      ->set('log_problems', $form_state->getValue('log_problems'))
      ->set('currency_symbol', $form_state->getValue('currency_symbol'))
      ->set('min', $form_state->getValue('min'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
