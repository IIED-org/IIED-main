<?php

namespace Drupal\layout_paragraphs_empty_component_list_test\EventSubscriber;

use Drupal\Core\Messenger\Messenger;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\layout_paragraphs\Event\LayoutParagraphsAllowedTypesEvent;

/**
 * Class definition for LayoutParagraphsAllowedTypesSubcriber.
 */
class LayoutParagraphsAllowedTypesSubscriber implements EventSubscriberInterface {

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * Constructor.
   *
   * We use dependency injection get Messenger.
   *
   * @param \Drupal\Core\Messenger\Messenger $messenger
   *   The messenger service.
   */
  public function __construct(Messenger $messenger) {
    $this->messenger = $messenger;
  }

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

    // Empty the list of available component types.
    $event->setTypes([]);
    $this->messenger->addMessage('All components removed.');

  }

}
