<?php

namespace Drupal\linkchecker;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the linkchecker link entity type.
 *
 * @see \Drupal\linkchecker\Entity\LinkCheckerLink
 */
class LinkCheckerLinkAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\linkchecker\LinkCheckerLinkInterface $entity */
    if ($account->hasPermission('administer linkchecker')
      || $account->hasPermission('edit linkchecker link settings')) {

      return $this->checkParentEntityAccess($entity, $operation, $account);
    }

    // The permission is required.
    return AccessResult::forbidden()->cachePerPermissions();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkFieldAccess($operation, FieldDefinitionInterface $field_definition, AccountInterface $account, FieldItemListInterface $items = NULL) {
    // No user can change read only fields.
    if ($operation == 'edit') {
      switch ($field_definition->getName()) {
        case 'method':
        case 'status':
          return AccessResult::allowedIfHasPermissions($account, [
            'administer linkchecker',
            'edit linkchecker link settings',
          ], 'OR');

        default:
          return AccessResult::forbidden();
      }
    }

    // User not allowed to view URL field if he does not have access to parent
    // entity.
    if ($operation == 'view'
      && isset($items)
      && $field_definition->getName() == 'url') {
      return $this->checkParentEntityAccess($items->getEntity(), $operation, $account);
    }

    return parent::checkFieldAccess($operation, $field_definition, $account, $items);
  }

  /**
   * Helper function for access checking.
   */
  protected function checkParentEntityAccess(LinkCheckerLinkInterface $entity, $operation, AccountInterface $account) {
    $parentEntity = $entity->getParentEntity();

    // If parent not exists - forbidden.
    if (!isset($parentEntity)) {
      return AccessResult::forbidden()
        ->cachePerPermissions();
    }

    // If user does not have access to parent entity - forbidden.
    if (!$parentEntity->access($operation, $account)) {
      return AccessResult::forbidden()
        ->addCacheableDependency($parentEntity)
        ->cachePerPermissions();
    }

    // If field where link was stored not exists anymore - forbidden.
    if (!$parentEntity->hasField($entity->getParentEntityFieldName())) {
      return AccessResult::forbidden()
        ->addCacheableDependency($parentEntity)
        ->cachePerPermissions();
    }

    // If user does not have access to field where link is stored - forbidden.
    $parentEntityField = $parentEntity->get($entity->getParentEntityFieldName());
    if (!$parentEntityField->access($operation, $account)) {
      return AccessResult::forbidden()
        ->addCacheableDependency($parentEntity)
        ->cachePerPermissions();
    }

    // Allowed in all other cases.
    return AccessResult::allowed()
      ->addCacheableDependency($parentEntity)
      ->cachePerPermissions();
  }

}
