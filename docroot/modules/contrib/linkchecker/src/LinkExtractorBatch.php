<?php

namespace Drupal\linkchecker;

use Drupal\Core\Batch\BatchBuilder;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\Site\Settings;
use Drupal\Core\StringTranslation\StringTranslationTrait;

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
   * The Linkchecker settings.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $linkcheckerSetting;

  /**
   * LinkExtractorBatch constructor.
   */
  public function __construct(LinkExtractorService $extractor, EntityTypeManagerInterface $entityTypeManager, Connection $dbConnection, ConfigFactoryInterface $configFactory) {
    $this->extractor = $extractor;
    $this->entityTypeManager = $entityTypeManager;
    $this->database = $dbConnection;
    $this->linkcheckerSetting = $configFactory->get('linkchecker.settings');
  }

  /**
   * Gets list of entity types that selected for extraction.
   *
   * @return array
   *   List of entity types with bundles.
   */
  public function getEntityTypesToProcess() {
    $fieldConfigs = $this
      ->entityTypeManager
      ->getStorage('field_config')
      ->loadMultiple();
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
  public function processEntities($numberOfItems = NULL) {
    $numberOfProcessedItems = 0;
    // This function is used in batch to extract all links on demand and it's
    // also called on every cron run (see linkchecker_cron()). Because it uses
    // SQL LEFT JOIN, it's quite expensive. So, first check if there is anything
    // to process. If yes, then use query with LEFT JOIN to retrieve entities to
    // be processed.
    if ($this->getTotalEntitiesToProcess() <= $this->getNumberOfProcessedEntities()) {
      return $numberOfProcessedItems;
    }

    if (!$numberOfItems) {
      $numberOfItems = Settings::get('entity_update_batch_size', 50);
    }
    $entityTypes = $this->getEntityTypesToProcess();

    foreach ($entityTypes as $entityTypeData) {
      /** @var \Drupal\Core\Entity\EntityTypeInterface $entityType */
      $entityType = $entityTypeData['entity_type'];
      $bundle = $entityTypeData['bundle'];

      $query = $this->getQuery($entityType, $bundle);
      $query->leftJoin('linkchecker_index', 'i', 'i.entity_id = base.' . $entityType->getKey('id') . ' AND i.entity_type = :entity_type', [
        ':entity_type' => $entityType->id(),
      ]);
      $query->isNull('i.entity_id');
      $query->range(0, $numberOfItems - $numberOfProcessedItems);

      $ids = $query->execute()->fetchCol();
      $entities = $this
        ->entityTypeManager
        ->getStorage($entityType->id())
        ->loadMultiple($ids);
      foreach ($entities as $entity) {
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
      $query = $this->getQuery($entityType, $bundle);
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
      ->addOperation([$this, 'batchProcessEntities'], [Settings::get('entity_update_batch_size', 50)])
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

  /**
   * Get link extraction query.
   *
   * If unpublished content should be skipped, data table is used, base table
   * otherwise.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entityType
   *   The entity type.
   * @param string $bundle
   *   The bundle.
   *
   * @return \Drupal\Core\Database\Query\SelectInterface
   *   The select query.
   */
  protected function getQuery(EntityTypeInterface $entityType, $bundle) {
    // Use data table only if exists and unpublished content should be skipped.
    if ($entityType->hasKey('status')
      && $entityType->getDataTable()
      && $this->linkcheckerSetting->get('search_published_contents_only')
    ) {
      $query = $this
        ->database
        ->select($entityType->getDataTable(), 'base')
        ->condition('base.' . $entityType->getKey('status'), 1)
        ->distinct();
    }
    // Otherwise, use base table, it has all necessary information.
    else {
      $query = $this->database->select($entityType->getBaseTable(), 'base');
    }

    $query->fields('base', [$entityType->getKey('id')]);
    if (!empty($bundle)) {
      $query->condition('base.' . $entityType->getKey('bundle'), $bundle);
    }

    $query->orderBy('base.' . $entityType->getKey('id'));
    return $query;
  }

}
