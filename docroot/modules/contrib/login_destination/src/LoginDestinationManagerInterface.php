<?php

namespace Drupal\login_destination;

use Drupal\Core\Session\AccountInterface;
use Drupal\login_destination\Entity\LoginDestination;

/**
 * Defines a login destination manager service interface.
 */
interface LoginDestinationManagerInterface {

  /**
   * Find destination.
   *
   * @param string $trigger
   *   Trigger.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   User account.
   *
   * @return bool|\Drupal\login_destination\Entity\LoginDestination
   *   Returns either FALSE or a LoginDestination object.
   */
  public function findDestination($trigger, AccountInterface $account);

  /**
   * Prepare request destination provided by login destination rule.
   *
   * @param \Drupal\login_destination\Entity\LoginDestination $destination
   *   Login destination rule.
   */
  public function prepareDestination(LoginDestination $destination);

}
