<?php

namespace Drupal\tfa\Plugin;

use Drupal\Core\Form\FormStateInterface;

/**
 * Interface TfaValidationInterface.
 *
 * Validation plugins interact with the Tfa form processes to provide code entry
 * and validate submitted codes.
 */
interface TfaValidationInterface {

  /**
   * Get TFA process form from plugin.
   *
   * @param array $form
   *   The configuration form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   Form API array.
   */
  public function getForm(array $form, FormStateInterface $form_state);

  /**
   * Validate form.
   *
   * @param array $form
   *   The configuration form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return bool
   *   Whether form passes validation or not
   */
  public function validateForm(array $form, FormStateInterface $form_state);

  /**
   * Check whether the user has setup Tfa for this validation plugin.
   *
   * @return bool
   *   Whether or not the user has setup this validation plugin.
   */
  public function ready();

}
