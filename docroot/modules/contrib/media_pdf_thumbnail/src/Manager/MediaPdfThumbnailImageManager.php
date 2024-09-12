<?php

namespace Drupal\media_pdf_thumbnail\Manager;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\File\FileSystem;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\file\FileInterface;
use Drupal\media_pdf_thumbnail\Form\MediaPdfThumbnailSettingsForm;

/**
 * Class MediaPdfThumbnailImageManager.
 *
 * Manages the creation of PDF image entities.
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
  protected EntityTypeManager $entityTypeManager;

  /**
   * FileSystem.
   *
   * @var \Drupal\Core\File\FileSystem
   */
  protected FileSystem $fileSystem;

  /**
   * ConfigFactory.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected ConfigFactory $configFactory;

  /**
   * Connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected Connection $connection;

  /**
   * Cache.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface
   */
  protected CacheTagsInvalidatorInterface $cache;

  /**
   * Logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected LoggerChannelInterface $logger;

  /**
   * Module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected ModuleHandlerInterface $moduleHandler;

  /**
   * MediaPdfThumbnailImageManager constructor.
   *
   * @param \Drupal\media_pdf_thumbnail\Manager\MediaPdfThumbnailImagickManager $mediaPdfThumbnailImagickManager
   *   MediaPdfThumbnailManager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   EntityTypeManager.
   * @param \Drupal\Core\File\FileSystemInterface $fileSystem
   *   FileSystem.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   ConfigFactory.
   * @param \Drupal\Core\Database\Connection $connection
   *   Connection.
   * @param \Drupal\Core\Cache\CacheTagsInvalidatorInterface $cache
   *   Cache.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerChannelFactory
   *   Logger channel.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   Module handler.
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
   * Create thumbnail.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity.
   * @param string $fileFieldName
   *   File field name.
   * @param string $imageFormat
   *   Image format.
   * @param int $page
   *   Page.
   *
   * @return array|false|null
   *   Pdf image.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function createThumbnail(EntityInterface $entity, string $fileFieldName, string $imageFormat, int $page = 1): bool | array | null {
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
   * Get image if exists.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity.
   * @param \Drupal\file\FileInterface $fileEntity
   *   File entity.
   * @param string $fieldName
   *   Field name.
   * @param string $format
   *   Format.
   * @param string|int $page
   *   Page.
   *
   * @return array|null
   *   Pdf image.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getImageIfExists(EntityInterface $entity, FileInterface $fileEntity, string $fieldName, string $format, string | int $page = 1): ?array {
    if (in_array($fileEntity->getMimeType(), self::VALID_MIME_TYPE)) {
      return $this->getPdfImage($entity, $fieldName, $fileEntity, $format, $page);
    }
    return NULL;
  }

  /**
   * Get file entity from field.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity.
   * @param string $fieldName
   *   Field name.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   File entity.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getFileEntityFromField(EntityInterface $entity, string $fieldName): ?EntityInterface {
    $fileEntity = NULL;
    if ($entity->hasField($fieldName) && !empty($entity->get($fieldName)
      ->getValue())) {
      $fileEntity = $this->getFileEntity($entity->get($fieldName)
        ->getValue()[0]['target_id']);
    }
    return $fileEntity;
  }

  /**
   * Get file entity.
   *
   * @param string|int $fid
   *   File id.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   File entity.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getFileEntity(string | int $fid) {
    return $this->entityTypeManager->getStorage('file')->load($fid);
  }

  /**
   * Get pdf image.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity.
   * @param string $fieldName
   *   Field name.
   * @param \Drupal\file\FileInterface $fileEntity
   *   File entity.
   * @param string $format
   *   Format.
   * @param string|int $page
   *   Page.
   *
   * @return array|null
   *   Pdf image.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getPdfImage(EntityInterface $entity, string $fieldName, FileInterface $fileEntity, string $format, string | int $page): ?array {
    $pdfImageEntity = $this->entityTypeManager->getStorage('pdf_image_entity')
      ->loadByProperties([
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
   * Create pdf image entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity.
   * @param string $fieldName
   *   Field name.
   * @param \Drupal\Core\Entity\EntityInterface $fileEntity
   *   File entity.
   * @param string|int $page
   *   Page.
   * @param array $imageFileInfo
   *   Image file info.
   *
   * @return array|null
   *   Pdf image entity infos.
   */
  protected function createPdfImageEntity(EntityInterface $entity, string $fieldName, EntityInterface $fileEntity, string | int $page, array $imageFileInfo): ?array {
    if (!empty($imageFileInfo['fid']) && !empty($imageFileInfo['uri'])) {
      try {
        $newPdfImageFile = $this->entityTypeManager->getStorage('pdf_image_entity')
          ->create([
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
      catch (\Exception $e) {
        $this->logger->error($e->getMessage());
        return NULL;
      }
    }
    return NULL;
  }

  /**
   * Create thumbnail file entity.
   *
   * @param string $fileUri
   *   File URI.
   *
   * @return array|void|null
   *   File infos.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function createThumbnailFileEntity(string $fileUri) {
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
   * Generate pdf image.
   *
   * @param \Drupal\file\FileInterface $fileEntity
   *   File entity.
   * @param string $imageFormat
   *   Image format.
   * @param string|int $page
   *   Page.
   *
   * @return bool|string|null
   *   Return pdf image.
   */
  protected function generatePdfImage(FileInterface $fileEntity, string $imageFormat, int | string $page = 1): bool | string | null {
    $fileInfos = $this->getFileInfos($fileEntity, $imageFormat, $page);
    if (!empty($fileInfos['source']) && !empty($fileInfos['destination'])) {
      return $this->mediaPdfThumbnailImagickManager->generateImageFromPdf($fileInfos['source'], $fileInfos['destination'], $imageFormat, $page);
    }
    return NULL;
  }

  /**
   * Get file infos.
   *
   * @param \Drupal\file\FileInterface $fileEntity
   *   File entity.
   * @param string $imageFormat
   *   Image format.
   * @param string|int $page
   *   Page.
   *
   * @return array
   *   Return file infos.
   */
  protected function getFileInfos(FileInterface $fileEntity, string $imageFormat, string | int $page): array {
    $sourcePath = $fileEntity->getFileUri();
    $path = $sourcePath;

    $destinationUri = str_starts_with($fileEntity->getFileUri(), 'private://') ? 'private' : 'public';

    // Set destination from config.
    $configDestinationUri = $this->configFactory->get(MediaPdfThumbnailSettingsForm::CONFIG_NAME)
      ->get(MediaPdfThumbnailSettingsForm::getConfigUri($destinationUri));
    if (!empty($configDestinationUri)) {
      $fileBaseName = pathinfo($sourcePath)['basename'];
      $path = $configDestinationUri . '/' . $fileBaseName;
    }

    $destinationPath = $path . '-p' . $page . '.' . $imageFormat;
    $infos = ['source' => $sourcePath, 'destination' => $destinationPath];
    $context = [
      'file_entity' => $fileEntity,
      'image_format' => $imageFormat,
      'page' => $page,
    ];

    // Give a chance to alter destination with hook.
    $this->moduleHandler->alter('media_pdf_thumbnail_image_destination', $infos, $context);

    return $infos;
  }

  /**
   * Get generic thumbnail.
   *
   * @return int|null
   *   Return image id.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getGenericThumbnail(): ?int {
    $uri = $this->configFactory->get('media.settings')->get('icon_base_uri');
    if (!file_exists($uri . '/' . self::GENERIC_FILENAME)) {
      $path = $this->moduleHandler->getModule('media')->getPath();
      $genFilePath = $path . '/images/icons/' . self::GENERIC_FILENAME;
      $this->fileSystem->prepareDirectory($uri);
      copy($genFilePath, $uri . '/' . self::GENERIC_FILENAME);
    }
    $files = $this->entityTypeManager->getStorage('file')
      ->loadByProperties(['uri' => $uri . '/' . self::GENERIC_FILENAME]);
    return !empty($files) ? reset($files)->id() : NULL;
  }

  /**
   * Get pdf entity by pdf file uri.
   *
   * @param string $fileUri
   *   File uri.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   Return pdf entity.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getPdfEntityByPdfFileUri(string $fileUri): array {
    return $this->entityTypeManager->getStorage('pdf_image_entity')
      ->loadByProperties(['image_file_uri' => $fileUri]);
  }

  /**
   * Set entity type manager.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager|\Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   *
   * @return MediaPdfThumbnailImageManager
   *   Return self.
   */
  public function setEntityTypeManager(EntityTypeManager | EntityTypeManagerInterface $entityTypeManager): MediaPdfThumbnailImageManager {
    $this->entityTypeManager = $entityTypeManager;
    return $this;
  }

  /**
   * Set cache.
   *
   * @param \Drupal\Core\Cache\Cache|\Drupal\Core\Cache\CacheTagsInvalidatorInterface $cache
   *   Cache.
   *
   * @return MediaPdfThumbnailImageManager
   *   Return self.
   */
  public function setCache(Cache | CacheTagsInvalidatorInterface $cache): MediaPdfThumbnailImageManager {
    $this->cache = $cache;
    return $this;
  }

}
