<?php

namespace Drupal\give\Plugin\Field\FieldFormatter;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Format a donation amount with the right currency.
 *
 * @FieldFormatter(
 *   id = "give_cents_to_dollars",
 *   label = @Translation("Donation amount"),
 *   description = @Translation("Convert stored cents into dollars or whatever"),
 *   field_types = {
 *     "integer"
 *   }
 * )
 * @todo Replaced this with the currency module.
 * @todo this takes nothing from NumericFormatterBase
 */
class CentsToDollarsFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    foreach ($items as $delta => $item) {
      $elements[$delta] = [
        '#markup' => $items->getEntity()->getFormattedAmount()
      ];
    }
    return $elements;
  }

}
