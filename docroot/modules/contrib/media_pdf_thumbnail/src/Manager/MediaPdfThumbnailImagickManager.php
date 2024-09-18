<?php

namespace Drupal\media_pdf_thumbnail\Manager;

use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Logger\LoggerChannel;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\media_pdf_thumbnail\Pdf;

/**
 * Class MediaPdfThumbnailImagickManager.
 *
 * Manages the creation of PDF images.
 *
 * @package Drupal\media_pdf_thumbnail\Manager
 */
class MediaPdfThumbnailImagickManager {

  /**
   * Logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannel|\Drupal\Core\Logger\LoggerChannelInterface
   */
  protected LoggerChannel | LoggerChannelInterface $logger;

  /**
   * File system.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected FileSystemInterface $fileSystem;

  /**
   * Stream wrapper manager.
   *
   * @var \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface
   */
  protected StreamWrapperManagerInterface $streamWrapperManager;

  /**
   * MediaPdfThumbnailImagickManager constructor.
   *
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerChannel
   *   Logger channel.
   * @param \Drupal\Core\File\FileSystemInterface $fileSystem
   *   File system.
   * @param \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface $streamWrapperManager
   *   Stream wrapper manager.
   */
  public function __construct(LoggerChannelFactoryInterface $loggerChannel, FileSystemInterface $fileSystem, StreamWrapperManagerInterface $streamWrapperManager) {
    $this->logger = $loggerChannel->get('Media PDF Thumbnail (MediaPdfThumbnailImagickManager');
    $this->fileSystem = $fileSystem;
    $this->streamWrapperManager = $streamWrapperManager;
  }

  /**
   * Generate image from PDF.
   *
   * @param string $source
   *   Path to PDF.
   * @param string $target
   *   Path to image.
   * @param string $imageFormat
   *   Image format.
   * @param string|int $page
   *   Page.
   *
   * @return bool|string|null
   *   Status
   */
  public function generateImageFromPdf(string $source, string $target, string $imageFormat, string | int $page = 1): null | bool | string {
    $directory = dirname($target);
    $this->fileSystem->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS);
    try {
      $pathInfos = $this->handleSourceFile($source);
      if (empty($pathInfos['path'])) {
        return NULL;
      }
      $status = $this->generate($pathInfos['path'], $target, $imageFormat, $page);
      if (!empty($pathInfos['delete'])) {
        $this->fileSystem->delete($pathInfos['path']);
      }
    }
    catch (\Exception $e) {
      $this->logger->error($e->getMessage());
      return NULL;
    }
    return $status ? $target : NULL;
  }

  /**
   * Generate image from PDF.
   *
   * @param string $pdfFilePath
   *   Path to PDF.
   * @param string $target
   *   Path to image.
   * @param string $imageFormat
   *   Image format.
   * @param string|int $page
   *   Page.
   *
   * @return bool
   *   Status.
   *
   * @throws \ImagickException
   * @throws \Spatie\PdfToImage\Exceptions\InvalidFormat
   * @throws \Spatie\PdfToImage\Exceptions\InvalidLayerMethod
   * @throws \Spatie\PdfToImage\Exceptions\PdfDoesNotExist
   */
  protected function generate(string $pdfFilePath, string $target, string $imageFormat, string | int $page): bool {
    $pdf = new Pdf($pdfFilePath, $page);
    $pdf->setLayerMethod(NULL);
    $pdf->setOutputFormat($imageFormat);
    if (file_exists($target)) {
      $this->fileSystem->delete($target);
    }
    $status = $pdf->saveImage($target);
    $pdf->imagick->clear();
    $pdf->imagick->destroy();
    return $status;
  }

  /**
   * Handle source file.
   *
   * @param string $source
   *   Path to file.
   *
   * @return array|null
   *   File infos.
   */
  protected function handleSourceFile(string $source): ?array {
    $streamWrapper = $this->streamWrapperManager->getViaUri($source);

    if (!$streamWrapper) {
      return NULL;
    }

    // For local files, we can use the realpath() method.
    if ($streamWrapper->getType() === StreamWrapperInterface::LOCAL_NORMAL) {
      $realPath = $streamWrapper->realpath();
      return [
        'path' => !empty($realPath) ? $realPath : $source,
        'delete' => FALSE,
      ];
    }

    // With remote file storage,
    // we need to copy the file to a temporary location as
    // Imagick may not be able to read it.
    $data = '';
    if ($streamWrapper->stream_open($source, 'rb', STREAM_REPORT_ERRORS, $opened_path)) {
      $stat = $streamWrapper->stream_stat();
      while (!$streamWrapper->stream_eof()) {
        $data .= $streamWrapper->stream_read($stat['size']);
      }
      $streamWrapper->stream_close();
    }

    if (empty($data)) {
      return NULL;
    }

    $tempPdfPath = 'temporary://' . basename($source);
    $tempPdfPath = str_replace(' ', '_', $tempPdfPath);
    $tempStreamWrapper = $this->streamWrapperManager->getViaScheme('temporary');
    if ($tempStreamWrapper->stream_open($tempPdfPath, 'w', STREAM_REPORT_ERRORS, $opened_path)) {
      $tempStreamWrapper->stream_write($data);
      $tempStreamWrapper->stream_close();
    }

    return [
      'path' => $tempPdfPath,
      'delete' => TRUE,
    ];
  }

}
