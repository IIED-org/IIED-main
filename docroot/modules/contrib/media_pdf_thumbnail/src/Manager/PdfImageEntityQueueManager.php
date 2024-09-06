<?php

namespace Drupal\media_pdf_thumbnail\Manager;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Queue\QueueInterface;
use Drupal\Core\Queue\QueueWorkerManagerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\media_pdf_thumbnail\Plugin\QueueWorker\PdfImageEntityGenerateQueue;

/**
 * Class PdfImageEntityQueueManager
 *
 * @package Drupal\media_pdf_thumbnail\Manager
 */
class PdfImageEntityQueueManager {

  const STATE = 'media_pdf_thumbnail.queue';

  /**
   * The queue factory
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected QueueFactory $queueFactory;

  /**
   * @var \Drupal\media_pdf_thumbnail\Manager\MediaPdfThumbnailImageManager
   */
  protected MediaPdfThumbnailImageManager $mediaPdfThumbnailImageManager;

  /**
   * @var \Drupal\Core\Queue\QueueInterface
   */
  protected QueueInterface $generateQueue;

  /**
   * @var \Drupal\Core\Queue\QueueWorkerManagerInterface
   */
  protected QueueWorkerManagerInterface $queueWorkerManager;

  /**
   * @var \Drupal\Core\State\StateInterface
   */
  protected StateInterface $state;

  /**
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected LoggerChannelInterface $logger;

  /**
   * PdfImageEntityQueueManager constructor.
   *
   * @param \Drupal\Core\Queue\QueueFactory $queueFactory
   * @param \Drupal\Core\Queue\QueueWorkerManagerInterface $queueWorkerManager
   * @param \Drupal\media_pdf_thumbnail\Manager\MediaPdfThumbnailImageManager $mediaPdfThumbnailImageManager
   * @param \Drupal\Core\State\StateInterface $state
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerChannel
   */
  public function __construct(QueueFactory $queueFactory, QueueWorkerManagerInterface $queueWorkerManager, MediaPdfThumbnailImageManager $mediaPdfThumbnailImageManager, StateInterface $state, LoggerChannelFactoryInterface $loggerChannel) {
    $this->queueFactory = $queueFactory;
    $this->mediaPdfThumbnailImageManager = $mediaPdfThumbnailImageManager;
    $this->generateQueue = $this->queueFactory->get(PdfImageEntityGenerateQueue::NAME);
    $this->queueWorkerManager = $queueWorkerManager;
    $this->state = $state;
    $this->logger = $loggerChannel->get('Media PDF Thumbnail Queue');
  }

  /**
   * Method description.
   */
  public function getThumbnail(EntityInterface $entity, $imageFieldName, $fileFieldName, $imageFormat, $page = 1) {

    $fileEntity = $this->mediaPdfThumbnailImageManager->getFileEntityFromField($entity, $fileFieldName);

    if (empty($fileEntity)) {
      return FALSE;
    }

    $pdfImage = $this->mediaPdfThumbnailImageManager->getImageIfExists($entity, $fileEntity, $fileFieldName, $imageFormat, $page);

    if (empty($pdfImage)) {

      $itemId = sprintf("%s:%s:%s:%s:%s:%s", $entity->id(), $entity->language()->getId(), $fileEntity->id(), $fileFieldName, $imageFormat, $page);

      if (!$this->isItemInQueue($itemId)) {
        // Add item to queue.
        $item = new \stdClass();
        $item->id = $itemId;
        $item->entity = $entity;
        $item->fileEntity = $fileEntity;
        $item->fieldName = $fileFieldName;
        $item->page = $page;
        $item->imageFormat = $imageFormat;
        $this->generateQueue->createItem($item);
      }

      // Return default image while waiting for queue run.
      $imageId = $entity->getEntityTypeId() !== 'media' && $entity->hasField($imageFieldName) ? $entity->get($imageFieldName)->target_id : $this->mediaPdfThumbnailImageManager->getGenericThumbnail();
      return [
        'image_id' => $imageId,
        'pdf_uri' => $fileEntity->getFileUri(),
      ];
    }

    return $pdfImage;
  }

  /**
   * @param $itemId
   *
   * @return array|mixed
   */
  protected function setItemInState($itemId) {
    $collection = $this->getCollection();
    $collection[$itemId] = $itemId;
    return $this->state->set(static::STATE, $collection);
  }

  /**
   * @param $itemId
   *
   * @return bool
   */
  protected function hasItemInState($itemId): bool {
    $collection = $this->getCollection();
    return in_array($itemId, $collection);
  }

  /**
   * @param $itemId
   *
   * @return mixed
   */
  public function deleteItemInState($itemId) {
    $collection = $this->getCollection();
    unset($collection[$itemId]);
    return $this->state->set(static::STATE, $collection);
  }

  /**
   * @return array|mixed
   */
  protected function getCollection() {
    $collection = $this->state->get(static::STATE);
    return empty($collection) ? [] : $collection;
  }

  /**
   * @return mixed
   */
  protected function clearItemsInState() {
    return $this->state->delete(static::STATE);
  }

  /**
   * @return mixed
   */
  public function clearQueue() {
    $this->generateQueue->deleteQueue();
    return $this->clearItemsInState();
  }

  /**
   * @return int
   */
  public function getNumberOfItems(): int {
    return $this->generateQueue->numberOfItems();
  }

  /**
   * @return void
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function runQueue() {

    /** @var \Drupal\Core\Queue\QueueWorkerInterface $queue_worker */
    $queueWorker = $this->getQueueWorker();

    while ($item = $this->generateQueue->claimItem()) {
      try {
        $queueWorker->processItem($item->data);
        $this->generateQueue->deleteItem($item);
      }
      catch (\Exception $e) {
        $this->logger->error($e->getMessage());
      }
    }
  }

  /**
   * @return \Drupal\Core\Queue\QueueInterface
   */
  public function getQueue(): QueueInterface {
    return $this->generateQueue;
  }

  /**
   * @return object
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function getQueueWorker(): object {
    /** @var \Drupal\Core\Queue\QueueWorkerInterface $queue_worker */
    return $this->queueWorkerManager->createInstance(PdfImageEntityGenerateQueue::NAME);
  }

  /**
   * @param $itemId
   *
   * @return bool
   */
  public function isItemInQueue($itemId): bool {
    $query = \Drupal::database()->select('queue', 'q')->fields('q', ['data'])->condition('name', PdfImageEntityGenerateQueue::NAME)->execute();
    while ($result = $query->fetchObject()) {
      if (!empty($result->data)) {
        $item = unserialize($result->data);
        if ($item->id == $itemId) {
          return TRUE;
        }
      }
    }
    return FALSE;
  }

}
