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
  public function getPossibleIds(EntityInterface $entity): array {
    return [
      $entity->getEntityTypeId() . '__' . $entity->bundle(),
      $entity->getEntityTypeId(),
      ContentTranslationRedirectInterface::DEFAULT_ID,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function loadByEntity(EntityInterface $entity): ?ContentTranslationRedirectInterface {
    /** @var \Drupal\content_translation_redirect\ContentTranslationRedirectInterface[] $redirects */
    $redirects = $this->loadMultiple($this->getPossibleIds($entity));
    return reset($redirects) ?: NULL;
  }

}
