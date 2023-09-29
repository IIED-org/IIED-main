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
   * Sets the redirect status code.
   *
   * @param int|null $code
   *   The redirect status code.
   *
   * @return $this
   */
  public function setStatusCode(?int $code): ContentTranslationRedirectInterface;

  /**
   * Gets the redirect status code.
   *
   * @return int|null
   *   The redirect status code.
   */
  public function getStatusCode(): ?int;

  /**
   * Sets the redirect path.
   *
   * @param string $path
   *   The redirect path.
   *
   * @return $this
   */
  public function setPath(string $path): ContentTranslationRedirectInterface;

  /**
   * Gets the redirect path.
   *
   * @return string|null
   *   The redirect path.
   */
  public function getPath(): ?string;

  /**
   * Gets the redirect Url object.
   *
   * @return \Drupal\Core\Url|null
   *   The redirect Url object.
   */
  public function getUrl(): ?Url;

  /**
   * Returns whether this redirect is locked.
   *
   * @return bool
   *   Whether the redirect is locked or not.
   */
  public function isLocked(): bool;

  /**
   * Should redirects only happen on translatable entities?
   *
   * @return bool
   *   Whether we should act on translatable entity only or not.
   */
  public function translatableEntityOnly(): bool;

}
