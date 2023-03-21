<?php

namespace Drupal\linkchecker;

use Drupal\Core\Batch\BatchBuilder;
use Drupal\Core\Database\Connection;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Entity\FieldableEntityInterface;

/**
 * Helper service to handle extraction index.
 */
class LinkExtractorBatch {

  use DependencySerializationTrait;
  use MessengerTrait;
  use StringTranslationTrait;

  /**
   * The link extractor.
   *
   * @var \Drupal\linkchecker\LinkExtractorService
   */
  protected $extractor;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * LinkExtractorBatch constructor.
   */
  public function __construct(LinkExtractorService $extractor, EntityTypeManagerInterface $entityTypeManager, Connection $dbConnection) {
    $this->extractor = $extractor;
    $this->entityTypeManager = $entityTypeManager;
    $this->database = $dbConnection;
  }

  /**
   * Gets list of entity types that selected for extraction.
   *
   * @return array
   *   List of entity types with bundles.
   */
  public function getEntityTypesToProcess() {
    $fieldConfigs = $this->entityTypeManager
      ->getStorage('field_config')
      ->loadMultiple(NULL);
    $entityTypes = [];

    /** @var \Drupal\Core\Field\FieldConfigInterface $config */
    foreach ($fieldConfigs as $config) {
      $scan = $config->getThirdPartySetting('linkchecker', 'scan', FALSE);

      if ($scan) {
        $entityTypeId = $config->getTargetEntityTypeId();
        $bundle = $config->getTargetBundle();
        if (!isset($entityTypes[$entityTypeId . '-' . $bundle])) {
          $entityType = $this->entityTypeManager->getDefinition($entityTypeId);
          $entityTypes[$entityTypeId . '-' . $bundle] = [
            'entity_type' => $entityType,
            'bundle' => $bundle,
          ];
        }
      }
    }

    return $entityTypes;
  }

  /**
   * Process part of entities.
   *
   * @param int $numberOfItems
   *   Number of items to process.
   *
   * @return int
   *   Number of items that were processed.
   */
  public function processEntities($numberOfItems = 20) {
    $entityTypes = $this->getEntityTypesToProcess();
    $numberOfProcessedItems = 0;

    foreach ($entityTypes as $entityTypeData) {
      /** @var \Drupal\Core\Entity\EntityTypeInterface $entityType */
      $entityType = $entityTypeData['entity_type'];
      $bundle = $entityTypeData['bundle'];

      $query = $this->database->select($entityType->getBaseTable(), 'base');
      $query->fields('base', [$entityType->getKey('id')]);
      $query->leftJoin('linkchecker_index', 'i', 'i.entity_id = base.' . $entityType->getKey('id') . ' AND i.entity_type = :entity_type', [
        ':entity_type' => $entityType->id(),
      ]);
      $query->isNull('i.entity_id');
      if (!empty($bundle)) {
        $query->condition('base.' . $entityType->getKey('bundle'), $bundle);
      }
      $query->range(0, $numberOfItems - $numberOfProcessedItems);

      $ids = $query->execute()->fetchCol();
      $storage = $this->entityTypeManager->getStorage($entityType->id());
      foreach ($ids as $id) {
        $entity = $storage->load($id);
        if ($entity instanceof FieldableEntityInterface) {
          // Process the entity links.
          $links = $this->extractor->extractFromEntity($entity);
          $this->extractor->saveLinkMultiple($links);
          $this->extractor->updateEntityExtractIndex($entity);
        }

        $numberOfProcessedItems++;
      }

      if ($numberOfProcessedItems >= $numberOfItems) {
        break;
      }
    }

    return $numberOfProcessedItems;
  }

  /**
   * Gets total number of entities to process.
   *
   * @return int
   *   Total number of entities.
   */
  public function getTotalEntitiesToProcess() {
    $entityTypes = $this->getEntityTypesToProcess();
    $total = 0;

    foreach ($entityTypes as $entityTypeData) {
      /** @var \Drupal\Core\Entity\EntityTypeInterface $entityType */
      $entityType = $entityTypeData['entity_type'];
      $bundle = $entityTypeData['bundle'];

      // We don`t use $this->getQuery() cause we do not need left join
      // on linkchecker_index table.
      $query = $this->database->select($entityType->getBaseTable(), 'base');
      $query->fields('base', [$entityType->getKey('id')]);
      if (!empty($bundle)) {
        $query->condition('base.' . $entityType->getKey('bundle'), $bundle);
      }

      $query = $query->countQuery();
      $total += $query->execute()->fetchField();
    }

    return $total;
  }

  /**
   * Gets number of processed entities.
   *
   * @return int
   *   Number of entities.
   */
  public function getNumberOfProcessedEntities() {
    $query = $this->database->select('linkchecker_index', 'i');
    $query->fields('i');
    $query = $query->countQuery();
    $total = $query->execute()->fetchField();

    return $total;
  }

  /**
   * Sets a batch to extract links from entities.
   */
  public function batch() {
    // Clear index to reindex all entities.
    $this->database->truncate('linkchecker_index')->execute();

    $batch = new BatchBuilder();
    $batch->setTitle('Extract entities')
      ->addOperation([$this, 'batchProcessEntities'], [20])
      ->setProgressive()
      ->setFinishCallback([$this, 'batchFinished']);

    batch_set($batch->toArray());
  }

  /**
   * Process part of entities within a batch operation.
   *
   * @param int $numberOfItems
   *   Number of items to process.
   * @param mixed $context
   *   Context data from batch API.
   */
  public function batchProcessEntities($numberOfItems, &$context) {
    if (!isset($context['sandbox']['total'])) {
      $context['sandbox']['total'] = $this->getTotalEntitiesToProcess();
      $context['sandbox']['current'] = $this->getNumberOfProcessedEntities();
    }

    $context['sandbox']['current'] += $this->processEntities($numberOfItems);

    if (!empty($context['sandbox']['total'])) {
      $context['finished'] = $context['sandbox']['current'] / $context['sandbox']['total'];
    }
    else {
      $context['finished'] = 1;
    }
  }

  /**
   * Finished callback for batch.
   */
  public function batchFinished($success) {
    if ($success) {
      $this->messenger()
        ->addStatus($this->t('Links were successfully extracted.'));
    }
    else {
      $this->messenger()
        ->addError($this->t('Links were not extracted.'));
    }
  }

}
