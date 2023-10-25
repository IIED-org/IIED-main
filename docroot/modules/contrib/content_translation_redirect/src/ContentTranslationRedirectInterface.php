<?php

namespace Drupal\content_translation_redirect;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
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
