<?php

namespace Drupal\content_translation_redirect;

use Drupal\Core\Entity\EntityTypeInterface;

/**
 * An interface for common functionality for Content Translation Redirect.
 */
interface ContentTranslationRedirectManagerInterface {

  /**
   * Checks whether an entity type is supported.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   *
   * @return bool
   *   TRUE if an entity type is supported, FALSE otherwise.
   */
  public function isEntityTypeSupported(EntityTypeInterface $entity_type): bool;

  /**
   * Returns a list of supported entity types.
   *
   * @return \Drupal\Core\Entity\EntityTypeInterface[]
   *   A list of supported entity types.
   */
  public function getSupportedEntityTypes(): array;

  /**
   * Resets the cache for the specified redirect.
   *
   * @param \Drupal\content_translation_redirect\ContentTranslationRedirectInterface $redirect
   *   The redirect to reset cache for.
   */
  public function resetCache(ContentTranslationRedirectInterface $redirect): void;

}
