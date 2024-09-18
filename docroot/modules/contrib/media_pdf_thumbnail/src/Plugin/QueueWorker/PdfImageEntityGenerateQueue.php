<?php

namespace Drupal\media_pdf_thumbnail\Plugin\QueueWorker;

use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\media_pdf_thumbnail\Manager\MediaPdfThumbnailImageManager;
use Drupal\media_pdf_thumbnail\Manager\PdfImageEntityQueueManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines 'pdf_image_entity_generate' queue worker. Generates image from PDF.
 *
 * @QueueWorker(
 *   id = "pdf_image_entity_generate",
 *   title = @Translation("PdfImageEntityGenerate"),
 *   cron = {"time" = 60}
 * )
 */
class PdfImageEntityGenerateQueue extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  const NAME = 'pdf_image_entity_generate';

  /**
   * Entity type manager.
   *
   * @var \Drupal\media_pdf_thumbnail\Manager\MediaPdfThumbnailImageManager
   */
  protected MediaPdfThumbnailImageManager $mediaPdfThumbnailImageManager;

  /**
   * Cache tags invalidator.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface
   */
  protected CacheTagsInvalidatorInterface $cacheTagsInvalidator;

  /**
   * PdfImageEntityQueueManager.
   *
   * @var \Drupal\media_pdf_thumbnail\Manager\PdfImageEntityQueueManager
   */
  protected PdfImageEntityQueueManager $pdfImageEntityQueueManager;

  /**
   * PdfImageEntityGenerateQueue constructor.
   *
   * @param array $configuration
   *   Configuration.
   * @param string $plugin_id
   *   Plugin id.
   * @param mixed $plugin_definition
   *   Plugin definition.
   * @param \Drupal\media_pdf_thumbnail\Manager\MediaPdfThumbnailImageManager $mediaPdfThumbnailImageManager
   *   MediaPdfThumbnailImageManager.
   * @param \Drupal\Core\Cache\CacheTagsInvalidatorInterface $cacheTagsInvalidator
   *   CacheTagsInvalidatorInterface.
   * @param \Drupal\media_pdf_thumbnail\Manager\PdfImageEntityQueueManager $pdfImageEntityQueueManager
   *   PdfImageEntityQueueManager.
   */
  public function __construct(array $configuration, string $plugin_id, mixed $plugin_definition, MediaPdfThumbnailImageManager $mediaPdfThumbnailImageManager, CacheTagsInvalidatorInterface $cacheTagsInvalidator, PdfImageEntityQueueManager $pdfImageEntityQueueManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->mediaPdfThumbnailImageManager = $mediaPdfThumbnailImageManager;
    $this->cacheTagsInvalidator = $cacheTagsInvalidator;
    $this->pdfImageEntityQueueManager = $pdfImageEntityQueueManager;
  }

  /**
   * {@inheritdoc}
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   Container.
   * @param array $configuration
   *   Configuration.
   * @param string $plugin_id
   *   Plugin id.
   * @param array $plugin_definition
   *   Plugin definition.
   *
   * @return \Drupal\media_pdf_thumbnail\Plugin\QueueWorker\PdfImageEntityGenerateQueue|static
   *   PdfImageEntityGenerateQueue.
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, mixed $plugin_definition): PdfImageEntityGenerateQueue {
    return new self($configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('media_pdf_thumbnail.image.manager'),
      $container->get('cache_tags.invalidator'),
      $container->get('media_pdf_thumbnail.pdf_image_entity.queue.manager'));
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    $this->mediaPdfThumbnailImageManager->createThumbnail($data->entity, $data->fieldName, $data->imageFormat, $data->page);
    $this->cacheTagsInvalidator->invalidateTags($data->entity->getCacheTagsToInvalidate());
  }

}
