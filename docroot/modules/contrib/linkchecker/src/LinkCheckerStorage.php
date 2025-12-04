<?php

namespace Drupal\linkchecker;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\linkchecker\Entity\LinkCheckerLink;

/**
 * The storage for link checker.
 */
class LinkCheckerStorage extends SqlContentEntityStorage {

  /**
   * Get existing IDs that matches the URL and entity.
   *
   * @param \Drupal\linkchecker\Entity\LinkCheckerLink $link
   *   The link.
   *
   * @return array
   *   An array of IDs, or an empty array if not found.
   */
  public function getExistingIdsFromLink(LinkCheckerLink $link) {
    $parent_entity = $link->getParentEntity();
    if ($parent_entity === NULL) {
      return [];
    }
    $query = $this
      ->getQuery()
      ->accessCheck(TRUE)
      ->condition('urlhash', LinkCheckerLink::generateHash($link->getUrl()))
      ->condition('parent_entity_type_id', $parent_entity->getEntityTypeId())
      ->condition('parent_entity_id', $parent_entity->id())
      ->condition('entity_field', $link->getParentEntityFieldName())
      ->condition('entity_langcode', $link->getParentEntityLangcode());
    return $query->execute();
  }

}
