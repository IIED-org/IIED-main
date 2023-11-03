<?php

namespace Drupal\purge_drush\Commands;

@trigger_error('The ' . __NAMESPACE__ . '\DiagnosticsCommand is deprecated. Instead, use \Drupal\purge\Commands\DiagnosticsCommand', E_USER_DEPRECATED);

use Drupal\purge\Commands\DiagnosticsCommand as DiagnosticsCommandBase;

/**
 * Generate a diagnostic self-service report.
 *
 * Note: This code has moved to Purge Core, see the parent class.
 *
 * @deprecated in Purge 8.x-1.x and will be removed before 2.0
 */
class DiagnosticsCommand extends DiagnosticsCommandBase {}
