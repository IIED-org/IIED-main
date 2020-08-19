<?php

namespace Drupal\isbn\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Validation constraint for the ISBN field.
 *
 * @Constraint(
 *   id = "IsbnValidation",
 *   label = @Translation("ISBN provider constraint", context = "Validation"),
 * )
 */
class IsbnConstraint extends Constraint {

  /**
   * Message shown when an invalid ISBN number is provided.
   *
   * @var string
   */
  public $message = 'The value provided isn\'t a valid ISBN number.';
}
