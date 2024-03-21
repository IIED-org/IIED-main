<?php

namespace Drupal\give;

use Error\Card;
use Error\Base;
use Error\InvalidRequest;
use Error\ApiConnection;
use Stripe\Stripe;
use Stripe\Plan;
use Stripe\Customer;
use Stripe\Charge;

/**
 * Give stripe.
 */
class GiveStripe implements GiveStripeInterface {


  /**
   * Stripe API version.
   *
   * The API version, which Stripe expresses as a date, which Give module has
   * been tested and works with.  The Stripe PHP library will send this as the
   * default Stripe-Version header.
   */
  const GIVE_STRIPE_API_VERSION = '2019-03-14';

  /**
   * The plan, if any, associated with a donation.
   *
   * @var \Stripe\Plan
   */
  protected $plan;

  /**
   * The charge, if any, associated with a donation.
   *
   * @var \Stripe\Charge
   */
  public $charge;

  /**
   * {@inheritdoc}
   */
  public function setApiKey($stripeSecretKey) {
    Stripe::setApiKey($stripeSecretKey);
    // Since we can't do anything without the API key, we're safe piggybacking
    // on here to set the API version Give module works with.
    Stripe::setApiVersion(self::GIVE_STRIPE_API_VERSION);
  }

  /**
   * {@inheritdoc}
   */
  public function createPlan(array $plan_data) {
    try {
      // Try to create the plan.
      $this->plan = Plan::create($plan_data);
    }
    catch (ApiConnection $e) {
      throw new \Exception("Could not connect to payment processor. More information: %e", $e->getMessage());
    }
    catch (InvalidRequest $e) {
      throw new \Exception("Failed to create plan.  Invalid request: %e", $e->getMessage());
    }
    catch (Base $e) {
      throw new \Exception("Failed to create plan.  Error: %e", $e->getMessage());
    }
    // Check if the plan was created or retrieved correctly.
    if (!($this->plan instanceof Plan)) {
      throw new \Exception("Unable to create subscription plan for recurring donation. Could not complete donation.");
    }
    return $this->plan;
  }

  /**
   * {@inheritdoc}
   */
  public function createCharge(array $donation_data) {
    try {
      $this->charge = Charge::create($donation_data);
    }
    catch (Card $e) {
      throw new \Exception("Could not process card: " . $e->getMessage());
    }
    catch (ApiConnection $e) {
      throw new \Exception("Could not connect to payment processor. More information: " . $e->getMessage());
    }
    catch (Base $e) {
      throw new \Exception('Error: ' . $e->getMessage());
    }

    if (!($this->charge instanceof Charge)) {
      throw new \Exception("Could not complete donation.");
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function createCustomer(array $customer_data) {
    try {
      $customer = Customer::create($customer_data);
    }
    catch (ApiConnection $e) {
      throw new \Exception('Could not connect to payment processor. More information: ' . $e->getMessage());
    }
    catch (Card $e) {
      throw new \Exception("Could not process card: " . $e->getMessage());
    }
    catch (Base $e) {
      throw new \Exception('Error: ' . $e->getMessage());
    }

    if (!($customer instanceof Customer)) {
      throw new \Exception("Could not complete donation.");
    }
    return TRUE;
  }

}
