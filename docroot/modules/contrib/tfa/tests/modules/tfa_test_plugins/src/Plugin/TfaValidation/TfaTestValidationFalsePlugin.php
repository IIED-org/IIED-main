<?php

namespace Drupal\tfa_test_plugins\Plugin\TfaValidation;

use Drupal\Core\Form\FormStateInterface;
use Drupal\tfa\Plugin\TfaBasePlugin;
use Drupal\tfa\Plugin\TfaValidationInterface;

/**
 * TFA Test Validation Plugin - FALSE.
 *
 * Provides a plugin that will return FALSE when interrogated.
 *
 * @package Drupal\tfa_test_plugins
 *
 * @TfaValidation(
 *   id = "tfa_test_plugins_validation_false",
 *   label = @Translation("TFA Test Validation Plugin - FALSE Response"),
 *   description = @Translation("TFA Test Validation Plugin - FALSE Response"),
 * )
 */
class TfaTestValidationFalsePlugin extends TfaBasePlugin implements TfaValidationInterface {

  /**
   * {@inheritdoc}
   */
  public function getForm(array $form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array $form, FormStateInterface $form_state) {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function ready() {
    return FALSE;
  }

}
