<?php

namespace Drupal\layout_paragraphs\Ajax;

use Drupal\Core\Ajax\CommandInterface;
use Drupal\Core\Ajax\CommandWithAttachedAssetsInterface;
use Drupal\Core\Ajax\CommandWithAttachedAssetsTrait;

/**
 * Class LayoutParagraphsStateResetCommand.
 */
class LayoutParagraphsInsertCommand implements CommandInterface, CommandWithAttachedAssetsInterface {

  use CommandWithAttachedAssetsTrait;

  /**
   * The layout element settings array.
   *
   * @var array
   */
  protected $settings;

  /**
   * The content for the matched element(s).
   *
   * Either a render array or an HTML string.
   *
   * @var string|array
   */
  protected $content;


  /**
   * Constructs a LayoutParagraphsInsertCommand instance.
   */
  public function __construct($settings, $content) {
    $this->settings = $settings;
    $this->content = $content;
  }

  /**
   * Render custom ajax command.
   *
   * @return ajax
   *   Command function.
   */
  public function render() {
    return [
      'command' => 'layoutParagraphsInsert',
      'content' => $this->getRenderedContent(),
      'settings' => $this->settings,
    ];
  }

}
