<?php

namespace Drupal\linkchecker;

use Drupal\Core\Batch\BatchBuilder;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
/**
 * Class LinkCleanUp.
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
   * Proccess link deletion within batch operation.
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
      ->range(0, 10)
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
    $query->condition('entity_id.target_id', $entity->id());
    $query->condition('entity_id.target_type', $entity->getEntityTypeId());
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
