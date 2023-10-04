<?php

namespace Drupal\layout_paragraphs\Access;

use Drupal\Core\Session\AccountInterface;
use Drupal\paragraphs\ParagraphsTypeInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\layout_paragraphs\LayoutParagraphsLayout;

/**
 * Class definition for LayoutParagraphsBuilderAccess.
 *
 * Checks access to layout paragraphs builder instance.
 */
class LayoutParagraphsBuilderAccess implements AccessInterface {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Class constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritDoc}
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account to check access for.
   * @param \Drupal\layout_paragraphs\LayoutParagraphsLayout $layout_paragraphs_layout
   *   The layout paragraphs layout object.
   * @param string $operation
   *   The operation being performed (i.e. 'create' or 'delete').
   * @param string $component_uuid
   *   The specific component being acted on.
   * @param \Drupal\paragraphs\ParagraphsTypeInterface $paragraph_type
   *   The paragraph type of a component being added.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result.
   */
  public function access(
    AccountInterface $account,
    LayoutParagraphsLayout $layout_paragraphs_layout,
    string $operation = NULL,
    string $component_uuid = NULL,
    ParagraphsTypeInterface $paragraph_type = NULL
  ) {

    // Check field access.
    $access = $layout_paragraphs_layout->getParagraphsReferenceField()->access('edit', $account, TRUE);

    // Check access to host entity.
    $entity = $layout_paragraphs_layout->getEntity();
    $lp_operation = $entity->isNew() ? 'create' : 'update';
    $access = $access->andIf($entity->access($lp_operation, $account, TRUE));

    // Check access to specific paragraph entity.
    if ($component_uuid) {
      $paragraph = $layout_paragraphs_layout
        ->getComponentByUuid($component_uuid)
        ->getEntity();
      $access = $access->andIf($paragraph->access($operation, $account, TRUE));
    }

    // Check access to paragraph type.
    if ($paragraph_type) {
      $access_handler = $this->entityTypeManager->getAccessControlHandler('paragraph');
      $access = $access->andIf($access_handler->createAccess($paragraph_type->id(), $account, [], TRUE));
    }

    return $access;
  }

}
