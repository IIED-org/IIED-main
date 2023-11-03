<?php

namespace Drupal\purge_drush\Commands;

@trigger_error('The ' . __NAMESPACE__ . '\TypesCommand is deprecated. Instead, use \Drupal\purge\Commands\TypesCommand', E_USER_DEPRECATED);

use Drupal\purge\Commands\TypesCommand as TypesCommandBase;

/**
 * List all supported cache invalidation types.
 *
 * Note: This code has moved to Purge Core, see the parent class.
 *
 * @deprecated in Purge 8.x-1.x and will be removed before 2.0
 */
class TypesCommand extends TypesCommandBase {}
