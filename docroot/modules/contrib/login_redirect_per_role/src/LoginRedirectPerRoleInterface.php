<?php

namespace Drupal\login_redirect_per_role;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;

/**
 * Interface defining Login And Logout Redirect Per Role helper service.
 */
interface LoginRedirectPerRoleInterface {

  /**
   * Config key for Login configuration.
   */
  const CONFIG_KEY_LOGIN = 'login';

  /**
   * Config key for Logout configuration.
   */
  const CONFIG_KEY_LOGOUT = 'logout';

  /**
   * Checks is login redirect action applicable on current page.
   *
   * @return bool
   *   Result of check.
   */
  public function isApplicableOnCurrentPage(): bool;

  /**
   * Return URL to redirect on user login.
   *
   * @return \Drupal\Core\Url|null
   *   URL to redirect to on success or NULL otherwise.
   */
  public function getLoginRedirectUrl(): ?Url;

  /**
   * Set Login destination parameter to do redirect.
   *
   * @param \Drupal\Core\Session\AccountInterface|null $account
   *   User account to set destination for.
   */
  public function setLoginDestination(?AccountInterface $account = NULL): void;

  /**
   * Return URL to redirect on user logout.
   *
   * @return \Drupal\Core\Url|null
   *   URL to redirect to on success or NULL otherwise.
   */
  public function getLogoutRedirectUrl(): ?Url;

  /**
   * Set Logout destination parameter to do redirect.
   *
   * @param \Drupal\Core\Session\AccountInterface|null $account
   *   User account to set destination for.
   */
  public function setLogoutDestination(?AccountInterface $account = NULL): void;

  /**
   * Return logout configuration.
   *
   * @return array
   *   Logout configuration on success or an empty array otherwise.
   */
  public function getLogoutConfig(): array;

  /**
   * Return login configuration.
   *
   * @return array
   *   Login configuration on success or an empty array otherwise.
   */
  public function getLoginConfig(): array;

}
