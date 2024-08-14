<?php

namespace Drupal\layout_paragraphs\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\layout_paragraphs\Event\LayoutParagraphsAllowedTypesEvent;

/**
 * Class definition for LayoutParagraphsAllowedTypesSubcriber.
 */
class LayoutParagraphsAllowedTypesSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritDoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      LayoutParagraphsAllowedTypesEvent::EVENT_NAME => 'typeRestrictions',
    ];
  }

  /**
   * Restricts available types based on settings in layout.
   *
   * @param \Drupal\layout_paragraphs\Event\LayoutParagraphsAllowedTypesEvent $event
   *   The allowed types event.
   */
  public function typeRestrictions(LayoutParagraphsAllowedTypesEvent $event) {

    $parent_uuid = $event->getParentUuid();
    $types = $event->getTypes();
    $layout = $event->getLayout();
    $settings = $layout->getSettings();

    if ($settings['require_layouts'] && !$parent_uuid) {
      $event->setTypes(array_filter($types, function ($type) {
        return $type['is_section'] === TRUE;
      }));
    }

    $depth = 0;
    while ($parent = $layout->getComponentByUuid($parent_uuid)) {
      $depth++;
      $parent_uuid = $parent->getParentUuid();
    }
    if ($depth > $settings['nesting_depth']) {
      $event->setTypes(array_filter($types, function ($type) {
        return $type['is_section'] === FALSE;
      }));
    }

  }

}
