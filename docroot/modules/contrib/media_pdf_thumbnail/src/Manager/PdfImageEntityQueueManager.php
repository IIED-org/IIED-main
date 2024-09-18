<?php

namespace Drupal\media_pdf_thumbnail\Manager;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Queue\QueueInterface;
use Drupal\Core\Queue\QueueWorkerManagerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\media_pdf_thumbnail\Plugin\QueueWorker\PdfImageEntityGenerateQueue;

/**
 * Class PdfImageEntityQueueManager.
 *
 * Manages the queue for generating PDF image entities.
 *
 * @package Drupal\media_pdf_thumbnail\Manager
 */
class PdfImageEntityQueueManager {

  const STATE = 'media_pdf_thumbnail.queue';

  /**
   * The queue factory.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected QueueFactory $queueFactory;

  /**
   * Media PDF Thumbnail Image Manager.
   *
   * @var \Drupal\media_pdf_thumbnail\Manager\MediaPdfThumbnailImageManager
   */
  protected MediaPdfThumbnailImageManager $mediaPdfThumbnailImageManager;

  /**
   * The queue for generating PDF image entities.
   *
   * @var \Drupal\Core\Queue\QueueInterface
   */
  protected QueueInterface $generateQueue;

  /**
   * Queue Worker Manager for PDF image entities.
   *
   * @var \Drupal\Core\Queue\QueueWorkerManagerInterface
   */
  protected QueueWorkerManagerInterface $queueWorkerManager;

  /**
   * The state interface.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected StateInterface $state;

  /**
   * The logger channel interface for Media PDF Thumbnail Queue.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected LoggerChannelInterface $logger;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected Connection $database;

  /**
   * PdfImageEntityQueueManager constructor.
   *
   * @param \Drupal\Core\Queue\QueueFactory $queueFactory
   *   Queue factory.
   * @param \Drupal\Core\Queue\QueueWorkerManagerInterface $queueWorkerManager
   *   Queue worker manager.
   * @param \Drupal\media_pdf_thumbnail\Manager\MediaPdfThumbnailImageManager $mediaPdfThumbnailImageManager
   *   Media PDF Thumbnail Image Manager.
   * @param \Drupal\Core\State\StateInterface $state
   *   State interface.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerChannel
   *   Logger channel factory.
   * @param \Drupal\Core\Database\Connection $database
   *   Database connection.
   */
  public function __construct(QueueFactory $queueFactory, QueueWorkerManagerInterface $queueWorkerManager, MediaPdfThumbnailImageManager $mediaPdfThumbnailImageManager, StateInterface $state, LoggerChannelFactoryInterface $loggerChannel, Connection $database) {
    $this->queueFactory = $queueFactory;
    $this->mediaPdfThumbnailImageManager = $mediaPdfThumbnailImageManager;
    $this->generateQueue = $this->queueFactory->get(PdfImageEntityGenerateQueue::NAME);
    $this->queueWorkerManager = $queueWorkerManager;
    $this->state = $state;
    $this->logger = $loggerChannel->get('Media PDF Thumbnail Queue');
    $this->database = $database;
  }

  /**
   * Get thumbnail.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity.
   * @param string|int|null $imageFieldName
   *   Image field name.
   * @param string $fileFieldName
   *   File field name.
   * @param string $imageFormat
   *   Image format.
   * @param string|int $page
   *   Page.
   *
   * @return bool|array
   *   Return thumbnail.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getThumbnail(EntityInterface $entity, string | int | NULL $imageFieldName, string $fileFieldName, string $imageFormat, string | int $page = 1): bool | array {
    $fileEntity = $this->mediaPdfThumbnailImageManager->getFileEntityFromField($entity, $fileFieldName);

    if (empty($fileEntity)) {
      return FALSE;
    }

    $pdfImage = $this->mediaPdfThumbnailImageManager->getImageIfExists($entity, $fileEntity, $fileFieldName, $imageFormat, $page);

    if (empty($pdfImage)) {
      $itemId = sprintf("%s:%s:%s:%s:%s:%s", $entity->id(), $entity->language()
        ->getId(), $fileEntity->id(), $fileFieldName, $imageFormat, $page);

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
   * Set item in state.
   *
   * @param string|int $itemId
   *   Item id.
   *
   * @return array|mixed
   *   Return collection.
   */
  protected function setItemInState(string | int $itemId): mixed {
    $collection = $this->getCollection();
    $collection[$itemId] = $itemId;
    return $this->state->set(static::STATE, $collection);
  }

  /**
   * Has item in state.
   *
   * @param string|int $itemId
   *   Item id.
   *
   * @return bool
   *   Return true if item is in state.
   */
  protected function hasItemInState(string | int $itemId): bool {
    $collection = $this->getCollection();
    return in_array($itemId, $collection);
  }

  /**
   * Delete item in state.
   *
   * @param string|int $itemId
   *   Item id.
   *
   * @return mixed
   *   Return collection.
   */
  public function deleteItemInState(string | int $itemId): mixed {
    $collection = $this->getCollection();
    unset($collection[$itemId]);
    return $this->state->set(static::STATE, $collection);
  }

  /**
   * Get deletion items in state.
   *
   * @return array|mixed
   *   Return collection.
   */
  protected function getCollection(): mixed {
    $collection = $this->state->get(static::STATE);
    return empty($collection) ? [] : $collection;
  }

  /**
   * Clear items in state.
   *
   * @return mixed
   *   Return collection.
   */
  protected function clearItemsInState(): mixed {
    return $this->state->delete(static::STATE);
  }

  /**
   * Clear queue.
   *
   * @return mixed
   *   Return collection.
   */
  public function clearQueue(): mixed {
    $this->generateQueue->deleteQueue();
    return $this->clearItemsInState();
  }

  /**
   * Get number of items in queue.
   *
   * @return int
   *   Return number of items.
   */
  public function getNumberOfItems(): int {
    return $this->generateQueue->numberOfItems();
  }

  /**
   * Run queue.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function runQueue(): void {
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
   * Get queue.
   *
   * @return \Drupal\Core\Queue\QueueInterface
   *   Return queue.
   */
  public function getQueue(): QueueInterface {
    return $this->generateQueue;
  }

  /**
   * Get queue worker.
   *
   * @return object
   *   Return queue worker.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function getQueueWorker(): object {
    /** @var \Drupal\Core\Queue\QueueWorkerInterface $queue_worker */
    return $this->queueWorkerManager->createInstance(PdfImageEntityGenerateQueue::NAME);
  }

  /**
   * Is item in queue.
   *
   * @param string|int $itemId
   *   Item id.
   *
   * @return bool
   *   Return true if item is in queue.
   */
  public function isItemInQueue(string | int $itemId): bool {
    $query = $this->database
      ->select('queue', 'q')
      ->fields('q', ['data'])
      ->condition('name', PdfImageEntityGenerateQueue::NAME)
      ->execute();
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
