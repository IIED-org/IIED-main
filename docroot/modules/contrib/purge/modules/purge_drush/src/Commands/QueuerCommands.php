<?php

namespace Drupal\purge_drush\Commands;

@trigger_error('The ' . __NAMESPACE__ . '\QueuerCommands is deprecated. Instead, use \Drupal\purge\Commands\QueuerCommands', E_USER_DEPRECATED);

use Drupal\purge\Commands\QueuerCommands as QueuerCommandsBase;

/**
 * Configure Purge queuers from the command line.
 *
 * Note: This code has moved to Purge Core, see the parent class.
 *
 * @deprecated in Purge 8.x-1.x and will be removed before 2.0
 */
class QueuerCommands extends QueuerCommandsBase {}
