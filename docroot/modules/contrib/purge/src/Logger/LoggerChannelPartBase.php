<?php

namespace Drupal\purge\Logger;

use Drupal\Core\Logger\LoggerChannel;
use Drupal\Core\Logger\RfcLogLevel;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;

/**
 * Provides a subchannel whichs logs to a single main channel with permissions.
 */
abstract class LoggerChannelPartBase extends LoggerChannel implements LoggerChannelPartInterface {
  use LoggerTrait;

  /**
   * Access levels for each RFC 5424 log type.
   *
   * The constructor changes the granted levels to TRUE so that $grants
   * doesn't have to be searched/iterated each and every time.
   *
   * @var bool[]
   */
  protected $access = [
    RfcLogLevel::EMERGENCY => FALSE,
    RfcLogLevel::ALERT => FALSE,
    RfcLogLevel::CRITICAL => FALSE,
    RfcLogLevel::ERROR => FALSE,
    RfcLogLevel::WARNING => FALSE,
    RfcLogLevel::NOTICE => FALSE,
    RfcLogLevel::INFO => FALSE,
    RfcLogLevel::DEBUG => FALSE,
  ];

  /**
   * The identifier of the channel part.
   *
   * @var string
   */
  protected $id = '';

  /**
   * Permitted RFC 5424 log types.
   *
   * @var int[]
   */
  protected $grants = [];

  /**
   * The single and central logger channel used by purge module(s).
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $loggerChannelPurge;

  /**
   * {@inheritdoc}
   */
  public function __construct(LoggerInterface $logger_channel_purge, $id, array $grants = []) {
    $this->id = $id;
    $this->grants = $grants;
    $this->addLogger($logger_channel_purge);
    foreach ($grants as $grant) {
      $this->access[$grant] = TRUE;
    }
    parent::__construct('purge');
  }

  /**
   * {@inheritdoc}
   */
  public function getGrants() {
    return $this->grants;
  }

  /**
   * {@inheritdoc}
   */
  public function isDebuggingEnabled() {
    return $this->access[RfcLogLevel::DEBUG];
  }

  /**
   * Logger Channel Message.
   *
   * @param $level
   *   Log Level.
   * @param $message
   *   The message.
   * @param $context
   *   Context for the message.
   *
   * @return void
   */
  protected function doLog($level, $message, $context = []): void {
    if ($this->access[$this->levelTranslation[$level]]) {
      $context += ['@purge_channel_part' => $this->id];
      $message = '@purge_channel_part: ' . $message;
      parent::log($level, $message, $context);
    }
  }
}
