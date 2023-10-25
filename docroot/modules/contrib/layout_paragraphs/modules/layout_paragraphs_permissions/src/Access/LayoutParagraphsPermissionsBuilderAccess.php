<?php

namespace Drupal\layout_paragraphs_permissions\Access;

use Drupal\Core\Session\AccountInterface;
use Drupal\paragraphs\ParagraphsTypeInterface;
use Drupal\layout_paragraphs\LayoutParagraphsLayout;
use Drupal\layout_paragraphs\Access\LayoutParagraphsBuilderAccess;
use Drupal\Core\Access\AccessResult;

/**
 * Defines a Layout Paragraphs Permissions Builder Access class.
 */
class LayoutParagraphsPermissionsBuilderAccess extends LayoutParagraphsBuilderAccess {

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
      $reorder_access = $account->hasPermission('reorder layout paragraphs components')
        ? AccessResult::allowed()
        : AccessResult::forbidden();
      $access = $access->andIf($reorder_access);
    }
    if ($operation == 'duplicate') {
      $duplicate_access = $account->hasPermission('duplicate layout paragraphs components')
        ? AccessResult::allowed()
        : AccessResult::forbidden();
      $access = $access->andIf($duplicate_access);
    }
    return $access;
  }

}
