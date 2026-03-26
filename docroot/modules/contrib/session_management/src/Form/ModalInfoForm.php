<?php

namespace Drupal\session_management\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\ReplaceCommand;

/**
 * Configuration form for modal dialog settings.
 */
class ModalInfoForm extends ConfigFormBase {

  public const SETTINGS = 'session_management.settings';

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [static::SETTINGS];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'modal_info_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(static::SETTINGS);

    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';

    $form['mo_modal_width'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Modal custom width'),
      '#size' => 40,
      '#description' => $this->t('Enter the width of the modal dialog in pixels.'),
      '#default_value' => $config->get('mo_modal_width') ?? 400,
      '#required' => TRUE,
    ];

    $form['mo_modal_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Modal custom title'),
      '#required' => TRUE,
      '#default_value' => $config->get('mo_modal_title') ?? $this->config('system.site')->get('name') . ' Alert',
      '#size' => 40,
      '#description' => $this->t('Enter the title for the modal dialog.'),
    ];

    $form['mo_modal_message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Modal custom message'),
      '#required' => TRUE,
      '#default_value' => $config->get('mo_modal_message') ?? "You've been inactive for a while. Would you like to continue your session?",
      '#size' => 40,
      '#description' => $this->t('Enter the message to display in the logout modal dialog.'),
      '#rows' => 3,
    ];

    $form['mo_modal_yes_button_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Modal custom confirm button text'),
      '#required' => TRUE,
      '#default_value' => $config->get('mo_modal_yes_button_text') ?? "Accept",
      '#size' => 40,
      '#description' => $this->t('Enter the confirmation button text to <b>extend</b> the user session.'),
    ];

    $form['mo_modal_no_button_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Modal custom decline button text'),
      '#required' => TRUE,
      '#default_value' => $config->get('mo_modal_no_button_text') ?? "Deny",
      '#size' => 40,
      '#description' => $this->t('Enter the confirmation button text to <b>end</b> the user session.'),
    ];

    $form['actions'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['form-actions']],
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
      '#ajax' => [
        'callback' => '::submitModalForm',
        'event' => 'click',
      ],
    ];

    $form['actions']['cancel'] = [
      '#type' => 'button',
      '#value' => $this->t('Cancel'),
      '#ajax' => [
        'callback' => '::cancelModal',
        'event' => 'click',
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $width = $form_state->getValue('mo_modal_width');
    if (!is_numeric($width) || $width < 200) {
      $form_state->setErrorByName('mo_modal_width', $this->t('Width must be a number greater than 200 pixels.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory()->getEditable(self::SETTINGS);

    $fields = [
      'mo_modal_width',
      'mo_modal_title',
      'mo_modal_message',
      'mo_modal_yes_button_text',
      'mo_modal_no_button_text',
    ];

    foreach ($fields as $field) {
      $config->set($field, $form_state->getValue($field));
    }
    $config->save();

    $this->messenger()->addStatus($this->t("Modal configuration saved successfully."));
  }

  /**
   * AJAX callback for form submission.
   */
  public function submitModalForm(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    
    if ($form_state->hasAnyErrors()) {
      $response->addCommand(new ReplaceCommand('#modal-info-form', $form));
    } else {
      $this->submitForm($form, $form_state);
      $response->addCommand(new CloseModalDialogCommand());
    }

    return $response;
  }

  /**
   * AJAX callback for cancel button.
   */
  public function cancelModal(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $response->addCommand(new CloseModalDialogCommand());
    return $response;
  }

}
