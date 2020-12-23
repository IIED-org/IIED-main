<?php

namespace Drupal\eu_cookie_compliance\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Class AdminController.
 */
class AdminController extends ControllerBase {

  /**
   * Access.
   *
   * @param AccountInterface $account
   *   The account.
   *
   * @return AccessResult
   *   Whether the user has access.
   */
  public function access(AccountInterface $account) {
    return AccessResult::allowedIf(
      $account->hasPermission('administer eu cookie compliance popup') ||
      $account->hasPermission('administer eu cookie compliance categories')
    );
  }

}
