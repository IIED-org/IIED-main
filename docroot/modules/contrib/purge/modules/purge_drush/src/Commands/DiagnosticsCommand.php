<?php

namespace Drupal\purge_drush\Commands;

@trigger_error(__NAMESPACE__ . '\DiagnosticsCommand is deprecated in purge:8.x-3.6 and is removed from purge:2.0.0. Use \Drupal\purge\Drush\Commands\DiagnosticsCommand instead. See https://www.drupal.org/node/3565396', E_USER_DEPRECATED);

use Drupal\purge\Drush\Commands\DiagnosticsCommand as DiagnosticsCommandBase;

/**
 * Generate a diagnostic self-service report.
 *
 * Note: This code has moved to Purge Core, see the parent class.
 *
 * @deprecated in purge:8.x-3.6 and is removed from purge:2.0.0. Use
 *   \Drupal\purge\Drush\Commands\DiagnosticsCommand instead.
 * @see https://www.drupal.org/node/3565396
 */
class DiagnosticsCommand extends DiagnosticsCommandBase {}
