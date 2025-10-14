<?php

namespace Drupal\ds\Plugin\DsField\Book;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ds\Attribute\DsField;
use Drupal\ds\Plugin\DsField\DsFieldBase;

/**
 * Plugin that the book navigation.
 */
#[DsField(
  id: 'book_navigation',
  title: new TranslatableMarkup('Book navigation'),
  entity_type: 'node',
  provider: 'book'
)]
class BookNavigation extends DsFieldBase {

  /**
   * {@inheritdoc}
   */
  public function isAllowed() {

    // We only allow the 'full' and 'default' view mode.
    if (!in_array($this->viewMode(), ['default', 'full'])) {
      return FALSE;
    }

    // Get all the allowed types.
    $types = \Drupal::config('book.settings')->get('allowed_types');

    if (!empty($types)) {
      foreach ($types as $type) {
        if ($type) {
          return TRUE;
        }
      }
    }

    // Return false when there are no displays.
    return FALSE;
  }

}
