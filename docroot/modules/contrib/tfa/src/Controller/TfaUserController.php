<?php

namespace Drupal\tfa\Controller;

use Symfony\Component\HttpFoundation\Request;

/**
 * Provide controller routines for user routes.
 */
class TfaUserController extends TfaUserControllerBase {

  /**
   * {@inheritdoc}
   */
  public function resetPassLogin($uid, $timestamp, $hash, Request $request) {
    return parent::doResetPassLogin($uid, $timestamp, $hash, $request);
  }

}
