<?php

namespace Drupal\content_translation_redirect;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining an Content Translation Redirect entity.
 */
interface ContentTranslationRedirectInterface extends ConfigEntityInterface {

  /**
   * Sets the redirect status code.
   *
   * @param int $code
   *   Redirect status code.
   */
  public function setStatusCode($code);

  /**
   * Sets the message after redirection.
   *
   * @param string $message
   *   Message after redirection.
   */
  public function setMessage($message);

  /**
   * Gets the redirect status code.
   *
   * @return int
   *   Redirect status code.
   */
  public function getStatusCode();

  /**
   * Gets the message after redirection.
   *
   * @return string
   *   Message after redirection.
   */
  public function getMessage();

}
