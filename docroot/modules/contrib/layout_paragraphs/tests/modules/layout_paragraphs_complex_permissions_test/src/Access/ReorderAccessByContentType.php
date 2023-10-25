<?php

namespace Drupal\layout_paragraphs_complex_permissions_test\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\paragraphs\ParagraphsTypeInterface;
use Drupal\layout_paragraphs\LayoutParagraphsLayout;
use Drupal\layout_paragraphs\Access\LayoutParagraphsBuilderAccess;

/**
 * Checks access to layout paragraphs builder instance per content type.
 */
class ReorderAccessByContentType extends LayoutParagraphsBuilderAccess {

  /**
   * {@inheritDoc}
   */
  public function access(
    AccountInterface $account,
    LayoutParagraphsLayout $layout_paragraphs_layout,
    string $operation = NULL,
    string $component_uuid = NULL,
    ParagraphsTypeInterface $paragraph_type = NULL
  ) {
    $access = parent::access($account, $layout_paragraphs_layout, $operation, $component_uuid, $paragraph_type);
    if ($operation == 'reorder') {
      $entity = $layout_paragraphs_layout->getEntity();
      $type_id = $entity->bundle();
      $permission_any = 'reorder layout paragraphs components';
      $permission_by_content_type = "reorder layout paragraph components for $type_id content";
      $reorder_access = $account->hasPermission($permission_any) || $account->hasPermission($permission_by_content_type)
        ? AccessResult::allowed()
        : AccessResult::forbidden();
      $access = $access->andIf($reorder_access);
    }

    return $access;
  }

}
