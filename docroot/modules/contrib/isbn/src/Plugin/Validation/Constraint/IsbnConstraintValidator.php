<?php

namespace Drupal\isbn\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class IsbnConstraintValidator extends ConstraintValidator {

  public function validate($value, Constraint $constraint) {

    if (gettype($value) == 'string') {
      $isbn_tools = \Drupal::service("isbn.isbn_service");
      if (!$isbn_tools->isValidIsbn($value)) {
        $this->context->addViolation(t('"%isbn" isn\'t a valid ISBN number.', ['%isbn' => $value]));
      }
    }
  }

}
