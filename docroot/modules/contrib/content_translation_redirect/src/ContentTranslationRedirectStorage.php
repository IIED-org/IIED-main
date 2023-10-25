<?php

namespace Drupal\content_translation_redirect;

use Drupal\Core\Config\Entity\ConfigEntityStorage;
use Drupal\Core\Entity\EntityInterface;

/**
 * Defines the storage handler for Content Translation Redirect entities.
 */
class ContentTranslationRedirectStorage extends ConfigEntityStorage implements ContentTranslationRedirectStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function loadByEntity(EntityInterface $entity): ?ContentTranslationRedirectInterface {
    $ids[] = $entity->getEntityTypeId() . '__' . $entity->bundle();
    $ids[] = $entity->getEntityTypeId();
    $ids[] = ContentTranslationRedirectInterface::DEFAULT_ID;

    foreach ($ids as $id) {
      if ($redirect = $this->load($id)) {
        return $redirect;
      }
    }
    return NULL;
  }

}
