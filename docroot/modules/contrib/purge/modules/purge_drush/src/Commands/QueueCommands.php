<?php

namespace Drupal\purge_drush\Commands;

@trigger_error('The ' . __NAMESPACE__ . '\QueueCommands is deprecated. Instead, use \Drupal\purge\Commands\QueueCommands', E_USER_DEPRECATED);

use Drupal\purge\Commands\QueueCommands as QueueCommandsBase;

/**
 * Interact with the Purge queue from the command line.
 *
 * Note: This code has moved to Purge Core, see the parent class.
 *
 * @deprecated in Purge 8.x-1.x and will be removed before 2.0
 */
class QueueCommands extends QueueCommandsBase {}
