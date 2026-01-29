<?php

namespace Drupal\login_redirect_per_role;

use Drupal\Component\Utility\SortArray;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Drupal\Core\Utility\Token;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Login And Logout Redirect Per Role helper service.
 */
class LoginRedirectPerRole implements LoginRedirectPerRoleInterface {

  /**
   * The currently active request object.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $currentRequest;

  /**
   * The login_redirect_per_role.settings config object.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * Constructs a new Login And Logout Redirect Per Role service object.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $currentRouteMatch
   *   The currently active route match object.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current active user.
   * @param \Drupal\Core\Utility\Token $token
   *   The token service.
   */
  public function __construct(
    private readonly RouteMatchInterface $currentRouteMatch,
    RequestStack $requestStack,
    ConfigFactoryInterface $configFactory,
    private readonly AccountProxyInterface $currentUser,
    private readonly Token $token,
  ) {
    $this->currentRequest = $requestStack->getCurrentRequest();
    $this->config = $configFactory->get('login_redirect_per_role.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function isApplicableOnCurrentPage(): bool {
    return match ($this->currentRouteMatch->getRouteName()) {
      'user.reset',
      'user.reset.form',
      'user.reset.login',
      'commerce_checkout.form',
      'change_pwd_page.reset' => FALSE,
      'tfa.entry' => !$this->currentRequest->query->get('pass-reset-token'),
      default => TRUE,
    };
  }

  /**
   * {@inheritdoc}
   */
  public function getLoginRedirectUrl(): ?Url {
    return $this->getRedirectUrl(LoginRedirectPerRoleInterface::CONFIG_KEY_LOGIN);
  }

  /**
   * {@inheritdoc}
   */
  public function setLoginDestination(?AccountInterface $account = NULL): void {
    $this->setDestination(LoginRedirectPerRoleInterface::CONFIG_KEY_LOGIN, $account);
  }

  /**
   * {@inheritdoc}
   */
  public function getLogoutRedirectUrl(): ?Url {
    return $this->getRedirectUrl(LoginRedirectPerRoleInterface::CONFIG_KEY_LOGOUT);
  }

  /**
   * {@inheritdoc}
   */
  public function setLogoutDestination(?AccountInterface $account = NULL): void {
    $this->setDestination(LoginRedirectPerRoleInterface::CONFIG_KEY_LOGOUT, $account);
  }

  /**
   * {@inheritdoc}
   */
  public function getLogoutConfig(): array {
    return $this->getConfig(LoginRedirectPerRoleInterface::CONFIG_KEY_LOGOUT);
  }

  /**
   * {@inheritdoc}
   */
  public function getLoginConfig(): array {
    return $this->getConfig(LoginRedirectPerRoleInterface::CONFIG_KEY_LOGIN);
  }

  /**
   * Set "destination" parameter to do redirect.
   *
   * @param string $key
   *   Configuration key (login or logout).
   * @param \Drupal\Core\Session\AccountInterface|null $account
   *   User account to set destination for.
   */
  protected function setDestination(string $key, ?AccountInterface $account = NULL): void {
    $url = $this->getRedirectUrl($key, $account);

    if ($url instanceof Url) {
      $this->currentRequest->query->set('destination', $url->toString());
    }
  }

  /**
   * Return redirect URL related to requested key and current user.
   *
   * @param string $key
   *   Configuration key (login or logout).
   * @param \Drupal\Core\Session\AccountInterface|null $account
   *   User account to get redirect URL for.
   *
   * @return \Drupal\Core\Url|null
   *   Redirect URL related to requested key and current user.
   */
  protected function getRedirectUrl(string $key, ?AccountInterface $account = NULL): ?Url {
    if ($key === LoginRedirectPerRoleInterface::CONFIG_KEY_LOGIN && !$this->isApplicableOnCurrentPage()) {
      return NULL;
    }

    $config = $this->getConfig($key);
    if (!$config) {
      return NULL;
    }

    $url = NULL;
    $user_roles = $this->getUserRoles($account);
    $destination = $this->currentRequest->query->get('destination');

    foreach ($config as $role_id => $settings) {
      // Do action only if user have a role and
      // "Redirect URL" is set for this role.
      if (in_array($role_id, $user_roles) && $settings['redirect_url']) {

        // Prevent redirect if destination usage is allowed.
        if ($settings['allow_destination'] && $destination) {
          break;
        }

        if ($settings['redirect_url'] === '<front>') {
          $url = Url::fromRoute($settings['redirect_url']);
          break;
        }

        $path = $this->token->replace($settings['redirect_url']);
        $url = Url::fromUserInput($this->stripSubdirectoryFromPath($path));
        break;
      }
    }

    return $url;
  }

  /**
   * Return requested configuration items (login or logout) ordered by weight.
   *
   * @param string $key
   *   Configuration key (login or logout).
   *
   * @return array
   *   Requested configuration items (login or logout) ordered by weight.
   */
  protected function getConfig($key): array {
    $config = $this->config->get($key);

    if ($config) {
      uasort($config, [SortArray::class, 'sortByWeightElement']);

      return $config;
    }

    return [];
  }

  /**
   * Return user roles list from given account or from current user.
   *
   * @param \Drupal\Core\Session\AccountInterface|null $account
   *   User account to get roles.
   *
   * @return array
   *   Roles list.
   */
  protected function getUserRoles(?AccountInterface $account = NULL): array {
    if ($account instanceof AccountInterface) {
      $user_roles = $account->getRoles();
    }
    else {
      $user_roles = $this->currentUser->getRoles();
    }

    return $user_roles;
  }

  /**
   * Strips subdirectories from a URI.
   *
   * URIs created by \Drupal\Core\Url::toString() always contain the
   * subdirectories. When further processing needs to be done on a URI, the
   * subdirectories need to be stripped before feeding the URI to
   * \Drupal\Core\Url::fromUserInput().
   *
   * @param string $uri
   *   A plain-text URI that might contain a subdirectory.
   *
   * @return string
   *   A plain-text URI stripped of the subdirectories.
   */
  public function stripSubdirectoryFromPath(string $uri): string {
    if (!empty($this->currentRequest->getBasePath()) && strpos($uri, $this->currentRequest->getBasePath()) === 0) {
      return substr($uri, mb_strlen($this->currentRequest->getBasePath()));
    }
    return $uri;
  }

}
