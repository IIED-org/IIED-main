<?php

namespace Drupal\purge_drush\Commands;

@trigger_error(__NAMESPACE__ . '\InvalidateCommand is deprecated in purge:8.x-3.6 and is removed from purge:2.0.0. Use \Drupal\purge\Drush\Commands\InvalidateCommand instead. See https://www.drupal.org/node/3565396', E_USER_DEPRECATED);

use Drupal\purge\Drush\Commands\InvalidateCommand as InvalidateCommandBase;

/**
 * Directly invalidate an item without going through the queue.
 *
 * Note: This code has moved to Purge Core, see the parent class.
 *
 * @deprecated in purge:8.x-3.6 and is removed from purge:2.0.0. Use
 *   \Drupal\purge\Drush\Commands\InvalidateCommand instead.
 * @see https://www.drupal.org/node/3565396
 */
class InvalidateCommand extends InvalidateCommandBase {}
