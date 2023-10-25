<?php

namespace Drupal\layout_paragraphs\Event;

use Symfony\Contracts\EventDispatcher\Event;
use Drupal\layout_paragraphs\LayoutParagraphsLayout;

/**
 * Class definition for Layout Paragraphs Allowed Types event.
 *
 * Developers can subscribe to this event and modify the $needsRefresh flag
 * to specify that a layout should or should not be completely refreshed
 * following a particular operation, by comparing the states of
 * $originalLayout and $layout.
 *
 * Layout Paragraphs will attempt to return the smallest payload possible and
 * avoid refreshing the entire builder whenever possible (for example, by
 * appending the HTML for a new paragraph in the correct position in the ui).
 * There are times when simply appending or replacing the new or altered
 * paragraph is insufficient for correclty rendering the layout, and the entire
 * builder interface should be refreshed by setting $needsRefresh to true in
 * this event.
 */
class LayoutParagraphsUpdateLayoutEvent extends Event {

  const EVENT_NAME = 'layout_paragraphs_update_layout';

  /**
   * The origin layout object.
   *
   * @var \Drupal\layout_paragraphs\LayoutParagraphsLayout
   */
  protected $originalLayout;

  /**
   * The updated layout object.
   *
   * @var \Drupal\layout_paragraphs\LayoutParagraphsLayout
   */
  protected $layout;

  /**
   * TRUE if the entire layout needs to be refreshed.
   *
   * @var bool
   */
  public $needsRefresh = FALSE;

  /**
   * Class cosntructor.
   *
   * @param \Drupal\layout_paragraphs\LayoutParagraphsLayout $original_layout
   *   The layout object.
   * @param \Drupal\layout_paragraphs\LayoutParagraphsLayout $layout
   *   The layout object.
   */
  public function __construct(LayoutParagraphsLayout $original_layout, LayoutParagraphsLayout $layout) {
    $this->originalLayout = $original_layout;
    $this->layout = $layout;
  }

  /**
   * Get the original layout.
   *
   * @return \Drupal\layout_paragraphs\LayoutParagraphsLayout
   *   The original layout.
   */
  public function getOriginalLayout() {
    return $this->originalLayout;
  }

  /**
   * Get the updated layout.
   *
   * @return \Drupal\layout_paragraphs\LayoutParagraphsLayout
   *   The updated layout.
   */
  public function getUpdatedLayout() {
    return $this->layout;
  }

}
