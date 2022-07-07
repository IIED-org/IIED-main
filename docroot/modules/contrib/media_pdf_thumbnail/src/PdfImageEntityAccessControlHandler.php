<?php

namespace Drupal\media_pdf_thumbnail;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Pdf image entity entity.
 *
 * @see \Drupal\media_pdf_thumbnail\Entity\PdfImageEntity.
 */
class PdfImageEntityAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\media_pdf_thumbnail\Entity\PdfImageEntityInterface $entity */

    switch ($operation) {

      case 'view':

        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished pdf image entity entities');
        }


        return AccessResult::allowedIfHasPermission($account, 'view published pdf image entity entities');

      case 'update':

        return AccessResult::allowedIfHasPermission($account, 'edit pdf image entity entities');

      case 'delete':

        return AccessResult::allowedIfHasPermission($account, 'delete pdf image entity entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add pdf image entity entities');
  }


}
