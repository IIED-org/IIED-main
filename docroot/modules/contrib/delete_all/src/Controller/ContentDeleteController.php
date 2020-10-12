<?php

namespace Drupal\delete_all\Controller;

use Drupal\delete_all\Controller\DeleteControllerBase;

/**
 * Returns responses for devel module routes.
 */
class ContentDeleteController extends DeleteControllerBase {
  /**
   * Get nids of the nodes to delete.
   *
   * @param array $roles
   *   Array of roles.
   *
   * @return array
   *   Array of nids of nodes to delete.
   */
  public function getContentToDelete($content_types = FALSE) {
    $nodes_to_delete = [];

    // Delete content by content type.
    if ($content_types !== FALSE) {
      $nodes_to_delete = [];
      foreach ($content_types as $content_type) {
        if ($content_type) {
          $nids = $this->connection->select('node', 'n')
                    ->fields('n', ['nid'])
                    ->condition('type', $content_type)
                    ->execute()
                    ->fetchCol('nid');

          $nodes_to_delete = array_merge($nodes_to_delete, $nids);
        }
      }
    }
    // Delete all content.
    else {
      $nodes_to_delete = FALSE;
    }

    return $nodes_to_delete;
  }

  /**
   *
   */
  public function getContentDeleteBatch($nodes_to_delete = FALSE) {
    // Define batch.
    $batch = [
      'operations' => [
        ['delete_all_content_batch_delete', [$nodes_to_delete]],
      ],
      'finished' => 'delete_all_content_batch_delete_finished',
      'title' => $this->t('Deleting Node'),
      'init_message' => $this->t('Node deletion is starting.'),
      'progress_message' => $this->t('Deleting Node...'),
      'error_message' => $this->t('Node deletion has encountered an error.'),
    ];

    return $batch;
  }
}
