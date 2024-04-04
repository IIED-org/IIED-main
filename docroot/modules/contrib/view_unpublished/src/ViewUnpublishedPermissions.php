<?php

declare(strict_types=1);

namespace Drupal\view_unpublished;

use Drupal\Core\Entity\BundlePermissionHandlerTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\node\Entity\NodeType;

/**
 * Provides dynamic permissions for viewing unpublished nodes per type.
 */
final class ViewUnpublishedPermissions {

  use BundlePermissionHandlerTrait;
  use StringTranslationTrait;

  /**
   * Returns an array of view unpublished permissions per node type.
   *
   * @return array[]
   *   The node type view unpublished permissions.
   *
   * @see \Drupal\user\PermissionHandlerInterface::getPermissions()
   */
  public function permissions(): array {
    $perms = [];
    // Generate view unpublished permissions for all node types.
    $perms = $this->generatePermissions(NodeType::loadMultiple(), [$this, 'buildPermissions']);

    return $perms;
  }

  /**
   * Returns a list of view unpublished permissions for a given node type.
   *
   * @param \Drupal\node\Entity\NodeType $type
   *   The node type.
   *
   * @phpstan-return non-empty-array{non-falsy-string:array{title:\Drupal\Core\StringTranslation\TranslatableMarkup}}
   *
   * @return array[]
   *   An associative array of permission names and descriptions.
   */
  protected function buildPermissions(NodeType $type): array {
    $type_id = $type->id();
    $type_params = ['%type_name' => $type->label()];

    return [
      "view any unpublished $type_id content" => [
        'title' => $this->t('%type_name: View any unpublished content', $type_params),
      ],
    ];
  }

}
