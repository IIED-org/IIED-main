<?php

namespace Drupal\password_policy_extras\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Base class for events.
 */
abstract class CheckEvent extends Event {

  /**
   * Event parameters.
   *
   * @var array
   */
  protected array $parameters;

  /**
   * Class constructor.
   *
   * @param array $parameters
   *   The event parameters.
   */
  public function __construct(array &$parameters) {
    $this->parameters = &$parameters;
  }

  /**
   * Return the event parameters.
   *
   * @return array
   *   The event parameters.
   */
  public function &getParameters(): array {
    return $this->parameters;
  }

}
