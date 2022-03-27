<?php

namespace Drupal\layout_paragraphs\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 * Class LayoutParagraphsStateResetCommand.
 */
class LayoutParagraphsStateResetCommand implements CommandInterface {

  /**
   * The wrapper DOM id.
   *
   * @var string
   */
  protected $id;

  /**
   * Constructs an ErlState instance.
   */
  public function __construct($id) {
    $this->id = $id;
  }

  /**
   * Render custom ajax command.
   *
   * @return ajax
   *   Command function.
   */
  public function render() {
    return [
      'command' => 'resetLayoutParagraphsState',
      'data' => [
        "id" => $this->id,
      ],
    ];
  }

}
