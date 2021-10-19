<?php

namespace Drupal\Core\Entity;

/**
 * Defines a common interface for all content entity objects.
 *
 * Content entities use fields for all their entity properties and can be
 * translatable and revisionable. Translations and revisions can be
 * enabled per entity type through annotation and using entity type hooks.
 *
 * It's best practice to always implement ContentEntityInterface for
 * content-like entities that should be stored in some database, and
 * enable/disable revisions and translations as desired.
 *
 * When implementing this interface which extends Traversable, make sure to list
 * IteratorAggregate or Iterator before this interface in the implements clause.
 *
 * @see \Drupal\Core\Entity\ContentEntityBase
 * @see \Drupal\Core\Entity\EntityTypeInterface
 *
 * @ingroup entity_api
 */
interface ContentEntityInterface extends \Traversable, FieldableEntityInterface, TranslatableRevisionableInterface, SynchronizableInterface {

  /**
   * Marks the content entity as language aware.
   *
   * @param bool $flag
   *   TRUE if this entity is being consulted as language aware.
   */
  public function setLanguageAware($flag = TRUE);

  /**
   * Checks if the entity is language aware or unaware.
   *
   * Unaware means that the entity inherits the language from the current
   * user language, even if that means that the current language is different
   * from the entity's default language. Language aware entities will default
   * to the default language of the entity.
   *
   * @return bool
   *   TRUE if language aware-..
   */
  public function isLanguageAware();

}
