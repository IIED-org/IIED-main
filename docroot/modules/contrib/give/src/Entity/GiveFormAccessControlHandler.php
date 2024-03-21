<?php

namespace Drupal\give\Entity;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the give form entity type.
 *
 * @see \Drupal\give\Entity\GiveForm.
 */
class GiveFormAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    if ($operation == 'delete' || $operation == 'update') {
      $admin = $account->hasPermission('administer give');
      $manage = $account->hasPermission('manage give forms');
      if ($operation == 'delete') {
        // Only delete if there are no donations using the form
        $ids = \Drupal::entityQuery('give_donation')
          ->accessCheck(TRUE)
          ->condition('give_form', $entity->id())
          ->execute();
        if ($ids) {
          return AccessResult::forbidden();
        }
      }
      return AccessResult::allowedIf($admin or $manage);
    }
    else {
      $result = AccessResult::allowedIfHasPermission($account, 'access give forms');
    }
    return $result->cachePerPermissions();
  }

}
