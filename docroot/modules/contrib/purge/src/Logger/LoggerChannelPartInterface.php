<?php

namespace Drupal\purge\Logger;

use Psr\Log\LoggerInterface;

/**
 * Describes a subchannel whichs logs to a single main channel with permissions.
 */
interface LoggerChannelPartInterface extends LoggerInterface {

  /**
   * Construct \Drupal\purge\Logger\LoggerChannelPartInterface.
   *
   * @param \Psr\Log\LoggerInterface $logger_channel_purge
   *   The single and central logger channel used by purge module(s).
   * @param string $id
   *   The identifier of the channel part.
   * @param int[] $grants
   *   Unassociative array of RFC 5424 log types. Each passed type grants the
   *   channel permission to log that type of message, without specific
   *   permissions the logger will stay silent for that type.
   *
   *   Grants available:
   *    - \Drupal\Core\Logger\RfcLogLevel::EMERGENCY
   *    - \Drupal\Core\Logger\RfcLogLevel::ALERT
   *    - \Drupal\Core\Logger\RfcLogLevel::CRITICAL
   *    - \Drupal\Core\Logger\RfcLogLevel::ERROR
   *    - \Drupal\Core\Logger\RfcLogLevel::WARNING
   *    - \Drupal\Core\Logger\RfcLogLevel::NOTICE
   *    - \Drupal\Core\Logger\RfcLogLevel::INFO
   *    - \Drupal\Core\Logger\RfcLogLevel::DEBUG.
   */
  public function __construct(LoggerInterface $logger_channel_purge, $id, array $grants = []);

  /**
   * Retrieve given grants.
   *
   * @return int[]
   *   Unassociative array of enabled RFC 5424 log types:
   *    - \Drupal\Core\Logger\RfcLogLevel::EMERGENCY
   *    - \Drupal\Core\Logger\RfcLogLevel::ALERT
   *    - \Drupal\Core\Logger\RfcLogLevel::CRITICAL
   *    - \Drupal\Core\Logger\RfcLogLevel::ERROR
   *    - \Drupal\Core\Logger\RfcLogLevel::WARNING
   *    - \Drupal\Core\Logger\RfcLogLevel::NOTICE
   *    - \Drupal\Core\Logger\RfcLogLevel::INFO
   *    - \Drupal\Core\Logger\RfcLogLevel::DEBUG.
   */
  public function getGrants();

  /**
   * Determine whether this channel has a RfcLogLevel::DEBUG grant.
   *
   * @return bool
   *   Whether debugging is enabled.
   */
  public function isDebuggingEnabled();

}
