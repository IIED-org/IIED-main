<?php

namespace Drupal\acquia_connector;

use Drupal\Core\Logger\RfcLoggerTrait;
use Psr\Log\LoggerInterface;

/**
 * Runs Connector related tasks during Cron.
 *
 * @package Drupal\acquia_connector
 */
class CronService implements LoggerInterface {
  use RfcLoggerTrait;

  /**
   * {@inheritdoc}
   */
  public function log($level, $message, array $context = []) {
    // Make sure that even when cron failures prevent hook_cron() from being
    // called, we still send out a heartbeat.
    if (!empty($context['channel']) && ($context['channel'] == 'cron') && ($message == 'Attempting to re-run cron while it is already running.')) {
      // Avoid doing this too frequently.
      $last_update_attempt = \Drupal::state()->get('acquia_subscription_data.timestamp', FALSE);
      if (!$last_update_attempt || ((\Drupal::time()->getRequestTime() - $last_update_attempt) >= 60 * 60)) {
        $subscription = new Subscription();
        $subscription->update();
      }
    }
  }

}
