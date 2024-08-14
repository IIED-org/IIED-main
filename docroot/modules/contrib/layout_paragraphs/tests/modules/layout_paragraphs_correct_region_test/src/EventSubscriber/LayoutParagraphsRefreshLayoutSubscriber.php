<?php

namespace Drupal\layout_paragraphs_correct_region_test\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\layout_paragraphs\Event\LayoutParagraphsUpdateLayoutEvent;

/**
 * Event subscriber.
 */
class LayoutParagraphsRefreshLayoutSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritDoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      LayoutParagraphsUpdateLayoutEvent::EVENT_NAME => 'layoutUpdated',
    ];
  }

  /**
   * Force an entire layout to be refreshed when edited.
   */
  public function layoutUpdated(LayoutParagraphsUpdateLayoutEvent $event) {
    $event->needsRefresh = TRUE;
  }

}
