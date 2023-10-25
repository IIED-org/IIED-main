<?php

namespace Drupal\layout_paragraphs_form_rendering_test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a form for testing form rendering within the LP Builder.
 *
 * @see https://www.drupal.org/project/layout_paragraphs/issues/3263715
 * @see https://www.drupal.org/project/layout_paragraphs/issues/3258879
 */
class TestForm extends FormBase {

  /**
   * {@inheritDoc}
   */
  public function getFormId() {
    return 'layout_paragraphs_test_form';
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Intentionally named "title" to test for conflict with node title.
    // @see https://www.drupal.org/project/layout_paragraphs/issues/3263715
    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Test field'),
      '#required' => TRUE,
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];
    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}
