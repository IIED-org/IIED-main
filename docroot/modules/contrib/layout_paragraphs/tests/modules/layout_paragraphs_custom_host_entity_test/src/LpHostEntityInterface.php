<?php

namespace Drupal\layout_paragraphs_custom_host_entity_test;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining a lp host entity entity type.
 */
interface LpHostEntityInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

  /**
   * Gets the lp host entity title.
   *
   * @return string
   *   Title of the lp host entity.
   */
  public function getTitle();

  /**
   * Sets the lp host entity title.
   *
   * @param string $title
   *   The lp host entity title.
   *
   * @return \Drupal\layout_paragraphs_custom_host_entity_test\LpHostEntityInterface
   *   The called lp host entity entity.
   */
  public function setTitle($title);

  /**
   * Gets the lp host entity creation timestamp.
   *
   * @return int
   *   Creation timestamp of the lp host entity.
   */
  public function getCreatedTime();

  /**
   * Sets the lp host entity creation timestamp.
   *
   * @param int $timestamp
   *   The lp host entity creation timestamp.
   *
   * @return \Drupal\layout_paragraphs_custom_host_entity_test\LpHostEntityInterface
   *   The called lp host entity entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the lp host entity status.
   *
   * @return bool
   *   TRUE if the lp host entity is enabled, FALSE otherwise.
   */
  public function isEnabled();

  /**
   * Sets the lp host entity status.
   *
   * @param bool $status
   *   TRUE to enable this lp host entity, FALSE to disable.
   *
   * @return \Drupal\layout_paragraphs_custom_host_entity_test\LpHostEntityInterface
   *   The called lp host entity entity.
   */
  public function setStatus($status);

}
