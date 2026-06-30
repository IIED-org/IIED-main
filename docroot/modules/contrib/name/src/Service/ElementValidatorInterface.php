<?php

declare(strict_types=1);

namespace Drupal\name\Service;

use Drupal\Core\Form\FormStateInterface;

/**
 * Validates name element minimum components and required state.
 *
 * @internal
 */
interface ElementValidatorInterface {

  /**
   * Validates a name form element.
   */
  public function validate(array $element, FormStateInterface $form_state): array;

}
