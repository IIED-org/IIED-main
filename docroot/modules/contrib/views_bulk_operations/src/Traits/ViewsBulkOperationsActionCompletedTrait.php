<?php

declare(strict_types=1);

namespace Drupal\views_bulk_operations\Traits;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Defines action completion logic.
 */
trait ViewsBulkOperationsActionCompletedTrait {

  /**
   * Set message function wrapper.
   *
   * @see \Drupal\Core\Messenger\MessengerInterface
   */
  public static function message(string|MarkupInterface $message, string $type = 'status', bool $repeat = TRUE): void {
    \Drupal::messenger()->addMessage($message, $type, $repeat);
  }

  /**
   * Translation function wrapper.
   *
   * @see \Drupal\Core\StringTranslation\TranslationInterface:translate()
   */
  public static function translate(string $string, array $args = [], array $options = []): TranslatableMarkup {
    return \Drupal::translation()->translate($string, $args, $options);
  }

  /**
   * Batch finished callback.
   *
   * @param bool $success
   *   Was the process successful?
   * @param array $results
   *   Batch process results array.
   * @param array $operations
   *   Performed operations array.
   */
  public static function finished($success, array $results, array $operations): ?RedirectResponse {
    if ($success) {
      foreach ($results['operations'] as $item) {
        if (str_contains($item['message'], '@count')) {
          $message = new FormattableMarkup($item['message'], [
            '@count' => $item['count'],
          ]);
        }
        else {
          $message_string = (string) $item['message'];
          $message = new FormattableMarkup("$message_string (@count)", [
            '@count' => $item['count'],
          ]);
        }
        static::message($message, $item['type']);
      }
    }
    else {
      $message = static::translate('Finished with an error.');
      static::message((string) $message, 'error');
    }
    return NULL;
  }

}
