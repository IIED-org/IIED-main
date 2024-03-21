<?php

namespace Drupal\give;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides an interface defining a give donation entity.
 *
 * @todo remove most of these functions in favour of the Drupal entity API get and set.
 * @todo incorporate the address module
 * @todo incorporate the currency module
 */
interface DonationInterface extends ContentEntityInterface {

  /**
   * Returns the form this give donation belongs to.
   *
   * @return \Drupal\give\GiveFormInterface
   *   The give form entity.
   */
  public function getGiveForm();

  /**
   * Get the name of the donor
   * @param bool $linked
   *
   * @return string
   *   The name of the donor.
   */
  public function getDonorName(bool $linked = FALSE) : string ;

  /**
   * Display the stored amount (in cents).
   */
  public function getFormattedAmount();

  /**
   * Returns TRUE if the donation should recur.
   *
   * @return bool
   *   TRUE if the donation should recur monthly, FALSE if not.
   */
  public function isRecurring();

  /**
   * Recurrence is the time between donations made up of the interval count and
   * the interval unit.
   */
  public function getRecurrence() : string;

  /**
   * Sets the intervals to elapse between donations.
   *
   * @param string $interval
   *   The interval unit used to describe how much time should elapse between
   *   donations. Currently the interval is hard-coded to month.
   *
   * @deprecated the recurrence unit only can be set in the give_form entity.
   */
  public function setRecurrenceIntervalUnit($interval);

  /**
   * Returns the interval used to define time to elapse between donations.
   *
   * @return int
   *   The interval unit used to describe how much time should elapse between
   *   donations. Currently the interval is hard-coded to month.
   */
  public function getRecurrenceIntervalUnit();

  /**
   * Sets the number of intervals to elapse between donations.
   *
   * @param int $count
   *   The number of intervals which should elapse between donations. Currently
   *   the interval is hard-coded to month, so a value of 1 is monthly, 3 is
   *   quarterly, and so on.
   *
   * @deprecated The recurrence only can be set in the give_form not in the
   *  donation.
   */
  public function setRecurrenceIntervalCount($count);

  /**
   * Returns the number of intervals to elapse between donations.
   *
   * @return int
   *   The number of intervals which should elapse between donations. Currently
   *   the interval is hard-coded to month, so a value of 1 is monthly, 3 is
   *   quarterly, and so on.
   */
  public function getRecurrenceIntervalCount();

  /**
   * Returns a product name based on currency, amount, interval, and interval count.
   *
   * Formerly this was a plan name, before Stripe changed their API:
   * https://stripe.com/docs/upgrades#2018-02-05
   *
   * Note that interval count is the number of intervals between donations, not
   * the number of times the payment should be made.  Recurring payments go on
   * forever.
   *
   * @return string
   */
  public function getRecurringProductName();

  /**
   * Returns a short text string which will show on donors' bank statements.
   *
   * Limited to 22 ASCII characters and printed in uppercase.  Recommended to
   * include the organization name.
   *
   * See https://stripe.com/docs/api#create_plan-product-statement_descriptor
   * and https://stripe.com/blog/dynamic-descriptors
   *
   * @return string
   */
  public function getStatementDescriptor();

  /**
   * Gets the donation creation timestamp.
   *
   * @return int
   *   Creation timestamp of the donation.
   */
  public function getCreatedTime();

  /**
   * Sets the donation creation timestamp.
   *
   * @param int $timestamp
   *   The donation creation timestamp.
   *
   * @return \Drupal\give\DonationInterface
   *   The called donation entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Sets the method the donor chose to use to make a donation.
   *
   * @param int $method
   *   The constant defining the donation method.
   *
   * @return \Drupal\give\DonationInterface
   *   The called donation entity.
   */
  public function setMethod($method);

  /**
   * Gets a string indication of the type of reply that should be sent.
   *
   * Corresponds to:
   *
   *     "onetime_mail"
   *     "pledge_mail",
   *     "recurring_mail",
   *
   * @return string
   *   A text string corresponding to the reply/subject pair.
   */
  public function getReplyType();

  /**
   * Returns the Stripe token for the donation.
   *
   * @return string
   *   The token returned by Stripe used to tell Stripe to process the donation.
   */
  public function getStripeToken();

  /**
   * Sets the Stripe token for the donation.
   *
   * @param string $token
   *   The token returned by Stripe used to tell Stripe to process the donation.
   */
  public function setStripeToken($token);

  /**
   * Returns payment card last four digits for the donation (if paid by card).
   *
   * @return int
   *   The payment card last four digits for the donation (if paid by card).
   */
  public function getCardLast4();

  /**
   * Sets the payment card last four digits for the donation (if paid by card).
   *
   * @param int $last4
   *   The payment card last four digits for the donation (if paid by card).
   */
  public function setCardLast4($last4);

  /**
   * Returns the payment card brand for the donation (if paid by card).
   *
   * For example Visa, MasterCard, etc.
   *
   * @return string
   *   The payment card brand used for the donation (if paid by card).
   */
  public function getCardBrand();

  /**
   * Sets the payment card brand used for the donation (if paid by card).
   *
   * For example Visa, MasterCard, etc.
   *
   * @param string $brand
   *   The payment card brand used for the donation (if paid by card).
   */
  public function setCardBrand($brand);

  /**
   * Returns the payment card funding type (credit, debit) (if paid by card).
   *
   * @return string
   *   The payment card funding type (credit, debit) used (if paid by card).
   */
  public function getCardFunding();

  /**
   * Sets the payment card funding type (credit, debit) used (if paid by card).
   *
   * @param string $funding
   *   The payment card funding type used for the donation (if paid by card).
   */
  public function setCardFunding($funding);

  /**
   * Returns the donation completed status.
   *
   * @return bool
   *   TRUE if the donation is completed.
   */
  public function isCompleted();

  /**
   * Sets the node promoted status.
   *
   * @param bool $completed
   *   TRUE (default) to set this donation to completed, FALSE to set it to not
   *   completed.
   *
   * @return \Drupal\give\DonationInterface
   *   The called donation entity.
   */
  public function setCompleted($completed = TRUE);

}
