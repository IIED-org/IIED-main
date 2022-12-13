<?php

namespace Drupal\acquia_search\Helper;

use Drupal\search_api\ServerInterface;

/**
 * Static Class Helpers for Acquia Search.
 *
 * Contains various helpers that don't fit neatly inside a service.
 */
class Runtime {

  /**
   * Determine if we should enforce read-only mode.
   *
   * @return bool
   *   TRUE if we should enforce read-only mode.
   */
  public static function shouldEnforceReadOnlyMode(): bool {
    $read_only = FALSE;
    // Check if the read-only mode is forced in configuration.
    if (!empty(\Drupal::config('acquia_search.settings')->get('read_only'))) {
      $read_only = TRUE;
    }
    \Drupal::moduleHandler()->alter('acquia_search_should_enforce_read_only', $read_only);
    return $read_only;
  }

  /**
   * Determine whether given server belongs to an Acquia search server.
   *
   * @param \Drupal\search_api\ServerInterface $server
   *   A search server configuration entity.
   *
   * @return bool
   *   TRUE if given server config belongs to an Acquia search server.
   */
  public static function isAcquiaServer(ServerInterface $server): bool {
    $backend_config = $server->getBackendConfig();
    return !empty($backend_config['connector']) && $backend_config['connector'] === 'solr_acquia_connector';
  }

}
