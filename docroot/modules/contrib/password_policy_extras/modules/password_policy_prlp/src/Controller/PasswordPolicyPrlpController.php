<?php

namespace Drupal\password_policy_prlp\Controller;

use Drupal\user\Controller\UserController;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller routines for password policy prlp routes.
 */
class PasswordPolicyPrlpController extends UserController {

  /**
   * {@inheritDoc}
   */
  public function getResetPassForm(Request $request, $uid) {

    $session = $request->getSession();
    /* In case of Ajax request, get hash and timeout from hidden fields. */
    if ($request->isXmlHttpRequest()) {
      $hash = $request->request->get('pass_reset_hash');
      $timestamp = $request->request->get('pass_reset_timeout');
      $session->set('pass_reset_hash', $hash);
      $session->set('pass_reset_timeout', $timestamp);
    }
    else {
      $timestamp = $session->get('pass_reset_timeout');
      $hash = $session->get('pass_reset_hash');
    }

    try {
      $form = parent::getResetPassForm($request, $uid);
      $form['pass_reset_hash'] = [
        '#type' => 'hidden',
        '#name' => 'pass_reset_hash',
        '#value' => $hash,
      ];
      $form['pass_reset_timeout'] = [
        '#type' => 'hidden',
        '#name' => 'pass_reset_timeout',
        '#value' => $timestamp,
      ];
    }
    finally {
      /* For Ajax debugging purpose. */
    }

    return $form;
  }

}
