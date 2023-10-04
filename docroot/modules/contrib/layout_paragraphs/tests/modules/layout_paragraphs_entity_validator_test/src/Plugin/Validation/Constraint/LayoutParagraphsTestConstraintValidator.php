<?php

namespace Drupal\layout_paragraphs_entity_validator_test\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the TestConstraint constraint.
 */
class LayoutParagraphsTestConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    $this->context->addViolation($constraint->message);
  }

}
