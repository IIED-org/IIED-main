<?php

namespace Drupal\give\Entity;

use Drupal\give\Plugin\Field\FieldType\PaymentMethod;
use Drupal\give\DonationInterface;
use Drupal\give\GiveStripeInterface;
use Drupal\user\EntityOwnerTrait;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityPublishedTrait;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Defines the give donation entity.
 *
 * @ContentEntityType(
 *   id = "give_donation",
 *   label = @Translation("Give donation"),
 *   handlers = {
 *     "access" = "Drupal\give\Entity\DonationAccessControlHandler",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\give\Entity\DonationListBuilder",
 *     "views_data" = "Drupal\give\Entity\DonationViewsData",
 *     "form" = {
 *       "default" = "Drupal\give\Form\Donation\DonationForm",
 *       "add" = "Drupal\give\Form\Donation\PaymentForm",
 *       "edit" = "Drupal\give\Form\Donation\DonationEditForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *       "complete" = "Drupal\give\Form\Donation\DonationCompleteForm"
 *     },
 *     "views_data" = "Drupal\give\Entity\DonationViewsData",
 *     "route_provider" = {
 *       "html" = "Drupal\give\Entity\DonationRouteProvider"
 *     }
 *   },
 *   base_table = "give_donation",
 *   admin_permission = "manage donations",
 *   entity_keys = {
 *     "id" = "id",
 *     "bundle" = "give_form",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode",
 *     "owner" = "uid",
 *     "published" = "complete",
 *   },
 *   links = {
 *     "canonical" = "/donation/{give_donation}",
 *     "add-form" = "/donation/add",
 *     "delete-form" = "/donation/{give_donation}/delete",
 *     "complete-form" = "/donation/{give_donation}/complete",
 *     "collection" = "/admin/reports/donation"
 *   },
 *   bundle_entity_type = "give_form",
 *   field_ui_base_route = "entity.give_form.edit_form",
 * )
 *
 * @todo improve the data structure for recurring donations.
 * @todo Change the method field from an integer to the method's name.
 * @todo Change the completed field to status, which is inherited from ContentEntityInterface
 * @todo remove most of the get/set functions here
 * @todo use the address module instead of putting fields here.
 */
class Donation extends ContentEntityBase implements DonationInterface, EntityOwnerInterface, EntityChangedInterface, EntityPublishedInterface {

  use EntityOwnerTrait;
  use EntityChangedTrait;
  use EntityPublishedTrait;
  use StringTranslationTrait;
  
  const NOT_RECURRING = 0;

  /**
   * {@inheritdoc}
   */
  public function getGiveForm() {
    return $this->get('give_form')->entity;
  }

