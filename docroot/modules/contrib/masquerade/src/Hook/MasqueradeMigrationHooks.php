<?php

declare(strict_types=1);

namespace Drupal\masquerade\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for masquerade migration support.
 */
class MasqueradeMigrationHooks {

  /**
   * Implements hook_migration_plugins_alter().
   *
   * Names of masquerade permissions changed between Drupal 7 and 8|9. Map the
   * permission names respectively.
   */
  #[Hook('migration_plugins_alter')]
  public function migrationPluginsAlter(array &$migrations): void {
    if (
      !empty($migrations['d7_user_role']['process']['permissions'][0]) &&
      $migrations['d7_user_role']['process']['permissions'][0]['plugin'] === 'static_map'
    ) {
      $migrations['d7_user_role']['process']['permissions'][0]['map']['masquerade as admin'] = 'masquerade as super user';
    }
  }

}
