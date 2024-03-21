<?php

namespace Drupal\give\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\give\GiveFormInterface;
use Drupal\give\Entity\Donation;

/**
 * Defines the give form entity.
 *
 * @ConfigEntityType(
 *   id = "give_form",
 *   label = @Translation("Give form"),
 *   handlers = {
 *     "access" = "Drupal\give\Entity\GiveFormAccessControlHandler",
 *     "list_builder" = "Drupal\give\Entity\GiveFormListBuilder",
 *     "view_builder" = "\Drupal\give\Entity\GiveFormViewBuilder",
 *     "form" = {
 *       "add" = "Drupal\give\Form\GiveForm\GiveFormEditForm",
 *       "edit" = "Drupal\give\Form\GiveForm\GiveFormEditForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider"
 *     }
 *   },
 *   config_prefix = "form",
 *   admin_permission = "administer give",
 *   bundle_of = "give_donation",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   links = {
 *     "canonical" = "/give/{give_form}",
 *     "add-form" = "/admin/structure/give/add",
 *     "edit-form" = "/admin/structure/give/manage/{give_form}",
 *     "delete-form" = "/admin/structure/give/manage/{give_form}/delete",
 *     "preview-reply" = "/admin/structure/give/manage/{give_form}/preview-reply",
 *     "collection" = "/admin/structure/give",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "recipients",
 *     "autoreply",
 *     "onetime_mail",
 *     "recurring_mail",
 *     "pledge_mail",
 *     "check_or_other_text",
 *     "credit_card_extra_text",
 *     "collect_address",
 *     "bankTransferDetails",
 *     "redirect_uri",
 *     "thanks_message",
 *     "submit_text",
 *     "payment_submit_text",
 *     "payment_page_title",
 *     "donor_name_description",
 *     "frequencies"
 *   }
 * )
 * @todo rename properties using camelCase
 */
class GiveForm extends ConfigEntityBundleBase implements GiveFormInterface {

  /**
   * The form ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The human-readable label of the category.
   *
   * @var string
   */
  protected $label;

  /**
   * List of recipient email addresses.
   *
   * @var array
   */
  protected $recipients = [];

  /**
   * Mail Templates (subject and body)
   *
   * @var array
   */
  protected $onetime_mail;
  protected $recurring_mail;
  protected $pledge_mail;

  /**
   * Optional message to show potential givers who select the "Check or other"
   * donation method.
   *
   * @var string
   */
  protected $check_or_other_text;
  protected $credit_card_extra_text;

  /**
   * Message instructing users how to make a bank transfer, including details
   * such as IBAN
   *
   * @var string
   */
  public $bankTransferDetails;

  /**
   * The uri where the user will go after to donate.
   *
   * @var string
   */
  protected $redirect_uri;

  /**
   * The text displayed in the submit Button.
   *
   * @var string
   */
  protected $submit_text;
  public $thanks_message;

  /**
   * The text displayed in the submit button on the second, payment page.
   *
   * @var string
   */
  protected $payment_submit_text;

  /**
   * The text displayed in the payment page title on the second, payment page.
   *
   * @var string
   */
  protected $payment_page_title;

  /**
   * The text displayed in the donor name description on the second, payment page.
   *
   * @var string
   */
  protected $donor_name_description;

  /**
   * Frequency intervals (Stripe Plans).
   *
   * @var array
   */
  protected $frequencies = [];

  /**
   * Whether or not to collect addresses.
   *
   * @var bool
   */
  protected $collect_address;

  /**
   * {@inheritdoc}
   */
  public function getFrequencies() : array {
    // Frequences aren't very well architected at the moment, so we need to filter them by whether they have a description or not.
    return array_filter(
      $this->frequencies,
      function ($period) {return !empty($period['description']);}
    );
    return $this->frequencies;
  }

  /**
   * {@inheritdoc}
   */
  public function setFrequencies(array $frequencies) {
    $this->frequencies = $frequencies;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRecipients() : array {
    return $this->recipients;
  }

  /**
   * {@inheritdoc}
   */
  public function setRecipients(array $recipients) {
    $this->recipients = $recipients;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSubject() {
    return $this->subject;
  }

  /**
   * {@inheritdoc}
   */
  public function setSubject($subject) {
    $this->subject = $subject;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getReply() {
    return $this->reply;
  }

  /**
   * {@inheritdoc}
   */
  public function setReply($reply) {
    $this->reply = $reply;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCheckOrOtherText() {
    return $this->check_or_other_text;
  }

  /**
   * {@inheritdoc}
   */
  public function setCheckOrOtherText($text) {
    $this->check_or_other_text = $text;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreditCardExtraText() {
    return $this->credit_card_extra_text;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreditCardExtraText($text) {
    $this->credit_card_extra_text = $text;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCollectAddress() {
    return $this->collect_address;
  }

  /**
   * {@inheritdoc}
   */
  public function setCollectAddress($collect_address) {
    $this->collect_address = $collect_address;
    return $this;
  }

  /**
   * {@inheritdoc}
   * @todo make this return a Drupal\Core\Url
   */
  public function getRedirectUri() {
    return $this->redirect_uri;
  }

  /**
   * {@inheritdoc}
   */
  public function setRedirectUri($redirect_uri) {
    $this->redirect_uri = $redirect_uri;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSubmitText() {
    return $this->submit_text;
  }

  /**
   * {@inheritdoc}
   */
  public function setSubmitText($submit_text) {
    $this->submit_text = $submit_text;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPaymentSubmitText() {
    return $this->payment_submit_text;
  }

  /**
   * {@inheritdoc}
   */
  public function setPaymentSubmitText($payment_submit_text) {
    $this->payment_submit_text = $payment_submit_text;
    return $this;
  }

  /**
   * {@inheritdoc}
   *
   * @todo consider removing support for these variables.
   */
  public function getPaymentPageTitle(Donation $donation = NULL) {
    return strtr(
      $this->payment_page_title ?? '',
      [
        ':name' => $donation ? $donation->getDonorName() : ':name',
        ':sitename' => \Drupal::config('system.site')->get('name')
      ]
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setPaymentPageTitle($payment_page_title) {
    $this->payment_page_title = $payment_page_title;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDonorNameDescription() {
    return $this->donor_name_description;
  }

  /**
   * {@inheritdoc}
   */
  public function setDonorNameDescription($donor_name_description) {
    $this->donor_name_description = $donor_name_description;
    return $this;
  }
}
