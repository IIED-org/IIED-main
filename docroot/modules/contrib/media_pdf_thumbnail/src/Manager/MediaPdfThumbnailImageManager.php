<?php

namespace Drupal\media_pdf_thumbnail\Manager;

use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\file\FileInterface;
use Exception;

/**
 * Class MediaPdfThumbnailImageManager.
 *
 * @package Drupal\media_pdf_thumbnail\Manager
 */
class MediaPdfThumbnailImageManager {

  const VALID_MIME_TYPE = ['application/pdf'];

  const GENERIC_FILENAME = 'generic.png';

  /**
   * MediaPdfThumbnailManager.
   *
   * @var \Drupal\media_pdf_thumbnail\Manager\MediaPdfThumbnailImagickManager
   */
  protected $mediaPdfThumbnailImagickManager;

  /**
   * EntityTypeManager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * FileSystem.
   *
   * @var \Drupal\Core\File\FileSystem
   */
  protected $fileSystem;

  /**
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * @var \Drupal\Core\Cache\Cache
   */
  protected $cache;

  /**
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * MediaPdfThumbnailImageManager constructor.
   *
   * @param \Drupal\media_pdf_thumbnail\Manager\MediaPdfThumbnailImagickManager $mediaPdfThumbnailImagickManager
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   * @param \Drupal\Core\File\FileSystemInterface $fileSystem
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   * @param \Drupal\Core\Database\Connection $connection
   * @param \Drupal\Core\Cache\CacheTagsInvalidatorInterface $cache
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerChannelFactory
   */
  public function __construct(MediaPdfThumbnailImagickManager $mediaPdfThumbnailImagickManager, EntityTypeManagerInterface $entityTypeManager, FileSystemInterface $fileSystem, ConfigFactoryInterface $configFactory, Connection $connection, CacheTagsInvalidatorInterface $cache, LoggerChannelFactoryInterface $loggerChannelFactory) {
    $this->mediaPdfThumbnailImagickManager = $mediaPdfThumbnailImagickManager;
    $this->entityTypeManager = $entityTypeManager;
    $this->fileSystem = $fileSystem;
    $this->configFactory = $configFactory;
    $this->connection = $connection;
    $this->cache = $cache;
    $this->logger = $loggerChannelFactory->get('Media pdf thumbnail');
  }

  /**
   * @param \Drupal\Core\Entity\EntityInterface $entity
   * @param $fieldName
   * @param int $page
   *
   * @return array|\Drupal\Core\Entity\EntityInterface[]|false|void|null
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function createThumbnail(EntityInterface $entity, $fieldName, $page = 1) {

    if (empty($entity)) {
      return FALSE;
    }

    if ($entity->hasField($fieldName) && !empty($entity->get($fieldName)->getValue())) {
      $fileEntity = $this->getFileEntity($entity->get($fieldName)->getValue()[0]['target_id']);
      if ($fileEntity && in_array($fileEntity->getMimeType(), self::VALID_MIME_TYPE)) {
        $pdfImage = $this->getPdfImage($entity, $fieldName, $fileEntity, $page);
        if (empty($pdfImage)) {
          $pdfImage = $this->createPdfImageEntity($entity, $fieldName, $fileEntity, $page, $this->createThumbnailFileEntity($this->generatePdfImage($fileEntity, $page)));
        }
        return $pdfImage;
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
   * @param \Drupal\Core\Entity\EntityInterface $entity
   * @param string $fieldName
   * @param \Drupal\file\FileInterface $fileEntity
   * @param $page
   *
   * @return array|null
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getPdfImage(EntityInterface $entity, string $fieldName, FileInterface $fileEntity, $page) {

    $pdfImageEntity = $this->entityTypeManager->getStorage('pdf_image_entity')->loadByProperties([
      'referenced_entity_type' => $entity->getEntityTypeId(),
      'referenced_entity_id' => $entity->id(),
      'referenced_entity_revision_id' => $entity->getLoadedRevisionId(),
      'referenced_entity_lang' => $entity->language()->getId(),
      'referenced_entity_field' => $fieldName,
      'pdf_file_id' => $fileEntity->id(),
      'pdf_file_page' => $page,
    ]);

    $pdfImageEntity = !empty($pdfImageEntity) ? reset($pdfImageEntity) : NULL;

    if (!empty($pdfImageEntity) && !empty($pdfImageEntity->get('image_file_uri')->value)) {
      return [
        'pdf_id' => $pdfImageEntity->get('pdf_file_id')->value,
        'pdf_uri' => $pdfImageEntity->get('pdf_file_uri')->value,
        'image_id' => $pdfImageEntity->get('image_file_id')->value,
        'image_uri' => $pdfImageEntity->get('image_file_uri')->value,
      ];
    }
    return NULL;
  }

  /**
   * @param \Drupal\Core\Entity\EntityInterface $entity
   * @param string $fieldName
   * @param \Drupal\Core\Entity\EntityInterface $fileEntity
   * @param $page
   * @param $imageFileInfo
   *
   * @return array|null
   */
  protected function createPdfImageEntity(EntityInterface $entity, string $fieldName, EntityInterface $fileEntity, $page, $imageFileInfo) {

    if (!empty($imageFileInfo['fid']) && !empty($imageFileInfo['uri'])) {
      try {
        $newPdfImageFile = $this->entityTypeManager->getStorage('pdf_image_entity')->create([
          'referenced_entity_type' => $entity->getEntityTypeId(),
          'referenced_entity_bundle' => $entity->bundle(),
          'referenced_entity_id' => $entity->id(),
          'referenced_entity_revision_id' => $entity->getLoadedRevisionId(),
          'referenced_entity_lang' => $entity->language()->getId(),
          'referenced_entity_field' => $fieldName,
          'pdf_file_id' => $fileEntity->id(),
          'pdf_file_uri' => $fileEntity->getFileUri(),
          'pdf_file_page' => $page,
          'image_file_id' => $imageFileInfo['fid'],
          'image_file_uri' => $imageFileInfo['uri'],
        ]);

        $newPdfImageFile->save();
        return [
          'pdf_id' => $fileEntity->id(),
          'pdf_uri' => $fileEntity->getFileUri(),
          'pdf_page' => $page,
          'image_id' => $imageFileInfo['fid'],
          'image_uri' => $imageFileInfo['uri'],
        ];
      }
      catch (Exception $e) {
        $this->logger->error($e->getMessage());
        return NULL;
      }
    }
    return NULL;
  }

