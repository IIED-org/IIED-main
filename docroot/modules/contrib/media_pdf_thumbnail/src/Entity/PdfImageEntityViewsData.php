<?php

namespace Drupal\media_pdf_thumbnail\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Pdf image entity entities.
 */
class PdfImageEntityViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData(): array {
    $data = parent::getViewsData();

    // Additional information for Views integration, such as table joins, can be
    // put here.
    return $data;
  }

}
