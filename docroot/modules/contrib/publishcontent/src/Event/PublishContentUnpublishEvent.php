<?php

namespace Drupal\publishcontent\Event;

use Drupal\Component\EventDispatcher\Event;
use Drupal\node\NodeInterface;

/**
 * Event that is fired when a node changes its status.
 */
class PublishContentUnpublishEvent extends Event {

  /**
   * Contains the node object.
   *
   * @var \Drupal\node\NodeInterface
   */
  public $node;

  /**
   * Contains the node language code.
   *
   * @var string
   */
  public $langcode;

  /**
   * Constructs a new PublishContentUnpublishEvent.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node object.
   * @param string $langcode
   *   The language code of the node.
   */
  public function __construct(NodeInterface $node, $langcode = '') {
    $this->node = $node;
    $this->langcode = $langcode;
  }

}
