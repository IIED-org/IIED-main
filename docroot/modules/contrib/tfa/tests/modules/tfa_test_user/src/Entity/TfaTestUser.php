<?php

declare(strict_types=1);

namespace Drupal\tfa_test_user\Entity;

use Drupal\user\Entity\User;
use Drupal\user\UserInterface;

/**
 * Test user for TFA.
 */
final class TfaTestUser extends User implements UserInterface {

  /**
   * {@inheritdoc}
   */
  public function id(): ?int {
    $id = parent::id();
    return $id !== NULL ? (int) $id : NULL;
  }

}
