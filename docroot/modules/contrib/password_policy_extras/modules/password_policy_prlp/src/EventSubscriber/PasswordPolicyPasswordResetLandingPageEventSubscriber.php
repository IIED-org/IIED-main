<?php

namespace Drupal\password_policy_prlp\EventSubscriber;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\password_policy\PasswordPolicyValidator;
use Drupal\password_policy_extras\Event\CheckValidationEvent;
use Drupal\password_policy_extras\Event\CheckVisibilityEvent;
use Drupal\password_policy_extras\EventSubscriber\PasswordPolicyExtrasEventSubscriber;
use Drupal\password_policy_extras\PasswordPolicyExtrasEvents;
use Drupal\prlp\Event\PrlpPasswordBeforeSaveEvent;
use Drupal\prlp\Event\PrlpPasswordValidateEvent;
use Drupal\prlp\PrlpEvents;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Password Policy User Registration Password events handling.
 */
class PasswordPolicyPasswordResetLandingPageEventSubscriber extends PasswordPolicyExtrasEventSubscriber {

  use StringTranslationTrait;

  /**
   * Current request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected RequestStack $requestStack;

  /**
   * The Password Policy Validator service.
   *
   * @var \Drupal\password_policy\PasswordPolicyValidator
   */
  protected PasswordPolicyValidator $passwordPolicyValidator;

  /**
   * The Drupal Date Formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected DateFormatter $dateFormatter;

  /**
   * The Drupal Time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected TimeInterface $time;

  /**
   * User Registration Password configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected ImmutableConfig $prlpSettings;

  /**
   * The Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityManager;

  /**
   * PasswordPolicyEventSubscriber constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The currently logged-in user.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The current request stack.
   * @param \Drupal\Core\Datetime\DateFormatter $date_formatter
   *   The Drupal Date Formatter service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The Drupal Time service.
   * @param \Drupal\password_policy\PasswordPolicyValidator $password_policy_validator
   *   The Password Policy Validator service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   The entity type manager.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    AccountProxyInterface $current_user,
    RouteMatchInterface $route_match,
    RequestStack $request_stack,
    DateFormatter $date_formatter,
    TimeInterface $time,
    PasswordPolicyValidator $password_policy_validator,
    EntityTypeManagerInterface $entity_manager,
  ) {
    parent::__construct($config_factory, $current_user, $route_match);
    $this->requestStack = $request_stack;
    $this->dateFormatter = $date_formatter;
    $this->time = $time;
    $this->passwordPolicyValidator = $password_policy_validator;
    $this->prlpSettings = $config_factory->get('prlp.settings');
    $this->entityManager = $entity_manager;
  }

  /**
   * Event callback to check it password status table should be visible.
   */
  public function skipVisibility(CheckVisibilityEvent $event): void {

    $route_name = $this->routeMatch->getRouteName();

    if (in_array($route_name, ['user.reset.form'])) {

      $visibility_params = &$event->getParameters();

      $visibility_params['verify_email_before_password'] = FALSE;
      $visibility_params['is_route_without_password'] = FALSE;

      $uid = $this->routeMatch->getParameter('uid');
      $user = $this->entityManager->getStorage('user')->load($uid);
      $user_roles = $user->getRoles();
      $visibility_params['user_roles'] = $user_roles;

    }
  }

  /**
   * Event callback to check if password should be validated.
   */
  public function skipValidation(CheckValidationEvent $event): void {

    $validation_params = &$event->getParameters();

    if (!empty($this->prlpSettings)) {
      $required = $this->prlpSettings->get('password_required');
    }
    else {
      $required = FALSE;
    }

    $validation_params['verify_email_before_password'] = !$required;
    $validation_params['is_route_without_password'] = !$required;

    $uid = $this->routeMatch->getParameter('uid');
    if (!empty($uid)) {
      $user = $this->entityManager->getStorage('user')->load($uid);
      $user_roles = $user->getRoles();
      $validation_params['user_roles'] = $user_roles;
    }
  }

  /**
   * Event callback to validate password on reset.
   */
  public function resetPasswordValidation(PrlpPasswordValidateEvent $event): void {
    $form_state = &$event->getFormState();
    $user = $event->getUser();
    $passwords = $this->requestStack->getCurrentRequest()->request->all('pass');
    $validation_report = $this->passwordPolicyValidator->validatePassword(
        reset($passwords),
        $user,
        $user->getRoles()
      );
    if ($validation_report->hasErrors()) {
      $form_state->setErrorByName('pass2', $validation_report->getErrors());
    }
  }

  /**
   * Event callback to reset the password policy expiration data.
   *
   * @param \Drupal\prlp\Event\PrlpPasswordBeforeSaveEvent $event
   *   The ResetPasswordUpdateEvent event to get the user from.
   */
  public function resetPasswordUpdate(PrlpPasswordBeforeSaveEvent $event) {
    $user = &$event->getUser();
    $date = $this->dateFormatter->format(
      $this->time->getRequestTime(),
      'custom',
      DateTimeItemInterface::DATETIME_STORAGE_FORMAT,
      DateTimeItemInterface::STORAGE_TIMEZONE);
    $user->set('field_last_password_reset', $date);
    $user->set('field_password_expiration', '0');
    $user->set('field_pending_expire_sent', '0');
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
    $events[PrlpEvents::PASSWORD_VALIDATE][] =
      ['resetPasswordValidation', 800];
    $events[PrlpEvents::PASSWORD_BEFORE_SAVE][] =
      ['resetPasswordUpdate', 800];
    return $events;
  }

}
