<?php

namespace Drupal\layout_paragraphs;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Drupal\layout_paragraphs\Event\LayoutParagraphsUpdateLayoutEvent;

/**
 * Trait for managing refresh state for layouts.
 *
 * When a layout is changed in the layout builder,
 * an event is dispatched to determine whether or not
 * the entire layout builder ui should be refreshed.
 *
 * @see \Drupal\layout_paragraphs\Event\LayoutParagraphsUpdateLayoutEvent
 * @see \Drupal\layout_paragraphs\EventSubscriber\LayoutParagraphsUpdateLayoutSubscriber
 */
trait LayoutParagraphsLayoutRefreshTrait {

  /**
   * The layout paragraphs layout object.
   *
   * @var \Drupal\layout_paragraphs\LayoutParagraphsLayout
   */
  protected $layoutParagraphsLayout;


  /**
   * The event dispatcher service.
   *
   * @var \Symfony\Contracts\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The original paragraphs layout object.
   *
   * @var \Drupal\layout_paragraphs\LayoutParagraphsLayout
   */
  protected $originalLayoutParagraphsLayout;

  /**
   * Setter for layoutParagraphsLayout property.
   *
   * Also creates an original copy to track changes between original
   * and updated layouts.
   *
   * @param \Drupal\layout_paragraphs\LayoutParagraphsLayout $layout_paragraphs_layout
   *   The layout paragraphs layout object.
   *
   * @return $this
   */
  public function setLayoutParagraphsLayout(LayoutParagraphsLayout $layout_paragraphs_layout) {
    $this->layoutParagraphsLayout = $layout_paragraphs_layout;
    $reference_field = clone $this->layoutParagraphsLayout->getParagraphsReferenceField();
    $settings = $this->layoutParagraphsLayout->getSettings();
    $this->originalLayoutParagraphsLayout = new LayoutParagraphsLayout($reference_field, $settings);
    return $this;
  }

  /**
   * Decorates an ajax response with a command to refresh an entire layout.
   *
   * @param \Drupal\Core\Ajax\AjaxResponse $response
   *   The ajax response to decorate.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The ajax response.
   */
  protected function refreshLayout(AjaxResponse $response) {
    $layout = $this->renderLayout();
    $dom_selector = '[data-lpb-id="' . $this->layoutParagraphsLayout->id() . '"]';
    $response->addCommand(new ReplaceCommand($dom_selector, $layout));
    return $response;
  }

  /**
   * Renders the layout builder UI render array.
   *
   * @return array
   *   The layout builder render array.
   */
  protected function renderLayout() {
    return [
      '#type' => 'layout_paragraphs_builder',
      '#layout_paragraphs_layout' => $this->layoutParagraphsLayout,
    ];
  }

  /**
   * Returns TRUE if the layout needs to be refreshed.
   *
   * @return bool
   *   Whether or not the layout needs to be refreshed.
   */
  protected function needsRefresh() {
    $event = new LayoutParagraphsUpdateLayoutEvent($this->originalLayoutParagraphsLayout, $this->layoutParagraphsLayout);
    $this->eventDispatcher()->dispatch($event, LayoutParagraphsUpdateLayoutEvent::EVENT_NAME);
    return $event->needsRefresh;
  }

  /**
   * Returns the event dispatcher service.
   *
   * @return \Symfony\Contracts\EventDispatcher\EventDispatcherInterface
   *   The event dispatcher service.
   */
  protected function eventDispatcher() {
    if (!($this->eventDispatcher && $this->eventDispatcher instanceof EventDispatcherInterface)) {
      $this->eventDispatcher = \Drupal::service('event_dispatcher');
    }
    return $this->eventDispatcher;
  }

}
