<?php

namespace Drupal\purge_drush\Commands;

@trigger_error(__NAMESPACE__ . '\DebugCommands is deprecated in purge:8.x-3.6 and is removed from purge:2.0.0. Use \Drupal\purge\Drush\Commands\DebugCommands instead. See https://www.drupal.org/node/3565396', E_USER_DEPRECATED);

use Drupal\purge\Drush\Commands\DebugCommands as DebugCommandsBase;

/**
 * Commands to help debugging caching and Purge.
 *
 * Note: This code has moved to Purge Core, see the parent class.
 *
 * @deprecated in purge:8.x-3.6 and is removed from purge:2.0.0. Use
 *   \Drupal\purge\Drush\Commands\DebugCommands instead.
 * @see https://www.drupal.org/node/3565396
 */
class DebugCommands extends DebugCommandsBase {}
