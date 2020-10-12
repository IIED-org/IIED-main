<?php

namespace Drupal\media_pdf_thumbnail\Manager;

use Drupal\Core\Logger\LoggerChannelFactory;
use Spatie\PdfToImage\Pdf;

/**
 * Class MediaPdfThumbnailImagickManager.
 *
 * @package Drupal\media_pdf_thumbnail\Manager
 */
class MediaPdfThumbnailImagickManager {

  protected $loggerFactory;

  /**
   * MediaPdfThumbnailImagickManager constructor.
   *
   * @param \Drupal\Core\Logger\LoggerChannelFactory $loggerChannelFactory
   */
  public function __construct(LoggerChannelFactory $loggerChannelFactory) {
    $this->loggerFactory = $loggerChannelFactory->get('Media PDF Thumbnail');
  }

  /**
   * Generate image from PDF file.
   *
   * @param string $source
   *   File source.
   * @param string $target
   *   File target.
   *
   * @return string
   *   File path.
   *
   */
  public function generateImageFromPDF($source, $target) {
    try {
      $pdf = new Pdf($source);
      $pdf->saveImage($target);
    }
    catch (\Exception $e) {
      $this->loggerFactory->error($e->getMessage());
      return FALSE;
    }
    return TRUE;
  }

}
