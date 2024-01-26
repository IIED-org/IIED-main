<?php

namespace Drupal\content_translation_redirect;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Url;

/**
 * Provides an interface defining a Content Translation Redirect entity.
 */
interface ContentTranslationRedirectInterface extends ConfigEntityInterface {

  /**
   * The ID of the default content translation redirect.
   */
  public const DEFAULT_ID = 'default';

  /**
   * Act on translatable entities.
   */
  public const MODE_TRANSLATABLE = 'translatable';

  /**
   * Act on untranslatable entities.
   */
  public const MODE_UNTRANSLATABLE = 'untranslatable';

  /**
   * Act on all entities.
   */
  public const MODE_ALL = 'all';

  /**
   * Gets the entity type ID for which this redirect is used.
   *
   * @return string|null
   *   The entity type ID, or NULL in the following cases:
   *   - The redirect does not yet have an ID.
   *   - This is the default redirect.
   */
  public function getTargetEntityTypeId(): ?string;

  /**
   * Gets the entity type definition for which this redirect is used.
   *
   * @return \Drupal\Core\Entity\EntityTypeInterface|null
   *   The entity type definition, or NULL in the following cases:
   *   - The redirect does not yet have an ID.
   *   - This is the default redirect.
   *   - The entity type ID is invalid.
   */
  public function getTargetEntityType(): ?EntityTypeInterface;

  /**
   * Gets the bundle for which this redirect is used.
   *
   * @return string|null
   *   The bundle name, or NULL in the following cases:
   *   - The redirect does not yet have an ID.
   *   - This is the default redirect.
   *   - The redirect is not bundle-specific.
   */
  public function getTargetBundle(): ?string;

  /**
   * Gets the redirect status code.
   *
   * @return int|null
   *   The redirect status code.
   */
  public function getStatusCode(): ?int;

  /**
   * Gets the redirect path.
   *
   * @return string
   *   The redirect path.
   */
  public function getPath(): string;

  /**
   * Gets the redirect Url object.
   *
   * @return \Drupal\Core\Url|null
   *   The redirect Url object.
   */
  public function getUrl(): ?Url;

  /**
   * Gets the translation mode.
   *
   * @return string
   *   The translation mode.
   */
  public function getTranslationMode(): string;

  /**
   * Should redirect only happen on translatable entities?
   *
   * @return bool
   *   Whether we should act on translatable entity only or not.
   */
  public function translatableEntityOnly(): bool;

  /**
   * Should redirect only happen on untranslatable entities?
   *
   * @return bool
   *   Whether we should act on untranslatable entity only or not.
   */
  public function untranslatableEntityOnly(): bool;

  /**
   * Returns whether this redirect is locked.
   *
   * @return bool
   *   Whether the redirect is locked or not.
   */
  public function isLocked(): bool;

}
