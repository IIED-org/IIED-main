<?php

namespace Drupal\purge_drush\Commands;

@trigger_error(__NAMESPACE__ . '\PurgerCommands is deprecated in purge:8.x-3.6 and is removed from purge:2.0.0. Use \Drupal\purge\Drush\Commands\PurgerCommands instead. See https://www.drupal.org/node/3565396', E_USER_DEPRECATED);

use Drupal\purge\Drush\Commands\PurgerCommands as PurgerCommandsBase;

/**
 * Configure Purge Purgers from the command line.
 *
 * Note: This code has moved to Purge Core, see the parent class.
 *
 * @deprecated in purge:8.x-3.6 and is removed from purge:2.0.0. Use
 *   \Drupal\purge\Drush\Commands\PurgerCommands instead.
 * @see https://www.drupal.org/node/3565396
 */
class PurgerCommands extends PurgerCommandsBase {}
