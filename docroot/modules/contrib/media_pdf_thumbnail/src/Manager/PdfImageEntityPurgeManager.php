<?php

namespace Drupal\media_pdf_thumbnail\Manager;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Class PdfImageEntityPurgeManager.
 *
 * Manages the purge of PDF image entities.
 *
 * @package Drupal\media_pdf_thumbnail\Manager
 */
class PdfImageEntityPurgeManager {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The logger channel interface for Media PDF Thumbnail Queue.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected LoggerChannelInterface $logger;

  /**
   * PdfImageEntityQueueManager constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerChannel
   *   Queue factory.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, LoggerChannelFactoryInterface $loggerChannel) {
    $this->entityTypeManager = $entityTypeManager;
    $this->logger = $loggerChannel->get('Media PDF Thumbnail Purge');
  }

  /**
   * Finish callback.
   *
   * @param mixed $success
   *   Success.
   * @param mixed $results
   *   Results.
   * @param mixed $operations
   *   Operations.
   */
  protected static function finishedCallback(mixed $success, mixed $results, mixed $operations): void {
    if ($success) {
      $message = \Drupal::translation()->formatPlural(count($results),
        'One task processed.',
        '@count tasks processed.');
      \Drupal::messenger()->addMessage($message);
    }
    else {
      \Drupal::messenger()->addError(t('Finished with an error.'));
    }
  }

  /**
   * Purge PDF image entities.
   *
   * @throws \Exception
   */
  public function purgePdfImageEntities(): void {
    $operations = [];

    try {
      $ids = $this->entityTypeManager
        ->getStorage('pdf_image_entity')
        ->getQuery()
        ->accessCheck(FALSE)
        ->execute();

      foreach ($ids as $id) {
        $operations[] = [
          [static::class, 'execute'],
          [$id],
        ];
      }

      $batch = [
        'title' => $this->t('Delete PDF images'),
        'operations' => $operations,
        'init_message' => $this->t('Task creating process is starting.'),
        'progress_message' => $this->t('Processed @current out of @total. Estimated time: @estimate.'),
        'error_message' => $this->t('An error occurred during processing'),
        'finished' => [static::class, 'finishedCallback'],
      ];

      batch_set($batch);
    }
    catch (\Exception $e) {
      $this->logger->error($e->getMessage());
    }
  }

  /**
   * Purge callback.
   *
   * @param string|int $id
   *   ID.
   * @param array $context
   *   Context.
   *
   * @throws \Exception
   */
  public static function execute(string | int $id, array &$context): void {
    try {
      $entity = \Drupal::entityTypeManager()
        ->getStorage('pdf_image_entity')
        ->load($id);
      $context['message'] = 'Processing - ' . $entity->id();
      $context['results'][] = $entity->id();
      $entity->delete();
    }
    catch (\Exception $e) {
      $context['message'] = 'Error - ' . $e->getMessage();
      $context['results'][] = $e->getMessage();
    }
  }

}
