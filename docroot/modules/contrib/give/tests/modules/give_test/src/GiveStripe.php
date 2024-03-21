<?php

namespace Drupal\give_test;

use Drupal\give\GiveStripeInterface;

/**
 * Give stripe test.
 */
class GiveStripe implements GiveStripeInterface {

  /**
   * The Stripe Api Key.
   *
   * @param string $stripeSecretKey
   *   Secret key.
   */
  public function setApiKey($stripeSecretKey) {

  }

  /**
   * Create a plan if it does not exists,.
   *
   * @param array $plan_data
   *   The stripe plan.
   *
   * @throws \Exception
   *   The error returned by the Stripe API.
   *
   * @return \Stripe\Plan
   *   The Stripe Plan.
   */
  public function createPlan(array $plan_data) {
    $plan = new \stdClass();
    $plan->_values = ['id' => 'test-id'];
    return $plan;
  }

  /**
   * Charge the donation.
   *
   * @param array $donation_data
   *   The donation data.
   *
   * @throws \Exception
   *   The error returned by the Stripe API.
   *
   * @return bool
   *   Value.
   */
  public function createCharge(array $donation_data) {
    return TRUE;
  }

  /**
   * Create a customer for this donation.
   *
   * @param array $customer_data
   *   Customer data.
   *
   * @throws \Exception
   *   The error returned by the Stripe API.
   *
   * @return bool
   *   Value.
   */
  public function createCustomer(array $customer_data) {
    return TRUE;
  }

}
