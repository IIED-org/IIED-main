<?php

namespace Drupal\tfa;

use Drupal\Component\Utility\Crypt;
use Drupal\user\UserInterface;

/**
 * Provides methods for logging in users.
 */
trait TfaLoginTrait {

  /**
   * Generate a hash that can uniquely identify an account's state.
   *
   * @param \Drupal\user\UserInterface $account
   *   The user account for which a hash is required.
   *
   * @return string
   *   The hash value representing the user.
   */
  protected function getLoginHash(UserInterface $account) {
    // Using account login will mean this hash will become invalid once user has
    // authenticated via TFA.
    $data = implode(':', [
      $account->getAccountName(),
      $account->getPassword(),
      $account->getLastLoginTime(),
    ]);
    return Crypt::hashBase64($data);
  }

}
