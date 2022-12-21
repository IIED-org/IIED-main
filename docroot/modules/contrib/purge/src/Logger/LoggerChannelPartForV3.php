<?php

namespace Drupal\purge\Logger;

/**
 * Provides a subchannel whichs logs to a single main channel with permissions.
 *
 * Supports psr/log:^3.
 */
class LoggerChannelPartForV3 extends LoggerChannelPartBase {

  /**
   * {@inheritdoc}
   */
  public function log($level, string|\Stringable $message, array $context = []): void {
    $this->doLog($level, $message, $context);
  }
}
