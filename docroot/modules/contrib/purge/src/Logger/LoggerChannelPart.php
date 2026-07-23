<?php

namespace Drupal\purge\Logger;

/**
 * Logger workaround for Drupal 9 and 10.
 *
 * Provides support for psr/log v1 and v3. With psr/log:^3 PHP 8.0 type hints
 * are used, which breaks PHP 7.4. This allows overriding methods in the psr/log
 * interface while supporting v1 and v3.
 *
 * @todo Remove when supporting only Drupal 10+.
 */
if (version_compare(\Drupal::VERSION, 10, '<')) {
  class_alias('Drupal\purge\Logger\LoggerChannelPartForV1', 'Drupal\purge\Logger\LoggerChannelPart');
}
else {
  class_alias('Drupal\purge\Logger\LoggerChannelPartForV3', 'Drupal\purge\Logger\LoggerChannelPart');
}

if (FALSE) {

  /**
   * Placeholder class for static analysis and IDE autocomplete.
   *
   * The actual class is resolved via class_alias above based on the
   * installed version of drupal/core-psr-log / Psr\Log.
   */
  class LoggerChannelPart extends LoggerChannelPartBase {
  }
}
