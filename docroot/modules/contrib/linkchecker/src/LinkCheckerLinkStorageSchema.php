<?php

namespace Drupal\linkchecker;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema;

/**
 * Defines storage schema handler for the linkcheckerlink entity type.
 */
class LinkCheckerLinkStorageSchema extends SqlContentEntityStorageSchema {

  /**
   * {@inheritdoc}
   */
  protected function getEntitySchema(ContentEntityTypeInterface $entity_type, $reset = FALSE) {
    $schema = parent::getEntitySchema($entity_type, $reset);

    $schema['linkchecker_link']['indexes'] += [
      'method' => ['method'],
      'code' => ['code'],
      'fail_count' => ['fail_count'],
      'last_check' => ['last_check'],
    ];

    return $schema;
  }

}
