<?php

/**
 * Function to delete users using Batch API.
 *
 * @param array $users
 *   Array of users to delete.
 * @param  array &$context
 *   Sandbox context array.
 */
function delete_all_users_batch_delete($users = FALSE, &$context) {
  $db = \Drupal::database();
  $entity_type_manager = \Drupal::entityTypeManager();

  if ($users === FALSE) {
    if (!isset($context['sandbox']['progress'])) {
      $context['sandbox']['progress'] = 0;
      $context['sandbox']['current_uid'] = 1;
      $context['sandbox']['max'] = $db->select('users', 'u')
       ->fields('u')
       ->condition('uid', 1, '>')
       ->countQuery()
       ->execute()
       ->fetchField();

      // Collect results to process in the finished callback.
      $context['results']['user_count'] = $context['sandbox']['max'];
    }

    // Get a batch of 50 users from all users to delete.
    $users_to_delete = $db->select('users', 'u')
     ->fields('u', ['uid'])
     ->condition('uid', $context['sandbox']['current_uid'], '>')
     ->range(0, 50)
     ->execute()
     ->fetchCol();
  }
  else {
    if (!isset($context['sandbox']['progress'])) {
      $context['sandbox']['progress'] = 0;
      $context['sandbox']['max'] = count($users);

      // Collect results to process in the finished callback.
      $context['results']['user_count'] = $context['sandbox']['max'];
    }

    // Get a batch of 50 users from the list of users to delete.
    $users_to_delete = array_slice($users, $context['sandbox']['progress'], 50);
  }

  if ($context['sandbox']['max'] + 1 >0) {
    if (!empty($users_to_delete)) {
      foreach ($users_to_delete as $uid) {
        $account = $entity_type_manager->getStorage('user')->load($uid);

        // Delete user.
        $entity_type_manager->getStorage('user')->load($uid)->delete();
        $context['message'] = t('Deleting user with uid %uid', ['%uid' => $uid]);
        $context['sandbox']['current_uid'] = $uid;
        $context['sandbox']['progress']++;
      }
    }

    // Inform the batch engine that we are not finished,
    // and provide an estimation of the completion level we reached.
    if ($context['sandbox']['progress'] != $context['sandbox']['max']) {
      $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
    }
  }
}

/**
 * Finished callback for the user deletion batch.
 * @param int $success
 *   Equals 1 if batch is successfull else equals 0.
 * @param variable $results
 *   List of results parameter collected during batch processing.
 * @param variable $operations
 *   @todo  add description.
 */
function delete_all_users_batch_delete_finished($success, $results, $operations) {
  if ($success) {
    \Drupal::messenger()->addMessage(t('Deleted @user_count users.', ['@user_count' => $results['user_count']]));
  }
}
