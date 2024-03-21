<?php

namespace Drupal\give\Plugin\Field\FieldType;

use Drupal\Core\TypedData\OptionsProviderInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\StringItem;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Field\FieldDefinitionInterface;

/**
 * Defines the payment methods enumerated field type
 *
 * @FieldType(
 *   id = "give_method",
 *   label = @Translation("Give payment method"),
 *   description = @Translation("Enumerated fields extensible with a hook."),
 *   category = @Translation("Give"),
 *   default_widget = "options_select",
 *   default_formatter = "basic_string"
 * )
 * @todo inject \Drupal::moduleHandler()
 */
class PaymentMethod extends StringItem implements OptionsProviderInterface{

  /**
   * Process donation with Stripe.
   */
  const GIVE_VIA_STRIPE = 'card';

  /**
   * Process bank transfer with Stripe.
   */
  const GIVE_VIA_BANK = 'banktransfer';

  /**
   * Accept a pledge to pay by check or other.
   */
  const GIVE_VIA_CHECK = 'check';
  const GIVE_VIA_OTHER = 'other';

  /**
   * {@inheritdoc}
   */
  public function getPossibleOptions(AccountInterface $account = NULL) {
    return payment_method_names();
  }

  /**
   * {@inheritdoc}
   */
  public function getPossibleValues(AccountInterface $account = NULL) {
    return array_keys($this->getPossibleOptions($account));
  }

  /**
   * {@inheritdoc}
   */
  public function getSettableValues(AccountInterface $account = NULL) {
    return array_keys($this->getPossibleValues($account));
  }

  /**
   * {@inheritdoc}
   */
  public function getSettableOptions(AccountInterface $account = NULL) {
    return $this->getPossibleOptions($account);
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $values = $this->getSettableValues();
    return $values[rand(0, count($values))];
  }

}
