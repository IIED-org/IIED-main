<?php

namespace Drupal\login_redirect_per_role_test\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Contains callbacks for test routes.
 */
class LoginRedirectPerRoleTestController extends ControllerBase {

  /**
   * Callback for route login_redirect_per_role_test.valid_path.
   */
  public function validPath(): array {
    return [
      '#markup' => 'Valid path',
    ];
  }

  /**
   * Callback for route login_redirect_per_role_test.invalid_path.
   */
  public function invalidPath(): array {
    return [
      '#markup' => 'Invalid path',
    ];
  }

  /**
   * Callback for route login_redirect_per_role_test.login_url_role1.
   */
  public function loginUrlRole1(): array {
    return [
      '#markup' => 'Login URL Role 1',
    ];
  }

  /**
   * Callback for route login_redirect_per_role_test.logout_url_role1.
   */
  public function logoutUrlRole1(): array {
    return [
      '#markup' => 'Logout URL Role 1',
    ];
  }

  /**
   * Callback for route login_redirect_per_role_test.login_url_role2.
   */
  public function loginUrlRole2(): array {
    return [
      '#markup' => 'Login URL Role 2',
    ];
  }

  /**
   * Callback for route login_redirect_per_role_test.logout_url_role2.
   */
  public function logoutUrlRole2(): array {
    return [
      '#markup' => 'Logout URL Role 2',
    ];
  }

  /**
   * Callback for route login_redirect_per_role_test.destination_url.
   */
  public function destinationUrl(): array {
    return [
      '#markup' => 'Destination URL',
    ];
  }

}
