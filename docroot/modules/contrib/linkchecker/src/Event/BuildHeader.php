<?php

namespace Drupal\linkchecker\Event;

use Drupal\Component\EventDispatcher\Event;

/**
 * Provides an event for linkchecker headers.
 */
class BuildHeader extends Event {

  /**
   * The headers.
   *
   * @var array
   */
  protected array $headers;

  /**
   * The context.
   *
   * @var array
   */
  protected array $context;

  /**
   * Constructs the event to build the request headers.
   *
   * @param array $headers
   *   The headers.
   * @param array $context
   *   The context. It contains 2 keys and their values: "method" and "url".
   *
   * @see \Drupal\linkchecker\LinkCheckerService::check
   */
  public function __construct(array $headers, array $context) {
    $this->headers = $headers;
    $this->context = $context;
  }

  /**
   * Get the headers array.
   *
   * @return array
   *   The headers.
   */
  public function getHeaders(): array {
    return $this->headers;
  }

  /**
   * Set the headers array.
   *
   * @param array $headers
   *   The headers.
   */
  public function setHeaders(array $headers): void {
    $this->headers = $headers;
  }

  /**
   * Get the contexts array.
   *
   * @return array
   *   The context.
   */
  public function getContext(): array {
    return $this->context;
  }

}
