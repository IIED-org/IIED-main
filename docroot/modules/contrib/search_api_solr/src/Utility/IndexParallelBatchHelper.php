<?php

namespace Drupal\search_api_solr\Utility;

use Drupal\Core\Batch\BatchStorageInterface;
<<<<<<< HEAD
use Drupal\search_api\IndexInterface;
use Drupal\search_api\SearchApiException;
use Drupal\search_api\Utility\IndexingBatchHelper;
=======
use Drupal\Core\Utility\Error;
use Drupal\search_api\IndexBatchHelper;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\SearchApiException;
>>>>>>> parent of 3b9f439507 (remove gitignored directories)
use Drupal\search_api_solr\Plugin\search_api\tracker\IndexParallel;

/**
 * Provides helper methods for indexing items using Drupal's Batch API.
 */
<<<<<<< HEAD
class IndexParallelBatchHelper extends IndexingBatchHelper {

  /**
   * IDs of batches created for the current indexing run.
   *
   * @var array<int, string>
   */
  protected array $batchIds = [];

  /**
   * The batch storage service.
   *
   * @var \Drupal\Core\Batch\BatchStorageInterface|null
   */
  protected ?BatchStorageInterface $batchStorage = NULL;

  /**
   * Sets the batch storage service.
   *
   * @param \Drupal\Core\Batch\BatchStorageInterface $batchStorage
   *   The batch storage service.
   */
  public function setBatchStorage(BatchStorageInterface $batchStorage): void {
    $this->batchStorage = $batchStorage;
  }
=======
class IndexParallelBatchHelper extends IndexBatchHelper {
>>>>>>> parent of 3b9f439507 (remove gitignored directories)

