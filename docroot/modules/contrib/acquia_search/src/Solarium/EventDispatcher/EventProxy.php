<?php

namespace Drupal\acquia_search\Solarium\EventDispatcher;

use Symfony\Component\EventDispatcher\Event;

/**
 * A proxy for events defined by symfony contracts to be used with Drupal 8.
 *
 * @phpstan-ignore-next-line
 */
class EventProxy extends Event {

  /**
   * Event being dispatched.
   *
   * @var \Symfony\Contracts\EventDispatcher\Event
   */
  protected $event;

  /**
   * {@inheritdoc}
   */
  public function __construct($event) {
    $this->event = $event;
  }

  /**
   * {@inheritdoc}
   */
  public function isPropagationStopped() {
    return $this->event->isPropagationStopped();
  }

  /**
   * {@inheritdoc}
   */
  public function stopPropagation() {
    $this->event->stopPropagation();
  }

  /**
   * Proxies all method calls to the original event.
   */
  public function __call($method, $arguments) {
    return $this->event->{$method}(...$arguments);
  }

}
