<?php

namespace Drupal\password_policy_extras;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\password_policy\PasswordPolicyValidationManager;
use Drupal\password_policy_extras\Event\CheckValidationEvent;
use Drupal\password_policy_extras\Event\CheckVisibilityEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class PasswordPolicyExtrasValidationManager.
 *
 * Decide whether to display validation and whether to validate a password.
 *
 * @package Drupal\password_policy_extras
 */
class PasswordPolicyExtrasValidationManager extends PasswordPolicyValidationManager {

  /**
   * Event dispatcher service.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected EventDispatcherInterface $eventDispatcher;

  /**
   * PasswordPolicyVisibilityManager constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current logged-in user.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   The current route match.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher
   *   The current route match.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(ConfigFactoryInterface $configFactory, AccountProxyInterface $currentUser, EntityTypeManagerInterface $entityTypeManager, RouteMatchInterface $routeMatch, EventDispatcherInterface $eventDispatcher) {
    parent::__construct($configFactory, $currentUser, $entityTypeManager, $routeMatch);
    $this->eventDispatcher = $eventDispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public function tableShouldBeVisible() {

    // Will be populated by the CHECK_VISIBILITY event listeners.
    $visibility_params = [];

    $event = new CheckVisibilityEvent($visibility_params);
    $this->eventDispatcher->dispatch(
      $event, PasswordPolicyExtrasEvents::CHECK_VISIBILITY);

    if ($this->currentUser->isAnonymous()
      && ($visibility_params['verify_email_before_password'] ?? TRUE)
      && ($visibility_params['is_route_without_password'] ?? TRUE)) {
      return FALSE;
    }

    $role_applies = $this->passwordPolicyStorage->getQuery()
      ->condition('roles.*', $visibility_params['user_roles'], 'IN')
      ->condition('show_policy_table', TRUE)
      ->accessCheck(FALSE)
      ->execute();

    return !empty($role_applies);
  }

  /**
   * {@inheritdoc}
   */
  public function validationShouldRun() {

    // Will be populated by the CHECK_VALIDATION event listeners.
    $validation_params = [];

    $event = new CheckValidationEvent($validation_params);
    $this->eventDispatcher->dispatch(
      $event, PasswordPolicyExtrasEvents::CHECK_VALIDATION);

    if ($this->currentUser->isAnonymous()
      && ($validation_params['verify_email_before_password'] ?? TRUE)
      && ($validation_params['is_route_without_password'] ?? TRUE)) {
      return FALSE;
    }

    $role_applies = $this->passwordPolicyStorage->getQuery()
      ->condition('roles.*', $validation_params['user_roles'], 'IN')
      ->accessCheck(FALSE)
      ->execute();

    return !empty($role_applies);
  }

}