  /**
   * Creates an indexing batch for a given search index.
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   The search index for which items should be indexed.
   * @param int|null $batch_size
   *   (optional) Number of items to index per batch. Defaults to the cron limit
   *   set for the index.
   * @param int $limit
   *   (optional) Maximum number of items to index. Defaults to indexing all
   *   remaining items.
<<<<<<< HEAD
   * @param int $time_limit
   *   (optional) Maximum number of seconds a batch run may take.
=======
   *
   * @return int[]
   *   The batch IDs.
>>>>>>> parent of 3b9f439507 (remove gitignored directories)
   *
   * @throws \Drupal\search_api\SearchApiException
   *   Thrown if the batch could not be created.
   */
<<<<<<< HEAD
  public function createBatch(
    IndexInterface $index,
    ?int $batch_size = NULL,
    int $limit = -1,
    int $time_limit = -1,
  ): void {
    // Make sure that the indexing lock is available.
    if (!$this->lockBackend->lockMayBeAvailable($index->getLockId())) {
      throw new SearchApiException('Items are being indexed in a different process.');
=======
  public static function create(IndexInterface $index, $batch_size = NULL, $limit = -1): array {
    // Make sure that the indexing lock is available.
    if (!\Drupal::lock()->lockMayBeAvailable($index->getLockId())) {
      throw new SearchApiException("Items are being indexed in a different process.");
>>>>>>> parent of 3b9f439507 (remove gitignored directories)
    }

    $ids = [];

    // Check if indexing items is allowed.
<<<<<<< HEAD
    if (($batch_size ?? 0) > 0 && $index->status() && !$index->isReadOnly()) {
      assert($this->batchStorage instanceof BatchStorageInterface);
=======
    if (($batch_size  ?? 0) > 0 && $index->status() && !$index->isReadOnly()) {
      /** @var BatchStorageInterface $batchStorage */
      $batchStorage = \Drupal::service('batch.storage');
>>>>>>> parent of 3b9f439507 (remove gitignored directories)

      for ($thread = 1; $thread <= $limit; $thread++) {
        // Define the search index batch definition.
        $batch_definition = [
          'operations' => [
            [
<<<<<<< HEAD
              [$this, 'process'],
              [
                $index,
                $batch_size,
                $thread,
                -1,
              ],
            ],
          ],
          'finished' => [$this, 'finish'],
          'progress_message' => $this->t('Completed about @percentage% of the indexing operation (@current of @total).'),
=======
              [__CLASS__, 'process'],
              [
                $index,
                $batch_size,
                $thread
              ]
            ],
          ],
          'finished' => [__CLASS__, 'finish'],
          'progress_message' => static::t('Completed about @percentage% of the indexing operation (@current of @total).'),
>>>>>>> parent of 3b9f439507 (remove gitignored directories)
        ];

        batch_set($batch_definition);

        $batch = &batch_get();

        if (isset($batch)) {
          $process_info = [
            'current_set' => 0,
          ];
          $batch += $process_info;

<<<<<<< HEAD
          if (!method_exists($this->batchStorage, 'getId')) {
            throw new SearchApiException('The batch storage service does not support ID generation.');
          }
          $ids[] = $batch['id'] = $this->batchStorage->getId();
=======
          // The batch is now completely built. Allow other modules to make changes
          // to the batch so that it is easier to reuse batch processes in other
          // environments.
          \Drupal::moduleHandler()->alter('batch', $batch);

          $ids[] = $batch['id'] = $batchStorage->getId();
>>>>>>> parent of 3b9f439507 (remove gitignored directories)

          $batch['progressive'] = TRUE;

          // Move operations to a job queue. Non-progressive batches will use a
          // memory-based queue.
          foreach ($batch['sets'] as $key => $batch_set) {
            _batch_populate_queue($batch, $key);
          }

<<<<<<< HEAD
          $this->batchStorage->create($batch);
=======
          $batchStorage->create($batch);
>>>>>>> parent of 3b9f439507 (remove gitignored directories)
          $batch = [];
        }
      }
    }
    else {
      $index_label = $index->label();
      throw new SearchApiException("Failed to create a batch with batch size '$batch_size' and threads '$limit' for index '$index_label'.");
    }

<<<<<<< HEAD
    $this->batchIds = array_reverse($ids);
  }

  /**
   * Get batch IDs.
   *
   * @return int[]
   *   The batch IDs.
   */
  public function getBatchIds(): array {
    return $this->batchIds;
=======
    return array_reverse($ids);
>>>>>>> parent of 3b9f439507 (remove gitignored directories)
  }

  /**
   * Processes an index batch operation.
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   The index on which items should be indexed.
   * @param int $batch_size
   *   The maximum number of items to index per batch pass.
   * @param int $limit
   *   The maximum number of items to index in total, or -1 to index all items.
<<<<<<< HEAD
   * @param int $time_limit
   *   (optional) The maximum number of seconds allowed to run indexing, or -1
   *   to not have any limit. Defaults to -1 (no limit).
=======
>>>>>>> parent of 3b9f439507 (remove gitignored directories)
   * @param array|\ArrayAccess $context
   *   The context of the current batch, as defined in the @link batch Batch
   *   operations @endlink documentation.
   */
<<<<<<< HEAD
  public function process(
    IndexInterface $index,
    int $batch_size,
    int $limit,
    int $time_limit,
    array|\ArrayAccess &$context,
  ): void {
=======
  public static function process(IndexInterface $index, $batch_size, $limit, &$context): void {
>>>>>>> parent of 3b9f439507 (remove gitignored directories)
    // Check if the sandbox should be initialized.
    if (!isset($context['sandbox']['limit'])) {
      $context['sandbox']['limit'] = -1;
      $context['sandbox']['thread'] = $limit;
      $context['sandbox']['batch_size'] = $batch_size;
    }

    if ($index->hasValidTracker() && !$index->isReadOnly() && $index->getTrackerId() === 'index_parallel') {
      /** @var \Drupal\search_api_solr\Plugin\search_api\tracker\IndexParallel $tracker */
      $tracker = $index->getTrackerInstance();
      $tracker->setThread($context['sandbox']['thread']);
      if ($context['sandbox']['thread'] > 1) {
        $tracker->setOffset($context['sandbox']['batch_size'] * IndexParallel::SAFETY_DISTANCE_FACTOR * ($context['sandbox']['thread'] - 1));
      }
    }

<<<<<<< HEAD
    parent::process($index, $batch_size, -1, $time_limit, $context);
=======
    IndexBatchHelper::process($index, $batch_size, -1, $context);
>>>>>>> parent of 3b9f439507 (remove gitignored directories)
  }

  /**
   * Finishes an index batch.
   */
<<<<<<< HEAD
  public function finish($success, $results, $operations): void {
=======
  public static function finish($success, $results, $operations) {
>>>>>>> parent of 3b9f439507 (remove gitignored directories)
    // Check if the batch job was successful.
    if ($success) {
      // Display the number of items indexed.
      if (!empty($results['indexed'])) {
        // Build the indexed message.
<<<<<<< HEAD
        $indexed_message = $this->formatPlural($results['indexed'], 'Thread successfully indexed 1 item.', 'Thread successfully indexed @count items.');
        // Notify user about indexed items.
        $this->messenger->addStatus($indexed_message);
      }
      else {
        // Notify user about failure to index items.
        $this->messenger->addError($this->t("Couldn't index items. Check the logs for details."));
=======
        $indexed_message = static::formatPlural($results['indexed'], 'Thread successfully indexed 1 item.', 'Thread successfully indexed @count items.');
        // Notify user about indexed items.
        \Drupal::messenger()->addStatus($indexed_message);
      }
      else {
        // Notify user about failure to index items.
        \Drupal::messenger()->addError(static::t("Couldn't index items. Check the logs for details."));
>>>>>>> parent of 3b9f439507 (remove gitignored directories)
      }
    }
    else {
      // Notify user about batch job failure.
<<<<<<< HEAD
      $this->messenger->addError($this->t('An error occurred while trying to index items. Check the logs for details.'));
=======
      \Drupal::messenger()->addError(static::t('An error occurred while trying to index items. Check the logs for details.'));
>>>>>>> parent of 3b9f439507 (remove gitignored directories)
    }
  }

}
