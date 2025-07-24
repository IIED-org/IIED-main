<?php

namespace Drupal\tfa;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Site\Settings;
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
    $key = \Drupal::service('private_key')->get() . Settings::get('hash_salt');
    return Crypt::hmacBase64($data, $key);
  }

}
