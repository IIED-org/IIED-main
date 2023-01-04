<?php

namespace Drupal\acquia_purge\Plugin\Purge\Purger;

/**
 * Describes a Acquia purger which is debugging aware.
 */
interface DebuggerAwareInterface {

  /**
   * Return existing debugger instance or instantiate new debugger.
   *
   * @warning
   *   Calls $this->logger() which must be able to return a logger.
   *
   * @return \Drupal\acquia_purge\Plugin\Purge\Purger\DebuggerInterface
   *   The debugger.
   */
  public function debugger();

  /**
   * Set the debugger instance.
   *
   * @param \Drupal\acquia_purge\Plugin\Purge\Purger\DebuggerInterface $debugger
   *   The debugger.
   * @param bool $throw
   *   Throw an exception when the debugger is already set.
   *
   * @throws \RuntimeException
   *   Thrown when the debugger was already instantiated.
   */
  public function setDebugger(DebuggerInterface $debugger, $throw = TRUE);

}
