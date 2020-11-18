<?php

namespace Drupal\media_pdf_thumbnail\Manager;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\State\StateInterface;

/**
 * Class MediaPdfThumbnailImageManager.
 *
 * @package Drupal\media_pdf_thumbnail\Manager
 */
class MediaPdfThumbnailImageManager {

  /**
   * MediaPdfThumbnailManager.
   *
   * @var \Drupal\media_pdf_thumbnail\Manager\MediaPdfThumbnailImagickManager
   */
  protected $mediaPdfThumbnailImagickManager;

  /**
   * EntityTypeManager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * FileSystem.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * MediaPdfThumbnailManager constructor.
   *
   * @param \Drupal\media_pdf_thumbnail\Manager\MediaPdfThumbnailImagickManager $mediaPdfThumbnailImagickManager
   *   $mediaPdfThumbnailImagickManager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   EntityTypeManager.
   * @param \Drupal\Core\File\FileSystemInterface $fileSystem
   *   FileSystem.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   * @param \Drupal\Core\State\StateInterface $state
   */
  public function __construct(MediaPdfThumbnailImagickManager $mediaPdfThumbnailImagickManager, EntityTypeManagerInterface $entityTypeManager, FileSystemInterface $fileSystem, ConfigFactoryInterface $configFactory, StateInterface $state) {
    $this->mediaPdfThumbnailImagickManager = $mediaPdfThumbnailImagickManager;
    $this->entityTypeManager = $entityTypeManager;
    $this->fileSystem = $fileSystem;
    $this->configFactory = $configFactory;
    $this->state = $state;
  }

  /**
   * Create pdf thumbnail.
   *
   * @param $entity
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function createThumbnail($entity) {
    $entityTypeId = $entity->getEntityTypeId();
    $bundle = $entity->bundle();
    if ($entityTypeId == 'media') {
      $config = $this->configFactory->get('media_pdf_thumbnail.bundles.settings');

      // If not enabled for this bundle.
      if (empty($config->get($bundle . '_enable'))) {
        return;
      }

      $fieldName = $config->get($bundle . '_field');
      if ($fieldName && $entity->hasField($fieldName) && !empty($entity->get($fieldName)
          ->getValue())) {
        $fileEntity = $this->getFileEntity($entity->get($fieldName)
          ->getValue()[0]['target_id']);
        if ($fileEntity && $fileEntity->getMimeType() == 'application/pdf') {
          $fileEntityInfo = !$this->generatePdfImage($fileEntity) ?: $this->createThumbnailFileEntity($fileEntity->getFileUri());
          if (!empty($fileEntityInfo)) {
            $this->setImageToMediaThumbnail($entity, $fileEntityInfo);
          }
        }
      }
    }
  }

  /**
   * @param $fid
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getFileEntity($fid) {
    return $this->entityTypeManager->getStorage('file')->load($fid);
  }

  /**
   * @param $fileEntity
   *
   * @return bool|string
   */
  protected function generatePdfImage($fileEntity) {
    $fileInfos = $this->getFileInfos($fileEntity);
    if (!empty($fileInfos['source']) && !empty($fileInfos['destination'])) {
      return $this->mediaPdfThumbnailImagickManager->generateImageFromPDF($fileInfos['source'],
        $fileInfos['destination']);
    }
    return NULL;
  }

  /**
   * @param $fileEntity
   *
   * @return array
   */
  protected function getFileInfos($fileEntity) {
    $fileUri = $fileEntity->getFileUri();
    $sourcePath = $this->fileSystem->realpath($fileUri);
    $destinationPath = $sourcePath . '.jpeg';
    return ['source' => $sourcePath, 'destination' => $destinationPath];
  }

  /**
   * Create file entity.
   *
   * @param string $fileUri
   *   File uri.
   *
   * @return array File entity id.
   *   File entity id.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function createThumbnailFileEntity($fileUri) {
    $fileUriArray = explode('/', $fileUri);
    $filenameNoExtension = str_replace('.pdf', '', end($fileUriArray));
    $fileUri = str_replace('.pdf', '.pdf.jpeg', $fileUri);
    $fileEntity = $this->entityTypeManager->getStorage('file')->create([
      'uri' => $fileUri,
      'status' => FILE_STATUS_PERMANENT,
    ]);
    $fileEntity->save();
    return $fileEntity ? [
      'fid' => $fileEntity->id(),
      'filename' => $filenameNoExtension,
    ] : NULL;
  }

  /**
   * @param $entity
   * @param $fileEntityInfo
   *
   */
  protected function setImageToMediaThumbnail($entity, $fileEntityInfo) {
    $entity->set('thumbnail',
      [
        'target_id' => $fileEntityInfo['fid'],
        'alt' => $fileEntityInfo['filename'],
      ]);
    $this->storeFileId($fileEntityInfo['fid']);
  }

  /**
   * @param $fid
   *
   */
  protected function storeFileId($fid) {
    $fids = explode(',', $this->state->get('media_pdf_thumbnail_fids'));
    $fids[] = $fid;
    $this->state->set('media_pdf_thumbnail_fids', implode(',', $fids));
  }

  /**
   * @param $files
   *
   * @return bool
   */
  public function isUsedAsPdfThumbnail($files) {
    $fids = explode(',', $this->state->get('media_pdf_thumbnail_fids'));
    foreach ($files as $file) {
      if (in_array($file->id(), $fids)) {
        return TRUE;
      }
    }
    return FALSE;
  }

}
