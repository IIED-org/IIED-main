<?php

namespace Drupal\isbn\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'isbn_default' formatter.
 *
 * @FieldFormatter(
 *   id = "isbn_default",
 *   label = @Translation("Non formatted value"),
 *   field_types = {
 *     "isbn"
 *   }
 * )
 */
class IsbnPlainFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $summary[] = t('Displays the ISBN string without formatting it.');

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];

    foreach ($items as $delta => $item) {
      // Render each element as markup.
      $element[$delta] = [
        '#type' => 'markup',
        '#markup' => $item->value,
      ];
    }

    return $element;
  }
}
