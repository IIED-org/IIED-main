<?php

namespace Drupal\isbn\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates if strings are valid ISBN numbers.
 */
class IsbnConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    try {
      if (gettype($value) == 'string') {
        if (!\Drupal::service('isbn.isbn_service')->isValidIsbn($value)) {
          $this->context->addViolation(t('"%isbn" isn\'t a valid ISBN number.', ['%isbn' => $value]));
        }
      }
    }
    catch (\RuntimeException $e) {
      watchdog_exception('isbn', $e);
      $this->context->addViolation(t('An error occurred while trying to validate the ISBN number. Refer to the logs for more info.'));
    }
  }

}
