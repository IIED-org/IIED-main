<?php

namespace Drupal\linkchecker;

use Drupal\Component\Utility\Environment;
use Drupal\Core\Batch\BatchBuilder;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Queue\QueueWorkerManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Helper service to handle links checking.
 */
class LinkCheckerBatch {

  use DependencySerializationTrait;
  use MessengerTrait;
  use StringTranslationTrait;

  /**
   * The link checker.
   *
   * @var \Drupal\linkchecker\LinkCheckerService
   */
  protected $checker;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The lock.
   *
   * @var \Drupal\Core\Lock\LockBackendInterface
   */
  protected $lock;

  /**
   * The queue factory.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queueFactory;

  /**
   * The queue worker manager.
   *
   * @var \Drupal\Core\Queue\QueueWorkerManagerInterface
   */
  protected $queueWorkerManager;

  /**
   * LinkExtractorBatch constructor.
   */
  public function __construct(LinkCheckerService $checker, EntityTypeManagerInterface $entityTypeManager, LockBackendInterface $lock, QueueFactory $queueFactory, QueueWorkerManagerInterface $queueWorkerManager) {
    $this->checker = $checker;
    $this->entityTypeManager = $entityTypeManager;
    $this->lock = $lock;
    $this->queueFactory = $queueFactory;
    $this->queueWorkerManager = $queueWorkerManager;
  }

  /**
   * Process next item in queue.
   */
  public function processQueueItem() {
    $item = $this->queueFactory
      ->get('linkchecker_check')
      ->claimItem();

    if (!empty($item)) {
      $this->queueWorkerManager
        ->createInstance('linkchecker_check')
        ->processItem($item->data);
    }
  }

  /**
   * Sets a batch to extract links from entities.
   */
  public function batch() {
    // Get max_execution_time from configuration, override 0 with 240 seconds.
    $maxExecutionTime = ini_get('max_execution_time') == 0 ? 240 : ini_get('max_execution_time');
    // Make sure we have enough time to validate all of the links.
    Environment::setTimeLimit($maxExecutionTime);
    // Make sure this is the only process trying to run this function.
    if (!$this->lock->acquire('linkchecker_check', $maxExecutionTime) && FALSE) {
      $this->messenger()
        ->addWarning($this->t('Attempted to re-run link checks while they are already running.'));
      return;
    }

    $batch = new BatchBuilder();
    $batch->setTitle('Check links')
      ->addOperation([$this, 'batchProcessQueue'])
      ->setProgressive()
      ->setFinishCallback([$this, 'batchFinished']);

    batch_set($batch->toArray());
  }

  /**
   * Gets total number of links to process.
   *
   * @return int
   *   Total number of links.
   */
  public function getTotalLinksToProcess() {
    return $this->checker->queueLinks(TRUE);
  }

  /**
   * Process linkchecker_check queue.
   *
   * @param mixed $context
   *   Context data from batch API.
   */
  public function batchProcessQueue(&$context) {
    if (!isset($context['sandbox']['total'])) {
      $context['sandbox']['total'] = $this->getTotalLinksToProcess();
      $context['sandbox']['current'] = 0;
    }

    $this->processQueueItem();
    $context['sandbox']['current']++;

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
        ->addStatus($this->t('Links were successfully checked.'));
    }
    else {
      $this->messenger()
        ->addError($this->t('Links were not checked.'));
    }
  }

}
