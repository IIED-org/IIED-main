<?php

namespace Drupal\tfa\Controller;

use Drupal\Component\Utility\Crypt;
use Drupal\tfa\TfaLoginContextTrait;
use Drupal\tfa\TfaLoginTrait;
use Drupal\user\Controller\UserController;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Provide controller routines for user routes.
 */
abstract class TfaUserControllerBase extends UserController {
  use TfaLoginContextTrait;
  use TfaLoginTrait;

  /**
   * The current session.
   *
   * @var \Symfony\Component\HttpFoundation\Session\SessionInterface
   */
  protected $session;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);

    $instance->tfaValidationManager = $container->get('plugin.manager.tfa.validation');
    $instance->tfaLoginManager = $container->get('plugin.manager.tfa.login');
    $instance->tfaSettings = $container->get('config.factory')->get('tfa.settings');
    $instance->privateTempStore = $container->get('tempstore.private')->get('tfa');
    $instance->session = $container->get('session');

    return $instance;
  }

  /**
   * Validates user, hash, and timestamp; logs the user in if correct.
   *
   * @param int $uid
   *   User ID of the user requesting reset.
   * @param int $timestamp
   *   The current timestamp.
   * @param string $hash
   *   Login link hash.
   * @param \Symfony\Component\HttpFoundation\Request|null $request
   *   The request.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Returns a redirect to the user edit form if the information is correct.
   *   If the information is incorrect redirects to 'user.pass' route with a
   *   message for the user.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *   If $uid is for a blocked user or invalid user ID.
   */
  protected function doResetPassLogin($uid, $timestamp, $hash, $request = NULL) {
    /** @var \Drupal\user\UserInterface $user */
    $user = $this->userStorage->load($uid);
    $this->setUser($user);

    // Let Drupal core deal with the one time login,
    // if Tfa is not enabled
    // or current user can skip TFA while resetting password.
    if ($this->isTfaDisabled() || $this->canResetPassSkip()) {
      // Let the Drupal core to validate the one time login.
      if ($request) {
        return parent::resetPassLogin($uid, $timestamp, $hash, $request);
      }
      else {
        return parent::resetPassLogin($uid, $timestamp, $hash);
      }
    }
    else {
      // Whether the TFA Validation Plugin is set and ready for use.
      $tfa_ready = $this->isReady();
      // Check for authentication plugin.
      if ($tfa_ready && $this->pluginAllowsLogin()) {
        // A trused browser or at least one plugin allows authentication.
        $this->messenger()->addStatus($this->t('You have logged in on a trusted browser.'));
        // Let the Drupal core to validate the one time login.
        if ($request) {
          return parent::resetPassLogin($uid, $timestamp, $hash, $request);
        }
        else {
          return parent::resetPassLogin($uid, $timestamp, $hash);
        }
      }

      // Drupal doesn't provide a hook or event
      // in which TFA can implement the TFA validation,
      // before the user_login_finalize() is called.
      // @see https://git.drupalcode.org/project/drupal/-/blob/9.4.6/core/modules/user/src/Controller/UserController.php#L245
      // So borrow following codes from the core
      // \Drupal\user\Controller\UserController::resetPassLogin(),
      $current = \Drupal::time()->getRequestTime();

      // Verify that the user exists and is active.
      if ($user === NULL || !$user->isActive()) {
        // Blocked or invalid user ID, so deny access. The parameters will be in
        // the watchdog's URL for the administrator to check.
        throw new AccessDeniedHttpException();
      }

      // Time out, in seconds, until login URL expires.
      $timeout = $this->config('user.settings')->get('password_reset_timeout');
      // No time out for first time login.
      if ($user->getLastLoginTime() && $current - $timestamp > $timeout) {
        $this->messenger()->addError($this->t('You have tried to use a one-time login link that has expired. Please request a new one using the form below.'));
        return $this->redirect('user.pass');
      }
      elseif ($user->isAuthenticated() && ($timestamp >= $user->getLastLoginTime()) && ($timestamp <= $current) && hash_equals($hash, user_pass_rehash($user, $timestamp))) {
        // The one time login has been validated.
        // Check if TFA is set up for this user.
        if ($tfa_ready) {
          // Regenerate the session ID to prevent session fixation attacks.
          $this->session->migrate();

          // TFA is set up.
          // Let the user's password be changed without the current password
          // check.
          $token = Crypt::randomBytesBase64(55);
          if ($request) {
            $request->getSession()->set('pass_reset_' . $uid, $token);
          }
          else {
            // Prior to D9.3 core used the global $_SESSION.
            $_SESSION['pass_reset_' . $uid] = $token;
          }

          // Begin TFA and set process context.
          // Log the one-time login link attempts.
          $this->logger->notice('User %name used one-time login link at time %timestamp.', [
            '%name' => $user->getDisplayName(),
            '%timestamp' => $timestamp,
          ]);
          // Store UID in order to later verify access to entry form.
          $this->tempStoreUid($user->id());
          // Redirect to TFA entry form.
          return $this->redirect('tfa.entry', [
            'uid' => $uid,
            'hash' => $this->getLoginHash($user),
          ], [
            'query' => ['pass-reset-token' => $token],
            'absolute' => TRUE,
          ]);
        }
        else {
          // TFA is not set up yet.
          // User may be able to skip TFA,
          // depending on module settings and number of
          // prior attempts.
          if ($this->canLoginWithoutTfa($this->getLogger('tfa'))) {
            // User can login without TFA.
            // Redirect to user edit form.
            return $this->redirectToUserForm($user, $request, $timestamp);
          }
          else {
            // TFA validation failed.
            // Redirect to the home page.
            return $this->redirect('<front>');
          }
        }
      }
    }

    if ($request) {
      return parent::resetPassLogin($uid, $timestamp, $hash, $request);
    }
    else {
      return parent::resetPassLogin($uid, $timestamp, $hash);
    }
  }

  /**
   * Redirect to user edit form.
   *
   * @param \Drupal\user\UserInterface $user
   *   Current user.
   * @param \Symfony\Component\HttpFoundation\Request|null $request
   *   Controller request.
   * @param int $timestamp
   *   The current timestamp.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Recirect response to user eidt form.
   */
  protected function redirectToUserForm(UserInterface $user, $request, $timestamp) {
    user_login_finalize($user);
    $this->logger->notice('User %name used one-time login link at time %timestamp.', [
      '%name' => $user->getDisplayName(),
      '%timestamp' => $timestamp,
    ]);
    $this->messenger()->addStatus($this->t('You have just used your one-time login link. It is no longer necessary to use this link to log in. Please change your password.'));
    // Let the user's password be changed without the current password
    // check.
    $token = Crypt::randomBytesBase64(55);
    if ($request) {
      $request->getSession()->set('pass_reset_' . $user->id(), $token);
    }
    else {
      // Prior to D9.3 core used the global $_SESSION.
      $_SESSION['pass_reset_' . $user->id()] = $token;
    }
    // Clear any flood events for this user.
    $this->flood->clear('user.password_request_user', $user->id());

    return $this->redirect('entity.user.edit_form', ['user' => $user->id()], [
      'query' => ['pass-reset-token' => $token],
      'absolute' => TRUE,
    ]);
  }

}
