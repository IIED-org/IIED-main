<?php

/**
 * @file
 * API Example for PublishContent module.
 */

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;

/**
 * Hook callback when publishing.
 *
 * @param \Drupal\node\NodeInterface $node
 *   The node we are trying to publish.
 * @param \Drupal\Core\Session\AccountInterface $account
 *   The account that is trying to publish the node.
 *
 * @return \Drupal\Core\Access\AccessResult
 *   The access result.
 */
function hook_publishcontent_publish_access(NodeInterface $node, AccountInterface $account) {
  // Very simple example.
  if ($node->getType() === 'article') {
    if ($node->hasField('field_test') && $node->field_test->value === 'dummy') {
      return AccessResult::allowed();
    }
    return AccessResult::forbidden();
  }

  // Default neutral so other hooks can judge.
  return AccessResult::neutral();
}

/**
 * Hook callback when unpublishing.
 *
 * @param \Drupal\node\NodeInterface $node
 *   The node we are trying to unpublish.
 * @param \Drupal\Core\Session\AccountInterface $account
 *   The account that is trying to unpublish the node.
 *
 * @return \Drupal\Core\Access\AccessResult
 *   The access result.
 */
function hook_publishcontent_unpublish_access(NodeInterface $node, AccountInterface $account) {
  // Very simple example.
  if ($node->getType() === 'article') {
    if ($node->hasField('field_test') && $node->field_test->value === 'dummy') {
      return AccessResult::allowed();
    }
    return AccessResult::forbidden();
  }

  // Default neutral so other hooks can judge.
  return AccessResult::neutral();
}
