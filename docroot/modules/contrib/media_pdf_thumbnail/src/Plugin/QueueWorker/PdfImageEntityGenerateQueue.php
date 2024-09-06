<?php

namespace Drupal\media_pdf_thumbnail\Plugin\QueueWorker;

use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\media_pdf_thumbnail\Manager\MediaPdfThumbnailImageManager;
use Drupal\media_pdf_thumbnail\Manager\PdfImageEntityQueueManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines 'pdf_image_entity_generate' queue worker.
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
   * @var \Drupal\media_pdf_thumbnail\Manager\MediaPdfThumbnailImageManager
   */
  protected MediaPdfThumbnailImageManager $mediaPdfThumbnailImageManager;

  /**
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface
   */
  protected CacheTagsInvalidatorInterface $cacheTagsInvalidator;

  /**
   * @var \Drupal\media_pdf_thumbnail\Manager\PdfImageEntityQueueManager
   */
  protected PdfImageEntityQueueManager $pdfImageEntityQueueManager;

  /**
   * PdfImageEntityGenerateQueue constructor.
   *
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   * @param \Drupal\media_pdf_thumbnail\Manager\MediaPdfThumbnailImageManager $mediaPdfThumbnailImageManager
   * @param \Drupal\Core\Cache\CacheTagsInvalidatorInterface $cacheTagsInvalidator
   * @param \Drupal\media_pdf_thumbnail\Manager\PdfImageEntityQueueManager $pdfImageEntityQueueManager
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MediaPdfThumbnailImageManager $mediaPdfThumbnailImageManager, CacheTagsInvalidatorInterface $cacheTagsInvalidator, PdfImageEntityQueueManager $pdfImageEntityQueueManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->mediaPdfThumbnailImageManager = $mediaPdfThumbnailImageManager;
    $this->cacheTagsInvalidator = $cacheTagsInvalidator;
    $this->pdfImageEntityQueueManager = $pdfImageEntityQueueManager;
  }

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   *
   * @return \Drupal\media_pdf_thumbnail\Plugin\QueueWorker\PdfImageEntityGenerateQueue|static
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static ($configuration,
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
