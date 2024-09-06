<?php

namespace Drupal\media_pdf_thumbnail\Manager;

use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManager;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
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
  protected FileSystemInterface $fileSystem;

  /**
   * @var \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface
   */
  protected StreamWrapperManagerInterface $streamWrapperManager;

  /**
   * MediaPdfThumbnailImagickManager constructor.
   *
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerChannel
   * @param \Drupal\Core\File\FileSystemInterface $fileSystem
   * @param \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface $streamWrapperManager
   */
  public function __construct(LoggerChannelFactoryInterface $loggerChannel, FileSystemInterface $fileSystem, StreamWrapperManagerInterface $streamWrapperManager) {
    $this->logger = $loggerChannel->get('Media PDF Thumbnail (MediaPdfThumbnailImagickManager');
    $this->fileSystem = $fileSystem;
    $this->streamWrapperManager = $streamWrapperManager;
  }

  /**
   * @param $source
   * @param $target
   * @param $imageFormat
   * @param $page
   *
   * @return mixed|null
   */
  public function generateImageFromPDF($source, $target, $imageFormat, $page = 1) {
    $directory = dirname($target);
    $this->fileSystem->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS | FileSystemInterface::EXISTS_REPLACE);
    try {
      $streamWrapper = $this->streamWrapperManager->getViaUri($source);
      $realPath = $streamWrapper->realpath();
      $path = !empty($realPath) ? $realPath : $source;
      $pdf = new Pdf($path);
      $status = $this->generate($pdf, $target, $imageFormat, $page);
    }
    catch (\Exception $e) {
      $this->logger->error($e->getMessage());
      return NULL;
    }
    return $status ? $target : NULL;
  }

  /**
   * @param \Spatie\PdfToImage\Pdf $pdf
   * @param $target
   * @param $imageFormat
   * @param $page
   *
   * @return bool
   * @throws \Spatie\PdfToImage\Exceptions\InvalidFormat
   * @throws \Spatie\PdfToImage\Exceptions\InvalidLayerMethod
   * @throws \Spatie\PdfToImage\Exceptions\PageDoesNotExist
   */
  protected function generate(Pdf $pdf, $target, $imageFormat, $page): bool {
    $pdf->getNumberOfPages() > intval($page) ? $pdf->setPage(intval($page)) : '';
    $pdf->setLayerMethod(NULL);
    $pdf->setOutputFormat($imageFormat);
    if (file_exists($target)) {
      $this->fileSystem->delete($target);
    }
    return $pdf->saveImage($target);
  }

}
