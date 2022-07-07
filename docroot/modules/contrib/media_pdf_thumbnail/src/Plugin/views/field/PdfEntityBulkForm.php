<?php

namespace Drupal\media_pdf_thumbnail\Plugin\views\field;

use Drupal\views\Plugin\views\field\BulkForm;

/**
 * Defines a PDF Image Entity operations bulk form element.
 *
 * @ViewsField("pdf_image_entity_bulk_form")
 */
class PdfEntityBulkForm extends BulkForm {

  /**
   * {@inheritdoc}
   */
  protected function emptySelectedMessage() {
    return $this->t('No content selected.');
  }

}
