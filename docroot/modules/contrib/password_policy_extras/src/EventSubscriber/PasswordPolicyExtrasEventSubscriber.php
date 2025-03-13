<?php

namespace Drupal\password_policy_extras\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\password_policy_extras\Event\CheckValidationEvent;
use Drupal\password_policy_extras\Event\CheckVisibilityEvent;
use Drupal\password_policy_extras\PasswordPolicyExtrasEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Password Policy Extras events handling.
 */
class PasswordPolicyExtrasEventSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * The currently logged-in user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected AccountProxyInterface $currentUser;

  /**
   * Config for user.settings.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected ImmutableConfig $userSettingsConfig;

  /**
   * Current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected RouteMatchInterface $routeMatch;

  /**
   * PasswordPolicyExtrasEventSubscriber constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The currently logged-in user.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   */
  public function __construct(ConfigFactoryInterface $config_factory, AccountProxyInterface $current_user, RouteMatchInterface $route_match) {
    $this->currentUser = $current_user;
    $this->routeMatch = $route_match;
    $this->userSettingsConfig = $config_factory->get('user.settings');
  }

  /**
   * Event callback to check it password status table should be visible.
   */
  public function skipVisibility(CheckVisibilityEvent $event): void {

    $visibility_params = &$event->getParameters();

    $verify_email_before_password = $this->userSettingsConfig->get('verify_mail');
    $is_route_without_password = ($this->routeMatch->getRouteName() !== 'user.reset');
    $user_roles = $this->currentUser->getRoles();

    $visibility_params = [
      'verify_email_before_password' => $verify_email_before_password,
      'is_route_without_password' => $is_route_without_password,
      'user_roles' => $user_roles,
    ];
  }

  /**
   * Event callback to check if password should be validated.
   */
  public function skipValidation(CheckValidationEvent $event): void {

    $validation_params = &$event->getParameters();

    $verify_email_before_password = $this->userSettingsConfig->get('verify_mail');
    $is_route_without_password = ($this->routeMatch->getRouteName() !== 'user.reset');
    $user_roles = $this->currentUser->getRoles();
    if (!in_array('authenticated', $user_roles)) {
      // Before a user has registered all they have is the anonymous role,
      // which can't be targeted by a password policy rule. So also search
      // for the authenticated role, which every user will have post register.
      $user_roles[] = 'authenticated';
    }

    $validation_params = [
      'verify_email_before_password' => $verify_email_before_password,
      'is_route_without_password' => $is_route_without_password,
      'user_roles' => $user_roles,
    ];
  }

  /**
   * Registers the methods in this class that should be listeners.
   *
   * @return array
   *   An array of event listener definitions.
   */
  public static function getSubscribedEvents(): array {
    $events[PasswordPolicyExtrasEvents::CHECK_VISIBILITY][] =
      ['skipVisibility', 1000];
    $events[PasswordPolicyExtrasEvents::CHECK_VALIDATION][] =
      ['skipValidation', 1000];
    return $events;
  }

}
