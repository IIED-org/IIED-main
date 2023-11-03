<?php

namespace Drupal\purge\Logger;

/**
 * Provides a subchannel whichs logs to a single main channel with permissions.
 *
 * Supports psr/log:^1.
 */
class LoggerChannelPartForV1 extends LoggerChannelPartBase {

  /**
   * {@inheritdoc}
   */
  public function log($level, $message, array $context = []): void {
    $this->doLog($level, $message, $context);
  }

}
