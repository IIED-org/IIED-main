<?php

namespace Drupal\layout_paragraphs\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 * Class ExampleCommand.
 */
class LayoutParagraphsEventCommand implements CommandInterface {

  /**
   * The layout object.
   *
   * @var \Drupal\layout_paragraphs\LayoutParagraphsLayout
   */
  protected $layout;

  /**
   * The component uuid.
   *
   * @var string
   */
  protected $componentUuid;

  /**
   * The event to trigger.
   *
   * @var string
   */
  protected $eventName;

  /**
   * Constructor.
   */
  public function __construct($layout, $component_uuid, $event_name) {
    $this->layout = $layout;
    $this->componentUuid = $component_uuid;
    $this->eventName = $event_name;
  }

  /**
   * Render custom ajax command.
   *
   * @return array
   *   The command array.
   */
  public function render() {
    return [
      'command' => 'LayoutParagraphsEventCommand',
      'layoutId' => $this->layout->id(),
      'componentUuid' => $this->componentUuid,
      'eventName' => $this->eventName,
    ];
  }

}
