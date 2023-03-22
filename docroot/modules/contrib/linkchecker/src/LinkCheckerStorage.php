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
    $query = $this->getQuery();
    $query->accessCheck()
      ->condition('urlhash', LinkCheckerLink::generateHash($link->getUrl()))
      ->condition('entity_id.target_id', $link->getParentEntity()->id())
      ->condition('entity_id.target_type', $link->getParentEntity()
        ->getEntityTypeId())
      ->condition('entity_field', $link->getParentEntityFieldName())
      ->condition('entity_langcode', $link->getParentEntityLangcode());
    return $query->execute();
  }

}
