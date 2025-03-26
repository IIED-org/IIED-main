<?php

namespace Drupal\media_pdf_thumbnail\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityPublishedInterface;

/**
 * Provides an interface for defining Pdf image entity entities.
 *
 * @ingroup media_pdf_thumbnail
 */
interface PdfImageEntityInterface extends ContentEntityInterface, EntityChangedInterface, EntityPublishedInterface {

  /**
   * Add get/set methods for your configuration properties here.
   */

  /**
   * Gets the Pdf image entity name.
   *
   * @return string|null
   *   Name of the Pdf image entity.
   */
  public function getName(): ?string;

  /**
   * Sets the Pdf image entity name.
   *
   * @param string $name
   *   The Pdf image entity name.
   *
   * @return \Drupal\media_pdf_thumbnail\Entity\PdfImageEntityInterface
   *   The called Pdf image entity entity.
   */
  public function setName(string $name): PdfImageEntityInterface;

  /**
   * Gets the Pdf image entity creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Pdf image entity.
   */
  public function getCreatedTime(): int;

  /**
   * Sets the Pdf image entity creation timestamp.
   *
   * @param int $timestamp
   *   The Pdf image entity creation timestamp.
   *
   * @return \Drupal\media_pdf_thumbnail\Entity\PdfImageEntityInterface
   *   The called Pdf image entity entity.
   */
  public function setCreatedTime(int $timestamp): PdfImageEntityInterface;

}