  /**
   * {@inheritdoc}
   *
   * @todo make this configurable with tokens.
   */
  public function label() {
    return $this->t(
      '@name gave @amount',
      [
        '@name' => $this->getDonorName(),
        '@amount' =>  $this->getFormattedAmount()
      ]
    );
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    if ($this->uid->value and !$this->name->value) {
      $this->name->value = $this->getOwner()->getDisplayName();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getDonorName(bool $linked = FALSE) : string {
    if ($this->uid->target_id) {
      $users = $this->uid->referencedEntities();
      $user = reset($users);
      $name = $linked ? $user->toLink()->toString() : $user->label();
    }
    else {
      $name = $this->name->value;
    }
    return $name;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormattedAmount() {
    return format_stripe_currency($this->amount->value ?? 0);
  }

  /**
   * {@inheritdoc}
   */
  public function isRecurring() {
    // TODO: The approach of comparing a saved 'index' value with the form
    // configuration means that if the form options ever change, all of these
    // functions break for historical records.  We should probably instead
    // save the "computed" values for recurrence interval and recurrence count.
    return $this->getRecurrenceIntervalCount() != self::NOT_RECURRING;
  }

  /**
   * {@inheritdoc}
   *
   * @note Seems this function is not used
   */
  public function setRecurrenceIntervalUnit($interval) {
    if ($interval != 'month') {
      // Not translatable because only developers should see this error.
      throw new \Exception(
        "Unsupported interval $interval. Interval periods other than month-based are not currently supported."
      );
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRecurrenceIntervalUnit() {
    $give_form = $this->getGiveForm();
    if ($frequencies = $give_form->getFrequencies()) {
      return $frequencies[$this->get('recurring')->value]['interval'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getRecurrenceIntervalCount() {
    $give_form = $this->getGiveForm();
    $frequencies = $give_form->getFrequencies();
    $recurring_val = $this->get('recurring')->value;
    if (!isset($frequencies[$recurring_val])) {
      return self::NOT_RECURRING;
    }
    return $frequencies[$recurring_val]['interval_count'];
  }

  /**
   * {@inheritdoc}
   *
   * @todo Maybe a higher level function handles the NOT_RECURRING option,
   * something like what happens with the field cardinality constants.
   *
   * @note Seems this function is not used
   */
  public function setRecurrenceIntervalCount($count) {
    $this->set('recurring', $count);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRecurrence() : string {
    $give_form = $this->getGiveForm();
    $frequencies = $give_form->getFrequencies();
    $value = $this->recurring->value;
    if (isset($frequencies[$value])) {
      return $frequencies[$value]['description'];
    }
    return 'No';
  }

  /**
   * {@inheritdoc}
   *
   * Should really validate input here against PaymentMethod constants.
   */
  public function setMethod($method) {
    $this->set('method', $method);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getReplyType() {
    if ($this->method->value == PaymentMethod::GIVE_VIA_STRIPE) {
      if ($this->isRecurring()) {
        return 'recurring';
      }
      else {
        return 'onetime';
      }
    }
    elseif ($this->method->value == PaymentMethod::GIVE_VIA_CHECK) {
      return 'pledge';
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getRecurringProductName() {
    $give_form = $this->getGiveForm();
    if ($this->isRecurring()) {
      $frequencies = $give_form->getFrequencies();
      if ($f = @$frequencies[$this->recurring->value]['description']) {
        return $this->getFormattedAmount() . ' ' . $f;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getStatementDescriptor() {
    // TODO allow setting of organization name in Give config and only using
    // sitename as a fallback.
    $config_system_site = \Drupal::config('system.site');
    $short_sitename = trim(substr($config_system_site->get('name'), 0, 12));
    $descriptor = $this->recurring->value ?
      $this->getRecurringProductName() :
      $this->label;
    return strtoupper(substr($short_sitename . ' ' . $descriptor, 0, 22));
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getStripeToken() {
    return $this->get('stripe_token')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setStripeToken($token) {
    $this->set('stripe_token', $token);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCardLast4() {
    return $this->get('card_last4')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCardLast4($last4) {
    $this->set('card_last4', $last4);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCardBrand() {
    return $this->get('card_brand')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCardBrand($brand) {
    $this->set('card_brand', $brand);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCardFunding() {
    return $this->get('card_funding')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCardFunding($funding) {
    $this->set('card_funding', $funding);
    return $this;
  }

  function showAddress() {
    $address = [
      $this->get('address_line1')->value,
      $this->get('address_line2')->value,
      $this->get('address_city')->value .' '. $this->get('address_state')->value,
      $this->get('address_zip')->value,
      $this->get('address_country')->value
    ];
    return implode('<br />', array_filter($address));
  }

  /**
   * {@inheritdoc}
   */
  public function isCompleted() {
    return (bool) $this->get('complete')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCompleted($completed = TRUE) {
    $this->set('complete', $completed ? DONATION_COMPLETED : DONATION_NOT_COMPLETED);
    return $this;
  }

  /**
   * Set card info.
   *
   * Helper function to set card last four, brand, and funding source from a
   * GiveStripe entity.
   *
   * @param \Drupal\give\GiveStripeInterface $give_stripe
   *   The donation.
   */
  public function setCardInfo(GiveStripeInterface $give_stripe) {
    $charge = $give_stripe->charge;
    $funding = $charge->source->funding;
    $brand = $charge->source->brand;
    $last4 = $charge->source->last4;
    $this->setCardFunding($funding);
    $this->setCardBrand($brand);
    $this->setCardLast4($last4);
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);
    $fields += static::publishedBaseFieldDefinitions($entity_type);
    $fields['complete']->setLabel(t('Received'));
    $fields['complete']->setDescription(t('The donation has been processed and received.'));
    $fields['complete']->setDefaultValue(FALSE);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t("The donor's name"))
      ->setDescription(t("The donor's name."))
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['amount'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Amount'))
      ->setDescription('The amount of the donation, to two decimal places.')
      ->setDisplayConfigurable('view', TRUE)
      ->setSetting('scale', 2)
      ->setRequired(TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the donation was last edited.'))
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the donation was created.'))
      ->setDisplayConfigurable('view', TRUE)
      ->setTranslatable(TRUE)
      ->setReadOnly(TRUE);


    // Currently this is just a string but it should probably be an enumerated field extended by a hook_alter
    $fields['method'] = BaseFieldDefinition::create('give_method')
      ->setLabel(t('Method'))
      ->setDescription(t('The donation method (payment card, check pledge).'))
      ->setDisplayConfigurable('view', TRUE);

    $fields['mail'] = BaseFieldDefinition::create('email')
      ->setLabel(t("The donor's email"))
      ->setDescription(t('The email of the person that is sending the give donation.'))
      ->setDisplayConfigurable('view', TRUE);

    $fields['recurring'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Recurring'))
      ->setDefaultValue(self::NOT_RECURRING)
      ->setDescription(t('The interval counts (in number of months) at which the donation should recur, or negative one if not recurring.'))
      ->setDisplayConfigurable('view', TRUE);

    $fields['telephone'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Phone'))
      ->setDescription(t('The telephone number of the donor.'))
      ->setDisplayConfigurable('view', TRUE)
      ->setSetting('max_length', 20);

    $fields['check_or_other_information'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Further information'))
      ->setDescription(t('Any questions or explain anything needed to arrange for giving donation.'))
      ->setDisplayConfigurable('view', TRUE)
      ->setSetting('max_length', 2000);

    $fields['address_line1'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Address line 1'))
      ->setDescription(t('The street address or PO Box of the donor; used in billing address.'))
      ->setDisplayConfigurable('view', TRUE);

    $fields['address_line2'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Address line 2'))
      ->setDescription(t('Optional apartment/suite/unit of the donor; used in billing address.'))
      ->setDisplayConfigurable('view', TRUE)
      ->setSetting('max_length', 100);

    $fields['address_city'] = BaseFieldDefinition::create('string')
      ->setLabel(t('City or district'))
      ->setDescription(t('The town of the donor; used in billing address.'))
      ->setDisplayConfigurable('view', TRUE)
      ->setSetting('max_length', 100);

    $fields['address_zip'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Postal code'))
      ->setDescription(t('ZIP or postal code of the donor; used in billing address.'))
      ->setDisplayConfigurable('view', TRUE)
      ->setSetting('max_length', 100);

    $fields['address_state'] = BaseFieldDefinition::create('string')
      ->setLabel(t('State or province'))
      ->setDescription(t('The state/province/region of the donor; used in billing address.'))
      ->setDisplayConfigurable('view', TRUE)
      ->setSetting('max_length', 100);

    $fields['address_country'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Country'))
      ->setDescription(t('The country the donor; used in billing address.'))
      ->setDisplayConfigurable('view', TRUE)
      ->setSetting('max_length', 100);

    // Does this need to be stored? Maybe for recurring payments?
    $fields['stripe_token'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Stripe token'))
      ->setDescription(t('The token returned by Stripe used to tell Stripe to process the donation.'))
      ->setSetting('max_length', 56);

    $fields['card_brand'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Card brand'))
      ->setDescription(t('The card brand (Visa, MasterCard, etc).'))
      ->setDisplayConfigurable('view', TRUE)
      ->setSetting('max_length', 30);

    $fields['card_funding'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Card funding'))
      ->setDescription(t('The card funding type (credit, debit).'))
      ->setDisplayConfigurable('view', TRUE)
      ->setSetting('max_length', 30);

    $fields['card_last4'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Last four'))
      ->setDescription(t('The last four digits of the credit/debit card, if applicable.'))
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

}
