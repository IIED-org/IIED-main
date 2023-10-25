<?php

namespace Drupal\layout_paragraphs_entity_validator_test\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks that the submitted value is a unique integer.
 *
 * @Constraint(
 *   id = "LayoutParagraphsTestConstraint",
 *   label = @Translation("Layout Paragraphs Test Constraint", context = "Validation"),
 *   type = "string"
 * )
 */
class LayoutParagraphsTestConstraint extends Constraint {

  public $message = 'Failed Layout Paragraphs test validation.';

}
