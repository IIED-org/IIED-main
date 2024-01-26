<?php

namespace Drupal\message_ui\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\Render\Markup;
use Drupal\message\Entity\Message;
use Drupal\message\Entity\MessageTemplate;

/**
 * Queue worker plugin instance to update the message arguments.
 *
 * @QueueWorker(
 *   id = "message_ui_arguments",
 *   title = @Translation("Message UI arguments"),
 *   cron = {"time" = 60}
 * )
 */
class MessageArgumentsWorker extends QueueWorkerBase {

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {

    // Load all of the messages.
    $query = \Drupal::entityQuery('message');
    $result = $query
      ->condition('template', $data['template'])
      ->sort('mid', 'DESC')
      ->condition('mid', $data['last_mid'], '>=')
      ->range(0, $data['item_to_process'])
      ->accessCheck(FALSE)
      ->execute();

    if (empty($result)) {
      return FALSE;
    }

    // Update the messages.
    $messages = Message::loadMultiple(array_keys($result));

    foreach ($messages as $message) {
      /** @var \Drupal\message\Entity\Message $message */
      self::messageArgumentsUpdate($message, $data['new_arguments']);
      $data['last_mid'] = $message->id();
    }

    // Create the next queue worker.
    $queue = \Drupal::queue('message_ui_arguments');
    return $queue->createItem($data);
  }

  /**
   * Get hard coded arguments.
   *
   * @param string $template
   *   The message template.
   * @param bool $count
   *   Determine weather to the count the arguments or return a list of them.
   *
   * @return int|array
   *   The number of the arguments.
   */
  public static function getArguments($template, $count = FALSE) {

    /** @var \Drupal\message\Entity\MessageTemplate $message_template */
    if (!$message_template = MessageTemplate::load($template)) {
      return [];
    }

    if (!$output = $message_template->getText()) {
      return [];
    }

    $text = array_map(function (Markup $markup) {
      return (string) $markup;
    }, $output);

    $text = implode("\n", $text);
    preg_match_all('/[@|%|\!]\{([a-z0-9:_\-]+?)\}/i', $text, $matches);

    return $count ? count($matches[0]) : $matches[0];
  }

  /**
   * A helper function for generate a new array of the message's arguments.
   *
   * @param \Drupal\message\Entity\Message $message
   *   The message with arguments need an update.
   * @param array $arguments
   *   The new arguments need to be calculated.
   */
  public static function messageArgumentsUpdate(Message $message, array $arguments) {

    $message_arguments = [];

    foreach ($arguments as $token) {
      // Get the hard coded value of the message.
      $token_name = str_replace(['@{', '}'], ['[', ']'], $token);
      $token_service = \Drupal::token();
      $value = $token_service->replace($token_name, ['message' => $message]);

      $message_arguments[$token] = $value;
    }

    $message->setArguments($message_arguments);
    $message->save();
  }

  /**
   * The message batch or queue item callback function.
   *
   * @param array $mids
   *   The messages ID for process.
   * @param array $arguments
   *   The new state arguments.
   */
  public static function argumentsUpdate(array $mids, array $arguments) {
    // Load the messages and update them.
    $messages = Message::loadMultiple($mids);

    foreach ($messages as $message) {
      /** @var \Drupal\message\Entity\Message $message */
      MessageArgumentsWorker::messageArgumentsUpdate($message, $arguments);
    }
  }

}
