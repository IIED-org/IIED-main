<?php

namespace Drupal\content_translation_redirect;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for Content Translation Redirect entities.
 */
class ContentTranslationRedirectAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResultInterface {
    if ($operation === 'delete') {
      /** @var \Drupal\content_translation_redirect\ContentTranslationRedirectInterface $entity */
      return AccessResult::allowedIf(!$entity->isLocked())->addCacheableDependency($entity)
        ->andIf(parent::checkAccess($entity, $operation, $account));
    }
    return parent::checkAccess($entity, $operation, $account);
  }

}
