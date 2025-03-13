<?php

namespace Drupal\password_policy_user_registrationpassword\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\password_policy_extras\Event\CheckValidationEvent;
use Drupal\password_policy_extras\Event\CheckVisibilityEvent;
use Drupal\password_policy_extras\EventSubscriber\PasswordPolicyExtrasEventSubscriber;
use Drupal\password_policy_extras\PasswordPolicyExtrasEvents;

/**
 * Password Policy User Registration Password events handling.
 */
class PasswordPolicyUserPasswordRegistrationEventSubscriber extends PasswordPolicyExtrasEventSubscriber {

  use StringTranslationTrait;

  /**
   * User Registration Password configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected ImmutableConfig $urpSettings;

  /**
   * PasswordPolicyEventSubscriber constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The currently logged-in user.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   */
  public function __construct(ConfigFactoryInterface $config_factory, AccountProxyInterface $current_user, RouteMatchInterface $route_match) {
    parent::__construct($config_factory, $current_user, $route_match);
    $this->urpSettings = $config_factory->get('user_registrationpassword.settings');
  }

  /**
   * Event callback to check it password status table should be visible.
   */
  public function skipVisibility(CheckVisibilityEvent $event): void {

    $visibility_params = &$event->getParameters();

    $user_roles = $visibility_params['user_roles'];

    if (!empty($this->urpSettings)) {
      $registration = $this->urpSettings->get('registration');
      $verify_email_before_password = ($registration === 'default');
      if (!$verify_email_before_password && $this->currentUser->isAnonymous()) {
        if (!in_array('authenticated', $user_roles)) {
          // Before a user has registered all they have is the anonymous role,
          // which can't be targeted by a password policy rule. So also search
          // for the authenticated role, which every user will have post
          // register.
          $user_roles[] = 'authenticated';
        }
      }
    }

    $visibility_params['verify_email_before_password'] = $verify_email_before_password;
    $visibility_params['user_roles'] = $user_roles;
  }

  /**
   * Event callback to check if password should be validated.
   */
  public function skipValidation(CheckValidationEvent $event): void {

    $validation_params = &$event->getParameters();

    if (!empty($this->urpSettings)) {
      $registration = $this->urpSettings->get('registration');
      $verify_email_before_password = ($registration === 'default');
    }

    $validation_params['verify_email_before_password'] = $verify_email_before_password;
  }

  /**
   * Registers the methods in this class that should be listeners.
   *
   * @return array
   *   An array of event listener definitions.
   */
  public static function getSubscribedEvents(): array {
    $events[PasswordPolicyExtrasEvents::CHECK_VISIBILITY][] =
      ['skipVisibility', 900];
    $events[PasswordPolicyExtrasEvents::CHECK_VALIDATION][] =
      ['skipValidation', 900];
    return $events;
  }

}
