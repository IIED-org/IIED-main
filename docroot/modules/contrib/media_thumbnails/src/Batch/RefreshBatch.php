<?php

namespace Drupal\media_thumbnails\Batch;

use Drupal\Core\Entity\EntityStorageException;
use Drupal;
use Exception;

/**
 * Class RefreshBatch.
 */
class RefreshBatch {

  /**
   * Creates the batch definition.
   *
   * @return array
   *   The batch definition.
   */
  public static function createBatch(): array {
    $ids = \Drupal::entityQuery('media')->execute();
    return [
      'operations' => [
        [
          '\Drupal\media_thumbnails\Batch\RefreshBatch::process',
          [array_values($ids)],
        ],
      ],
      'finished' => '\Drupal\media_thumbnails\Batch\RefreshBatch::finished',
      'title' => t('Refreshing media entity thumbnails'),
      'init_message' => t('Thumbnail refresh batch is starting.'),
      'progress_message' => t('Please wait...'),
      'error_message' => t('Thumbnail refresh batch has encountered an error.'),
    ];
  }

  /**
   * Returns the total number of media entities.
   *
   * @return int
   *   The number of media entities.
   */
  public static function count(): int {
    /** @var \Drupal\Core\Entity\EntityTypeManager $entity_type_manager */
    $entity_type_manager = Drupal::service('entity_type.manager');
    try {
      $storage = $entity_type_manager->getStorage('media');
    }
    catch (Exception $e) {
      return 0;
    }
    $query = $storage->getAggregateQuery();
    $query->count();
    return (int) $query->execute();
  }

  /**
   * Batch process callback.
   */
  public static function process($ids, &$context) {
    if (!isset($context['results']['processed'])) {
      $context['results']['processed'] = 0;
      $context['results']['updated'] = 0;
      $context['sandbox']['count'] = self::count();
    }
    /** @var \Drupal\Core\Entity\EntityTypeManager $entity_type_manager */
    $entity_type_manager = Drupal::service('entity_type.manager');
    $storage = $entity_type_manager->getStorage('media');
    $media = $storage->load($ids[$context['results']['processed']]);
    if ($media) {
      try {
        $media->save();
      }
      catch (EntityStorageException $e) {
      }
    }
    $context['results']['processed']++;
    $context['finished'] = $context['sandbox']['count'] ? $context['results']['processed'] / $context['sandbox']['count'] : 1;
  }

  /**
   * Batch finish callback.
   */
  public static function finished($success, $results, $operations) {
    $variables = ['@processed' => $results['processed']];
    if ($success) {
      Drupal::messenger()
        ->addMessage(t('Processed @processed media entities.', $variables));
    }
    else {
      Drupal::messenger()
        ->addWarning(t('An error occurred after processing @processed media entities.', $variables));
    }
  }

}
