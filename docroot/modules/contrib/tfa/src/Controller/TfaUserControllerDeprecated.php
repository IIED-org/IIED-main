<?php

namespace Drupal\tfa\Controller;

/**
 * Provide controller routines for user routes.
 *
 * Used  only on Drupal 9.2 and   older.
 */
class TfaUserControllerDeprecated extends TfaUserControllerBase {

  /**
   * {@inheritdoc}
   */
  public function resetPassLogin($uid, $timestamp, $hash) {
    return parent::doResetPassLogin($uid, $timestamp, $hash, NULL);
  }

}
