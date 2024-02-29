<?php

namespace Drupal\tfa\Plugin;

/**
 * Interface TfaLoginInterface.
 *
 * Login plugins interact with the Tfa loginAllowed() process prior to starting
 * a TFA process.
 */
interface TfaLoginInterface {

  /**
   * Whether login is allowed.
   *
   * @return bool
   *   Whether login is allowed.
   */
  public function loginAllowed();

}
