<?php

namespace Drupal\media_pdf_thumbnail\Manager;

use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Spatie\PdfToImage\Pdf;

/**
 * Class MediaPdfThumbnailImagickManager.
 *
 * @package Drupal\media_pdf_thumbnail\Manager
 */
class MediaPdfThumbnailImagickManager {

  /**
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $logger;

  /**
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * MediaPdfThumbnailImagickManager constructor.
   *
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerChannel
   * @param \Drupal\Core\File\FileSystemInterface $fileSystem
   */
  public function __construct(LoggerChannelFactoryInterface $loggerChannel, FileSystemInterface $fileSystem) {
    $this->logger = $loggerChannel->get('Media PDF Thumbnail (MediaPdfThumbnailImagickManager');
    $this->fileSystem = $fileSystem;
  }

  /**
   * @param $source
   * @param $target
   * @param int $page
   *
   * @return mixed|null
   */
  public function generateImageFromPDF($source, $target, $page = 1) {
    $directory = dirname($target);
    $this->fileSystem->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS | FileSystemInterface::EXISTS_REPLACE);
    try {
      $pdf = new Pdf($source);
      $pdf->getNumberOfPages() > intval($page) ? $pdf->setPage(intval($page)) : '';
      if (method_exists($pdf, 'setLayerMethod') && is_callable([$pdf, 'setLayerMethod'])) {
        $pdf->setLayerMethod(NULL);
      }
      if (file_exists($target)) {
        $this->fileSystem->delete($target);
      }
      $status = $pdf->saveImage($target);
    }
    catch (\Exception $e) {
      try {
        $pdf = new Pdf($this->fileSystem->realpath($source));
        $pdf->getNumberOfPages() > intval($page) ? $pdf->setPage(intval($page)) : '';
        if (file_exists($target)) {
          $this->fileSystem->delete($target);
        }
        $status = $pdf->saveImage($target);
      }
      catch (\Exception $e) {
        $this->logger->error($e->getMessage());
        return NULL;
      }
    }
    return $status ? $target : NULL;
  }

}
