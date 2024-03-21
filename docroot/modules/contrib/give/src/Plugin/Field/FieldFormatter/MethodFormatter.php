<?php

namespace Drupal\give\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'user_name' formatter.
 *
 * @FieldFormatter(
 *   id = "give_method",
 *   label = @Translation("Donation method"),
 *   description = @Translation("Display the method, if any, with which a donation is given."),
 *   field_types = {
 *     "give_method"
 *   }
 * )
 */
class MethodFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    foreach ($items as $delta => $item) {
      /* @var $donation \Drupal\give\DonationInterface */
      if ($donation = $item->getEntity()) {
        $elements[$delta] = [
          '#markup' => payment_method_names()[$donation->method->value],
          '#cache' => [
            'tags' => $donation->getCacheTags(),
          ],
        ];
      }
    }

    return $elements;
  }

}
