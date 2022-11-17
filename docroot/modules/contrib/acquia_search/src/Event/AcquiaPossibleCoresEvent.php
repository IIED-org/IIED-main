<?php

namespace Drupal\acquia_search\Event;

use Drupal\acquia_connector\Event\EventBase;

/**
 * The event dispatched to populate possible cores outside the service.
 */
class AcquiaPossibleCoresEvent extends EventBase {

  /**
   * Readonly status of Acquia Search.
   *
   * @var bool
   */
  protected $coreReadonly;

  /**
   * Raw subscription data to alter.
   *
   * @var array
   */
  protected $possibleCores;

  /**
   * Possible cores context.
   *
   * @var array
   *   Possible Cores.
   */
  protected $context;

  /**
   * Pass in connector config by default to all events.
   *
   * @param array $possible_cores
   *   Possible Cores.
   */
  public function __construct(array $possible_cores) {
    $this->possibleCores = $possible_cores;
    $this->coreReadonly = TRUE;
  }

  /**
   * Gets possible cores from the event.
   *
   * @return array
   *   The Acquia Subscription data.
   */
  public function getPossibleCores() {
    return $this->possibleCores;
  }

  /**
   * Add possible core.
   *
   * @param string $core_id
   *   Core to be added.
   */
  public function addPossibleCore(string $core_id): void {
    if (!array_search($core_id, $this->possibleCores)) {
      $this->possibleCores[] = $core_id;
    }
  }

  /**
   * Get Readonly Status from Acquia Search.
   *
   * @return bool
   *   Readonly Status.
   */
  public function isReadOnly() {
    return $this->coreReadonly;
  }

  /**
   * Set readonly status for Acquia Search.
   *
   * @param bool $coreReadonly
   *   Set Readonly Status.
   */
  public function setReadOnly(bool $coreReadonly) {
    $this->coreReadonly = $coreReadonly;
  }

}
