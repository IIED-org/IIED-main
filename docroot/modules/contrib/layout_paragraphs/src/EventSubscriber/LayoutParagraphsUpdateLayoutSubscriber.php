<?php

namespace Drupal\layout_paragraphs\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\layout_paragraphs\Event\LayoutParagraphsUpdateLayoutEvent;

/**
 * Event subscriber.
 */
class LayoutParagraphsUpdateLayoutSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritDoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      LayoutParagraphsUpdateLayoutEvent::EVENT_NAME => 'layoutUpdated',
    ];
  }

  /**
   * Determines if a Layout Paragraphs Builder UI needs to be refreshed.
   *
   * Some interactions will create conditions where the entire builder UI needs
   * to be refreshed, rather than simply returning a single new or edited
   * component. For example: when removing the only component from a layout,
   * the layout should be refreshed to show the correct ui/messaging for an
   * empty container.
   *
   * @param \Drupal\layout_paragraphs\Event\LayoutParagraphsUpdateLayoutEvent $event
   *   The event.
   */
  public function layoutUpdated(LayoutParagraphsUpdateLayoutEvent $event) {
    $event->needsRefresh = $this->compareEmptyState($event) || $this->compareMaximumReached($event);
  }

  /**
   * Compares the empty state of the original and updated layout.
   *
   * If the original layout was empty and the updated layout is not, or visa
   * versa, the entire layout builder ui needs to be refreshed.
   *
   * @param \Drupal\layout_paragraphs\Event\LayoutParagraphsUpdateLayoutEvent $event
   *   The event.
   */
  public function compareEmptyState(LayoutParagraphsUpdateLayoutEvent $event) {
    $original = $event->getOriginalLayout()->getParagraphsReferenceField();
    $layout = $event->getUpdatedLayout()->getParagraphsReferenceField();
    return $original->isEmpty() != $layout->isEmpty();
  }

  /**
   * Compares count == cardinality of the original and updated layouts.
   *
   * @param \Drupal\layout_paragraphs\Event\LayoutParagraphsUpdateLayoutEvent $event
   *   The event.
   *
   * @return bool
   *   True if the count == cardinality limit has changed.
   */
  public function compareMaximumReached(LayoutParagraphsUpdateLayoutEvent $event) {

    $original_count = $event->getOriginalLayout()->getParagraphsReferenceField()->count();
    $updated_count = $event->getUpdatedLayout()->getParagraphsReferenceField()->count();
    $cardinality = $event->getUpdatedLayout()
      ->getParagraphsReferenceField()
      ->getFieldDefinition()
      ->getFieldStorageDefinition()
      ->getCardinality();

    $original_is_max = $cardinality > 0 && $cardinality <= $original_count;
    $updated_is_max = $cardinality > 0 && $cardinality <= $updated_count;

    return $original_is_max != $updated_is_max;
  }

}
