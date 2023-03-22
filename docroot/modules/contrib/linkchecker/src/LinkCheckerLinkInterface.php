<?php

namespace Drupal\linkchecker;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Entity\FieldableEntityInterface;

/**
 * Provides an interface defining a LinkCheckerLink type entity.
 */
interface LinkCheckerLinkInterface extends EntityPublishedInterface, ContentEntityInterface {

  const TYPE_ALL = 0;

  const TYPE_EXTERNAL = 1;

  const TYPE_INTERNAL = 2;

  /**
   * Generates a unique hash for identification purposes.
   *
   * @param string $uri
   *   URI string.
   *
   * @return string
   *   Base 64 hash.
   */
  public static function generateHash($uri);

  /**
   * Gets a URL hash.
   *
   * @return string
   *   The URL hash.
   */
  public function getHash();

  /**
   * Sets a URL hash.
   *
   * @param string $hash
   *   New hash.
   *
   * @return $this
   */
  public function setHash($hash);

  /**
   * Gets URL that was found.
   *
   * @return string
   *   Url.
   */
  public function getUrl();

  /**
   * Sets new URL.
   *
   * @param string $url
   *   The URL.
   *
   * @return $this
   */
  public function setUrl($url);

  /**
   * Gets a request method.
   *
   * @return string
   *   The method.
   */
  public function getRequestMethod();

  /**
   * Sets a request method.
   *
   * @param string $method
   *   The request method.
   *
   * @return $this
   */
  public function setRequestMethod($method);

  /**
   * Gets a response status code.
   *
   * -1 means the link was not checked.
   *
   * @return int
   *   The status code.
   */
  public function getStatusCode();

  /**
   * Sets a response status code.
   *
   * @param int $code
   *   The status code.
   *
   * @return $this
   */
  public function setStatusCode($code);

  /**
   * Gets a response error message.
   *
   * @return string
   *   The error message.
   */
  public function getErrorMessage();

  /**
   * Sets a response error message.
   *
   * @param string $message
   *   The error message.
   *
   * @return $this
   */
  public function setErrorMessage($message);

  /**
   * Gets a number of failed requests.
   *
   * @return int
   *   Number.
   */
  public function getFailCount();

  /**
   * Sets a number of failed requests.
   *
   * @param int $count
   *   New amount.
   *
   * @return $this
   */
  public function setFailCount($count);

  /**
   * Gets last time when the link was checked.
   *
   * @return int
   *   Timestamp.
   */
  public function getLastCheckTime();

  /**
   * Sets a last time the link was checked.
   *
   * @param int $time
   *   Timestamp.
   *
   * @return $this
   */
  public function setLastCheckTime($time);

  /**
   * Gets entity where the link was found.
   *
   * @return \Drupal\Core\Entity\FieldableEntityInterface
   *   Entity.
   */
  public function getParentEntity();

  /**
   * Sets a entity where the link was found.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   Entity.
   *
   * @return $this
   */
  public function setParentEntity(FieldableEntityInterface $entity);

  /**
   * Gets a field name where the link was found.
   *
   * @return string
   *   Field name.
   */
  public function getParentEntityFieldName();

  /**
   * Sets a field name where the link was found.
   *
   * @param string $fieldName
   *   Field name.
   *
   * @return $this
   */
  public function setParentEntityFieldName($fieldName);

  /**
   * Gets a langcode of entity translation where the link was found.
   *
   * @return string
   *   Langcode.
   */
  public function getParentEntityLangcode();

  /**
   * Sets a langcode of entity translation where the link was found.
   *
   * @param string $langcode
   *   Langcode.
   *
   * @return $this
   */
  public function setParentEntityLangcode($langcode);

  /**
   * Returns whether or not the link checking is enabled.
   *
   * @return bool
   *   TRUE if link checking is enabled, FALSE otherwise.
   */
  public function isLinkCheckStatus();

  /**
   * Enable link checking.
   *
   * @return $this
   */
  public function setEnableLinkCheck();

  /**
   * Disable link checking.
   *
   * @return $this
   */
  public function setDisableLinkCheck();

  /**
   * Checks if link was not removed from content.
   *
   * If link becomes blacklisted this method should return false.
   *
   * @return bool
   *   TRUE if link exists in a content.
   */
  public function isExists();

}
