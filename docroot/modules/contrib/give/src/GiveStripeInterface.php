<?php

namespace Drupal\give;

/**
 * Give stripe interface.
 */
interface GiveStripeInterface {

  /**
   * The Stripe Api Key.
   *
   * @param string $stripeSecretKey
   *   Secret key.
   */
  public function setApiKey($stripeSecretKey);

  /**
   * Create a plan if it does not exists,.
   *
   * @param array $plan_data
   *   The stripe plan. See https://stripe.com/docs/api/plan/object
   *
   * @throws \Exception
   *   The error returned by the Stripe API.
   *
   * @return \Stripe\Plan
   *   The Stripe Plan.
   */
  public function createPlan(array $plan_data);

  /**
   * Charge the donation.
   *
   * @param array $donation_data
   *   The donation data. See https://stripe.com/docs/api/charges/object
   *
   * @throws \Exception
   *   The error returned by the Stripe API.
   *
   * @return bool
   *   Value.
   */
  public function createCharge(array $donation_data);

  /**
   * Create a customer for this donation.
   *
   * @param array $customer_data
   *   Customer data. See https://stripe.com/docs/api/customers/object
   *
   * @throws \Exception
   *   The error returned by the Stripe API.
   *
   * @return bool
   *   Value.
   */
  public function createCustomer(array $customer_data);

}
