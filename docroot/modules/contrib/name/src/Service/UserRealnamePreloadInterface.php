<?php

declare(strict_types=1);

namespace Drupal\name\Service;

use Drupal\Core\Session\AccountInterface;

/**
 * Loads full user when user_format_name_alter needs preferred name field data.
 *
 * @internal
 */
interface UserRealnamePreloadInterface {

  /**
   * Preloads user entity where configured; mirrors legacy global behavior.
   *
   * Recursion check in place after RealName module issue queue suggested that
   * there were issues with token based recursion on load.
   */
  public function preload(AccountInterface $account): void;

}
