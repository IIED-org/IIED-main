<?php

namespace Drupal\acquia_search\Solarium\EventDispatcher;

use Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * A helper to decorate the legacy EventDispatcherInterface::dispatch().
 */
final class Psr14Bridge extends ContainerAwareEventDispatcher implements EventDispatcherInterface {

  /**
   * Event Dispatcher.
   *
   * @var \Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher
   */
  protected $dispatcher;

  /**
   * {@inheritdoc}
   */
  public function __construct(ContainerAwareEventDispatcher $eventDispatcher) {
    $this->dispatcher = $eventDispatcher;
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-ignore-next-line
   */
  public function dispatch($event, Event $null = NULL) {
    if (\is_object($event)) {
      return $this->dispatcher->dispatch(\get_class($event), new EventProxy($event));
    }
    return $this->dispatcher->dispatch($event, $null);
  }

  /**
   * {@inheritdoc}
   */
  public function addListener($event_name, $listener, $priority = 0) {
    $this->dispatcher->addListener($event_name, $listener, $priority);
  }

}
