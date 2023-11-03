<?php

namespace Drupal\purge_drush\Commands;

@trigger_error('The ' . __NAMESPACE__ . '\DiagnosticsCommand is deprecated. Instead, use \Drupal\purge\Commands\InvalidateCommand', E_USER_DEPRECATED);

use Drupal\purge\Commands\InvalidateCommand as InvalidateCommandBase;

/**
 * Directly invalidate an item without going through the queue.
 *
 * Note: This code has moved to Purge Core, see the parent class.
 *
 * @deprecated in Purge 8.x-1.x and will be removed before 2.0
 */
class InvalidateCommand extends InvalidateCommandBase {}
