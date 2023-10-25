<?php

namespace Drupal\content_translation_redirect;

use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * Defines an interface for Content Translation Redirect entity storage.
 */
interface ContentTranslationRedirectStorageInterface extends ConfigEntityStorageInterface {

  /**
   * Loads a redirect for the specified entity, if any.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to find the redirect for.
   *
   * @return \Drupal\content_translation_redirect\ContentTranslationRedirectInterface|null
   *   A redirect entity object. NULL if no matching redirect is found.
   */
  public function loadByEntity(EntityInterface $entity): ?ContentTranslationRedirectInterface;

}
