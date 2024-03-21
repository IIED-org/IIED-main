<?php

namespace Drupal\give;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\give\Entity\Donation;

/**
 * Provides an interface defining a give form entity.
 */
interface GiveFormInterface extends ConfigEntityInterface {

  /**
   * Get the plans supported by this donation.
   *
   * @return array
   *   The Plans available for this donation form.
   */
  public function getFrequencies();

  /**
   * Set the plans supported by this donation.
   *
   * @param array $frequencies
   *   The plans supported by this donation.
   *
   * @return $this
   *   Return the entity.
   */
  public function setFrequencies(array $frequencies);

  /**
   * Returns list of recipient email addresses.
   *
   * @return array
   *   List of recipient email addresses.
   */
  public function getRecipients() : array ;

  /**
   * Returns an automatic subject for the donation receipt e-mail.
   *
   * @return string
   *   Subject for the donation receipt e-mail.
   */
  public function getSubject();

  /**
   * Returns an automatic reply to be sent with donation receipt to the donor.
   *
   * @return string
   *   Text to be sent with the donation receipt.
   */
  public function getReply();

  /**
   * Returns an optional message to show potential givers who select the "Check
   * or other" donation method.
   *
   * @return string
   *   Text to show for check or other.
   */
  public function getCheckOrOtherText();

  /**
   * Sets list of recipient email addresses.
   *
   * @param array $recipients
   *   The desired list of email addresses of this category.
   *
   * @return $this
   */
  public function setRecipients(array $recipients);

  /**
   * Sets an optional message to show potential givers who select the "Check
   * or other" donation method.
   *
   * @param string $text
   *   Text to show for check or other.
   */
  public function setCheckOrOtherText($text);

  /**
   * Sets an optional extra message to show potential givers who select the
   * "Credit card" donation method.
   *
   * @param string $text
   *   Text to show above credit card information form.
   *
   * @return $this
   */
  public function setCreditCardExtraText($text);

  /**
   * Returns an optional extra message to show potential givers who select the
   * "Credit card" donation method.
   *
   * @return string
   *   Optional extra text to show above credit card form.
   */
  public function getCreditCardExtraText();

  /**
   * Sets the requirement for donors to provide their address.
   *
   * @param bool $collect_address
   *   True to require donors to provide address information.
   *
   * @return $this
   */
  public function setCollectAddress($collect_address);

  /**
   * Gets the requirement for donors to provide their address.
   *
   * @return bool
   *   True requires donors to provide address information; false does not.
   */
  public function getCollectAddress();

  /**
   * Sets an automatic subject for the donation receipt e-mail to the donor.
   *
   * @param string $subject
   *   The desired subject for the donation receipt e-mail.
   *
   * @return $this
   */
  public function setSubject($subject);

  /**
   * Sets an automatic reply to be sent with donation receipt to the donor.
   *
   * @param string $reply
   *   The desired reply to be sent with the donation receipt.
   *
   * @return $this
   */
  public function setReply($reply);

  /**
   * Gets the uri where the user will be redirected after the donate.
   */
  public function getRedirectUri();

  /**
   * Sets the uri where the user will be redirected after to donate.
   *
   * @param string $uri
   *   The uri.
   *
   * @return $this
   */
  public function setRedirectUri($uri);

  /**
   * The text displayed in the submit button in the donation form.
   */
  public function getSubmitText();

  /**
   * Set the text displayed in the submit button in the donation form.
   *
   * @param string $text
   *   The text displayed in the submit button.
   *
   * @return $this
   */
  public function setSubmitText($text);

  /**
   * The text displayed in the submit button in the payment form.
   */
  public function getPaymentSubmitText();

  /**
   * Set the text displayed in the submit button in the payment form.
   *
   * @param string $text
   *   The text displayed in the payment submit button.
   *
   * @return $this
   */
  public function setPaymentSubmitText($text);

  /**
   * The text displayed in the Donate Encouragement in the payment form.
   *
   * @param \Drupal\give\Entity\Donation $donation
   *   A donation object.
   */
  public function getPaymentPageTitle(Donation $donation = NULL);

  /**
   * Set the text displayed in the Donate Encouragement in the payment form.
   *
   * @param string $text
   *   The text displayed in the Donate Encouragement.
   *
   * @return $this
   */
  public function setPaymentPageTitle($text);

  /**
   * The text displayed in the Donate Encouragement in the payment form under name field.
   */
  public function getDonorNameDescription();

  /**
   * Set the text displayed in the Donate Encouragement in the payment form for name description field.
   *
   * @param string $text
   *   The text displayed in the Donate Encouragement.
   *
   * @return $this
   */
  public function setDonorNameDescription($text);

}
