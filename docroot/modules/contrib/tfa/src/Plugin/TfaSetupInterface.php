<?php

namespace Drupal\tfa\Plugin;

use Drupal\Core\Form\FormStateInterface;

/**
 * Interface TfaSetupInterface.
 *
 * Setup plugins are used by TfaSetup for configuring a plugin.
 *
 * Implementations of a begin plugin should also be a validation plugin.
 */
interface TfaSetupInterface {

  /**
   * Get the setup form for the validation method.
   *
   * @param array $form
   *   The configuration form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   Form API array.
   */
  public function getSetupForm(array $form, FormStateInterface $form_state);

  /**
   * Validate the setup data.
   *
   * @param array $form
   *   The configuration form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return bool
   *   Whether or not form passes validation.
   */
  public function validateSetupForm(array $form, FormStateInterface $form_state);

  /**
   * Submit the setup form.
   *
   * @param array $form
   *   The configuration form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return bool
   *   TRUE if no errors occur when saving the data.
   */
  public function submitSetupForm(array $form, FormStateInterface $form_state);

  /**
   * Returns a list of links containing helpful information for plugin use.
   *
   * @return string[]
   *   An array containing help links for e.g., OTP generation.
   */
  public function getHelpLinks();

  /**
   * Returns a list of messages for plugin step.
   *
   * @return string[]
   *   An array containing messages to be used during plugin setup.
   */
  public function getSetupMessages();

  /**
   * Return process error messages.
   *
   * @return string[]
   *   An array containing the setup errors.
   */
  public function getErrorMessages();

  /**
   * Plugin overview page.
   *
   * @param array $params
   *   Parameters to setup the overview information.
   *
   * @return array
   *   The overview form.
   */
  public function getOverview(array $params);

}
