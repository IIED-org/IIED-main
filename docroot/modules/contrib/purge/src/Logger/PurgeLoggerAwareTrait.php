<?php

namespace Drupal\purge\Logger;

use Psr\Log\LoggerInterface;

/**
 * Provides logging services for purge components.
 */
trait PurgeLoggerAwareTrait {

  /**
   * Channel logger.
   *
   * @var null|\Drupal\purge\Logger\LoggerChannelPartInterface
   */
  protected $logger;

  /**
   * {@inheritdoc}
   */
  public function logger() {
    if (is_null($this->logger)) {
      throw new \LogicException('Logger unavailable, call ::setLogger().');
    }
    return $this->logger;
  }

  /**
   * Sets a logger.
   *
   * @param \Psr\Log\LoggerInterface $logger
   *   The Logger.
   */
  public function setLogger(LoggerInterface $logger): void {
    $this->logger = $logger;
  }

}
