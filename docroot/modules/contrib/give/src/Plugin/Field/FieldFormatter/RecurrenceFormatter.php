<?php

namespace Drupal\give\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'user_name' formatter.
 *
 * @FieldFormatter(
 *   id = "give_recurrence",
 *   label = @Translation("Recurrence"),
 *   description = @Translation("Display the automatic frequency, if any, with which a donation is given."),
 *   field_types = {
 *     "integer"
 *   }
 * )
 */
class RecurrenceFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      /* @var $donation \Drupal\give\DonationInterface */
      if ($donation = $item->getEntity()) {
        $elements[$delta] = [
          '#markup' => $donation->getRecurrence(),
          '#cache' => [
            'tags' => $donation->getCacheTags(),
          ],
        ];
      }
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    return $field_definition->getTargetEntityTypeId() === 'give_donation' && $field_definition->getName() === 'recurring';
  }

}