  /**
   * @param $fileUri
   *
   * @return array|void|null
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function createThumbnailFileEntity($fileUri) {

    if (empty($fileUri)) {
      return;
    }

    $infos = pathinfo($fileUri);
    $fileEntity = $this->entityTypeManager->getStorage('file')->create([
      'uri' => $fileUri,
      'status' => FileInterface::STATUS_PERMANENT,
    ]);
    $fileEntity->save();
    return $fileEntity ? [
      'fid' => $fileEntity->id(),
      'filename' => $infos['filename'],
      'uri' => $fileUri,
    ] : NULL;
  }

  /**
   * @param \Drupal\file\FileInterface $fileEntity
   * @param $page
   *
   * @return mixed|null
   */
  protected function generatePdfImage(FileInterface $fileEntity, $page = 1) {
    $fileInfos = $this->getFileInfos($fileEntity, $page);
    if (!empty($fileInfos['source']) && !empty($fileInfos['destination'])) {
      return $this->mediaPdfThumbnailImagickManager->generateImageFromPDF($fileInfos['source'], $fileInfos['destination'], $page);
    }
    return NULL;
  }

  /**
   * @param \Drupal\file\FileInterface $fileEntity
   * @param $page
   *
   * @return array
   */
  protected function getFileInfos(FileInterface $fileEntity, $page) {
    $sourcePath = $fileEntity->getFileUri();
    $destinationPath = $sourcePath . '-p' . $page . '.jpeg';
    return ['source' => $sourcePath, 'destination' => $destinationPath];
  }

  /**
   * @return int|string|null
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getGenericThumbnail() {
    $uri = $this->configFactory->get('media.settings')->get('icon_base_uri');
    if (!file_exists($uri . '/' . self::GENERIC_FILENAME)) {
      $path = \Drupal::service('module_handler')->getModule('media')->getPath();
      $genFilePath = $path . '/images/icons/' . self::GENERIC_FILENAME;
      $this->fileSystem->prepareDirectory($uri, FileSystemInterface::MODIFY_PERMISSIONS | FileSystemInterface::EXISTS_REPLACE);
      copy($genFilePath, $uri . '/' . self::GENERIC_FILENAME);
    }
    $files = $this->entityTypeManager->getStorage('file')->loadByProperties(['uri' => $uri . '/' . self::GENERIC_FILENAME]);
    return !empty($files) ? reset($files)->id() : NULL;
  }

}
