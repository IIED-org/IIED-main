<?php

namespace Drupal\acquia_purge\Plugin\Purge\Purger;

use Drupal\purge\Logger\PurgeLoggerAwareTrait;

/**
 * Provides a Acquia purger which is debugging aware.
 */
trait DebuggerAwareTrait {
  use PurgeLoggerAwareTrait;

  /**
   * The debugger instance.
   *
   * @var null|\Drupal\acquia_purge\Plugin\Purge\Purger\DebuggerInterface
   */
  private $debuggerInstance = NULL;

  /**
   * {@inheritdoc}
   */
  public function debugger() {
    if (is_null($this->debuggerInstance)) {
      $this->debuggerInstance = new Debugger($this->logger());
    }
    return $this->debuggerInstance;
  }

  /**
   * {@inheritdoc}
   */
  public function setDebugger(DebuggerInterface $debugger, $throw = TRUE) {
    if ($throw && (!is_null($this->debuggerInstance))) {
      throw new \RuntimeException("Debugger already instantiated!");
    }
    $this->debuggerInstance = $debugger;
  }

}
