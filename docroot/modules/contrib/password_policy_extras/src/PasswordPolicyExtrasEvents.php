<?php

namespace Drupal\password_policy_extras;

/**
 * Defines events provided by the Password Policy Extras module.
 */
final class PasswordPolicyExtrasEvents {

  /**
   * Dispatches to check if password status table should be made visible.
   *
   * @Event
   *
   * @var string
   */
  public const CHECK_VISIBILITY = 'password_policy_extras.skip_visibility';

  /**
   * Dispatches when the password field have to be validated.
   *
   * @Event
   *
   * @var string
   */
  public const CHECK_VALIDATION = 'password_policy_extras.skip_validation';

}
