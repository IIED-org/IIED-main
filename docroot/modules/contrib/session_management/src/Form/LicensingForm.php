<?php

namespace Drupal\session_management\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\session_management\Utilities;

/**
 * Upgrade version Form.
 */
class LicensingForm extends ConfigFormBase
{

  public const SETTINGS = 'session_management.settings';

  /**
   * {@inheritDoc}
   */
  protected function getEditableConfigNames()
  {
    return [static::SETTINGS];
  }

  /**
   * {@inheritDoc}
   */
  public function getFormId()
  {
    return 'licensing-form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state)
  {

    $form['libraries'] = [
      '#attached' => [
        'library' => [
          "session_management/session_management.mo_session",
        ],
      ],
    ];

    $form['div_start'] = [
      '#type' => 'markup',
      '#markup' => '<div class="mo-table-layout-2"><div class="mo-table-layout-3 mo-container1">',
    ];

    $form['div_end'] = [
      '#type' => 'markup',
      '#markup' => '</div></div>',
    ];

    Utilities::addSupportButton($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {}
}
