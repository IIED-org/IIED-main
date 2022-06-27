<?php

namespace Drupal\default_content_deploy\Event;

use Drupal\Component\EventDispatcher\Event;
use Drupal\Core\Entity\ContentEntityInterface;

/**
 * PreSerializeEvent.
 */
class PreSerializeEvent extends Event {

  /**
   * @var \Drupal\Core\Entity\ContentEntityInterface
   */
  protected $entity;

  /**
   * @var string
   */
  protected $mode;

  /**
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   * @param string $mode
   */
  public function __construct(ContentEntityInterface $entity, $mode) {
    $this->entity = $entity;
    $this->mode = $mode;
  }

  /**
   * @return \Drupal\Core\Entity\ContentEntityInterface|null
   */
  public function getEntity() {
    return $this->entity;
  }

  /**
   * @param \Drupal\Core\Entity\ContentEntityInterface|null $entity
   */
  public function setEntity($entity = NULL) {
    $this->entity = $entity;
  }

  /**
   * @return string
   */
  public function getMode() {
    return $this->mode;
  }
}
