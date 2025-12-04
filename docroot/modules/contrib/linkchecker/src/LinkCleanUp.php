<?php

namespace Drupal\linkchecker;

use Drupal\Core\Batch\BatchBuilder;
use Drupal\Core\Database\Connection;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\Site\Settings;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Form to clean the links.
 */
class LinkCleanUp {

  use DependencySerializationTrait;
  use MessengerTrait;
  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The link extractor.
   *
   * @var \Drupal\linkchecker\LinkExtractorService
   */
  protected $extractor;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * LinkCleanUp constructor.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, LinkExtractorService $linkExtractorService, Connection $dbConnection) {
    $this->entityTypeManager = $entityTypeManager;
    $this->extractor = $linkExtractorService;
    $this->database = $dbConnection;
  }

  /**
   * Removes all extracted links.
   */
  public function removeAllBatch() {
    // Clear index to reindex all entities.
    $this->database->truncate('linkchecker_index');

    $batch = new BatchBuilder();
    $batch->setTitle('Remove links')
      ->addOperation([$this, 'batchProcessDelete'])
      ->setProgressive()
      ->setFinishCallback([$this, 'batchFinished']);

    batch_set($batch->toArray());
  }

  /**
   * Process link deletion.
   *
   * @param array $ids
   *   Array of ids to delete.
   */
  public function processDelete(array $ids) {
    $storage = $this->entityTypeManager->getStorage('linkcheckerlink');

    $links = $storage->loadMultiple($ids);

    $storage->delete($links);
  }

  /**
   * Process link deletion within batch operation.
   *
   * @param mixed $context
   *   Batch context.
   */
  public function batchProcessDelete(&$context) {
    $storage = $this->entityTypeManager->getStorage('linkcheckerlink');
    if (!isset($context['sandbox']['total'])) {
      $context['sandbox']['total'] = $storage->getQuery()
        ->accessCheck()
        ->count()
        ->execute();
    }

    $ids = $storage->getQuery()
      ->accessCheck()
      ->range(0, Settings::get('entity_update_batch_size', 50))
      ->execute();

    $this->processDelete($ids);

    // Count how many items are not proceed.
    $toProcess = $storage->getQuery()
      ->accessCheck()
      ->count()
      ->execute();
    $context['sandbox']['current'] = $context['sandbox']['total'] - $toProcess;
    if (!empty($toProcess)) {
      $context['finished'] = $context['sandbox']['current'] / $context['sandbox']['total'];
    }
    else {
      $context['finished'] = 1;
    }
  }

  /**
   * Removes non-existing links for given entity.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity.
   */
  public function cleanUpForEntity(FieldableEntityInterface $entity) {
    // Trying to reload the entity
    // If it was removed then we should remove all links related to the entity.
    $isEntityDeleted = !$this->entityTypeManager
      ->getStorage($entity->getEntityTypeId())
      ->load($entity->id());

    $extractedIds = [];
    /** @var \Drupal\linkchecker\LinkCheckerStorage $storage */
    $storage = $this->entityTypeManager->getStorage('linkcheckerlink');
    // If entity is not deleted, gather all links that exists in fields.
    if (!$isEntityDeleted) {
      $links = $this->extractor->extractFromEntity($entity);

      foreach ($links as $link) {
        $extractedIds = array_merge($storage->getExistingIdsFromLink($link), $extractedIds);
      }
    }
    else {
      // Remove entity from index.
      $this->database->delete('linkchecker_index')
        ->condition('entity_id', $entity->id())
        ->condition('entity_type', $entity->getEntityTypeId())
        ->execute();
    }

    // Get list of link IDs that should be deleted.
    $query = $storage->getQuery();
    $query->accessCheck();
    $query->condition('parent_entity_type_id', $entity->getEntityTypeId());
    $query->condition('parent_entity_id', $entity->id());
    if (!empty($extractedIds)) {
      $query->condition('lid', $extractedIds, 'NOT IN');
    }
    $ids = $query->execute();

    if (!empty($ids)) {
      // Delete the links.
      $linksToDelete = $storage->loadMultiple($ids);
      $storage->delete($linksToDelete);
    }
  }

  /**
   * Clean up queues data per specified linkchecker entity id.
   */
  public function cleanUpQueues($id): void {
    // Since both queues have different data structure - handle it using
    // separate queries.
    $queue_status_handle_items = $this->database
      ->select('queue', 'q')
      ->fields('q', ['item_id', 'data'])
      ->condition('name', 'linkchecker_status_handle')
      ->condition('data', '%' . serialize([$id => $id]) . '%', 'LIKE')
      ->execute()
      ->fetchAll(\PDO::FETCH_ASSOC);
    foreach ($queue_status_handle_items as $queue_status_handle_item) {
      // If there is just 1 id in the queue item -> delete it.
      // Otherwise - update it.
      $queue_status_handle_item_data = unserialize($queue_status_handle_item['data']);
      if (count($queue_status_handle_item_data['links']) > 1) {
        $index = array_search($id, $queue_status_handle_item_data['links'], TRUE);
        if ($index !== FALSE) {
          unset($queue_status_handle_item_data['links'][$index]);
          $this->database->update('queue')
            ->fields(['data' => serialize($queue_status_handle_item_data)])
            ->condition('item_id', $queue_status_handle_item['item_id'])
            ->condition('name', 'linkchecker_status_handle')
            ->execute();
        }
      }
      else {
        $this->deleteQueueItemById($queue_status_handle_item['item_id']);
      }
    }

    $queue_check_items = $this->database
      ->select('queue', 'q')
      ->fields('q', ['item_id', 'data'])
      ->condition('name', 'linkchecker_check')
      ->condition('data', '%"' . $id . '"%', 'LIKE')
      ->execute()
      ->fetchAll(\PDO::FETCH_ASSOC);
    // Theoretically there should be no case when 1 link is in several queue
    // items. But, loop through everything we found just to be sure.
    foreach ($queue_check_items as $queue_check_item) {
      $data = unserialize($queue_check_item['data']);
      // Find index of deleted linkchecker entity.
      if (count($data) > 1) {
        $index = array_search($id, $data, TRUE);
        if ($index !== FALSE) {
          unset($data[$index]);
          $this->database->update('queue')
            ->fields(['data' => serialize($data)])
            ->condition('item_id', $queue_check_item['item_id'])
            ->condition('name', 'linkchecker_check')
            ->execute();
        }
      }
      else {
        $this->deleteQueueItemById($queue_check_item['item_id']);
      }
    }
  }

  /**
   * Deletes queue item by id.
   */
  protected function deleteQueueItemById($item_id): void {
    $this->database
      ->delete('queue')
      ->condition('item_id', $item_id)
      ->execute();
  }

  /**
   * Finished callback for batch.
   */
  public function batchFinished($success) {
    if ($success) {
      $this->messenger()
        ->addStatus($this->t('Links were successfully checked.'));
    }
    else {
      $this->messenger()
        ->addError($this->t('Links were not checked.'));
    }
  }

}
