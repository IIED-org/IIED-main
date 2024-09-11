<?php

namespace Drupal\media_pdf_thumbnail\Manager;

use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\file\FileInterface;
use Drupal\media_pdf_thumbnail\Form\MediaPdfThumbnailSettingsForm;
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
  protected MediaPdfThumbnailImagickManager $mediaPdfThumbnailImagickManager;

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
  protected Connection $connection;

  /**
   * @var \Drupal\Core\Cache\Cache
   */
  protected $cache;

  /**
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected LoggerChannelInterface $logger;

  /**
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected ModuleHandlerInterface $moduleHandler;

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
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   */
  public function __construct(MediaPdfThumbnailImagickManager $mediaPdfThumbnailImagickManager, EntityTypeManagerInterface $entityTypeManager, FileSystemInterface $fileSystem, ConfigFactoryInterface $configFactory, Connection $connection, CacheTagsInvalidatorInterface $cache, LoggerChannelFactoryInterface $loggerChannelFactory, ModuleHandlerInterface $moduleHandler) {
    $this->mediaPdfThumbnailImagickManager = $mediaPdfThumbnailImagickManager;
    $this->entityTypeManager = $entityTypeManager;
    $this->fileSystem = $fileSystem;
    $this->configFactory = $configFactory;
    $this->connection = $connection;
    $this->cache = $cache;
    $this->logger = $loggerChannelFactory->get('Media pdf thumbnail');
    $this->moduleHandler = $moduleHandler;
  }

  /**
   * @param \Drupal\Core\Entity\EntityInterface $entity
   * @param $fileFieldName
   * @param $imageFormat
   * @param $page
   *
   * @return array|false|null
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function createThumbnail(EntityInterface $entity, $fileFieldName, $imageFormat, $page = 1) {

    if (empty($entity->id())) {
      $this->logger->error('Entity id is empty');
      return FALSE;
    }

    $fileEntity = $this->getFileEntityFromField($entity, $fileFieldName);

    if (empty($fileEntity)) {
      $this->logger->error('File entity is empty');
      return FALSE;
    }

    $pdfImage = $this->getImageIfExists($entity, $fileEntity, $fileFieldName, $imageFormat, $page);

    if (empty($pdfImage)) {
      $pdfImage = $this->createPdfImageEntity($entity, $fileFieldName, $fileEntity, $page, $this->createThumbnailFileEntity($this->generatePdfImage($fileEntity, $imageFormat, $page)));
    }

    return $pdfImage;

  }

  /**
   * @param \Drupal\Core\Entity\EntityInterface $entity
   * @param \Drupal\file\FileInterface $fileEntity
   * @param $fieldName
   * @param $format
   * @param $page
   *
   * @return array|null
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getImageIfExists(EntityInterface $entity, FileInterface $fileEntity, $fieldName, $format, $page = 1): ?array {
    if (in_array($fileEntity->getMimeType(), self::VALID_MIME_TYPE)) {
      return $this->getPdfImage($entity, $fieldName, $fileEntity, $format, $page);
    }
    return NULL;
  }

  /**
   * @param \Drupal\Core\Entity\EntityInterface $entity
   * @param $fieldName
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getFileEntityFromField(EntityInterface $entity, $fieldName): ?EntityInterface {
    $fileEntity = NULL;
    if ($entity->hasField($fieldName) && !empty($entity->get($fieldName)->getValue())) {
      $fileEntity = $this->getFileEntity($entity->get($fieldName)->getValue()[0]['target_id']);
    }
    return $fileEntity;
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
   * @param $format
   * @param $page
   *
   * @return array|null
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getPdfImage(EntityInterface $entity, string $fieldName, FileInterface $fileEntity, $format, $page) {

    $pdfImageEntity = $this->entityTypeManager->getStorage('pdf_image_entity')->loadByProperties([
      'referenced_entity_type' => $entity->getEntityTypeId(),
      'referenced_entity_id' => $entity->id(),
      'referenced_entity_revision_id' => $entity->getLoadedRevisionId(),
      'referenced_entity_lang' => $entity->language()->getId(),
      'referenced_entity_field' => $fieldName,
      'pdf_file_id' => $fileEntity->id(),
      'pdf_file_page' => $page,
      'image_format' => $format,
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
          'image_format' => $imageFileInfo['format'],
        ]);

        $newPdfImageFile->save();
        return [
          'pdf_id' => $fileEntity->id(),
          'pdf_uri' => $fileEntity->getFileUri(),
          'pdf_page' => $page,
          'image_id' => $imageFileInfo['fid'],
          'image_uri' => $imageFileInfo['uri'],
          'image_format' => $imageFileInfo['format'],
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
      'format' => $infos['extension'],
    ] : NULL;
  }

  /**
   * @param \Drupal\file\FileInterface $fileEntity
   * @param $imageFormat
   * @param $page
   *
   * @return mixed|null
   */
  protected function generatePdfImage(FileInterface $fileEntity, $imageFormat, $page = 1) {
    $fileInfos = $this->getFileInfos($fileEntity, $imageFormat, $page);
    if (!empty($fileInfos['source']) && !empty($fileInfos['destination'])) {
      return $this->mediaPdfThumbnailImagickManager->generateImageFromPDF($fileInfos['source'], $fileInfos['destination'], $imageFormat, $page);
    }
    return NULL;
  }

  /**
   * @param \Drupal\file\FileInterface $fileEntity
   * @param $imageFormat
   * @param $page
   *
   * @return array
   */
  protected function getFileInfos(FileInterface $fileEntity, $imageFormat, $page): array {

    $sourcePath = $fileEntity->getFileUri();
    $path = $sourcePath;

    $destinationUri = str_starts_with($fileEntity->getFileUri(), 'private://') ? 'private' : 'public';

    // Set destination from config.
    $configDestinationUri = $this->configFactory->get(MediaPdfThumbnailSettingsForm::CONFIG_NAME)->get(MediaPdfThumbnailSettingsForm::getConfigUri($destinationUri));
    if (!empty($configDestinationUri)) {
      $fileBaseName = pathinfo($sourcePath)['basename'];
      $path = $configDestinationUri . '/' . $fileBaseName;
    }

    $destinationPath = $path . '-p' . $page . '.' . $imageFormat;
    $infos = ['source' => $sourcePath, 'destination' => $destinationPath];
    $context = ['file_entity' => $fileEntity, 'image_format' => $imageFormat, 'page' => $page];

    // Give a chance to alter destination with hook.
    $this->moduleHandler->alter('media_pdf_thumbnail_image_destination', $infos, $context);

    return $infos;
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

  /**
   * @param $fileUri
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getPdfEntityByPdfFileUri($fileUri) {
    return $this->entityTypeManager->getStorage('pdf_image_entity')->loadByProperties(['image_file_uri' => $fileUri]);
  }

}
