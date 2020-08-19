<?php

namespace Drupal\isbn\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'isbn_formatted_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "isbn_formatted_formatter",
 *   label = @Translation("ISBN formatted value"),
 *   field_types = {
 *     "isbn"
 *   }
 * )
 */
class IsbnFormattedFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $summary[] = t('Displays the ISBN value formatted.');

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
        '#markup' => $this->format($item->value),
      ];
    }

    return $element;
  }

  private function format($isbn_number) {
    $isbn_tools = \Drupal::service("isbn.isbn_service");
    return $isbn_tools->format($isbn_number);
  }
}
