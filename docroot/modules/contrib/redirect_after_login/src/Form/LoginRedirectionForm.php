<?php

namespace Drupal\redirect_after_login\Form;

use Drupal;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class LoginRedirectionForm.
 *
 * @package Drupal\redirect_after_login\Form
 */
class LoginRedirectionForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'login_redirection_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('redirect_after_login.settings');
    $savedPathRoles = $config->get('login_redirection');

    $form['roles'] = [
      '#type'  => 'fieldset',
      '#title' => $this->t('All roles'),
    ];
    foreach (user_role_names(TRUE) as $user => $name) {
      if ($user != "anonymous") {
        $form['roles'][$user] = [
          '#type'          => 'textfield',
          '#title'         => $name,
          '#size'          => 60,
          '#maxlength'     => 128,
          '#description'   => $this->t('Add a valid url or &ltfront> for main page'),
          '#required'      => TRUE,
          '#default_value' => isset($savedPathRoles[$user]) ? $savedPathRoles[$user] : '',
        ];
      }
    }

    $form['exclude_urls'] = [
      '#type'          => 'textarea',
      '#title'         => $this->t('Exclude url from redirection'),
      '#description'   => $this->t('One url per line. Redirection on this urls will be skipped. You can use wildcard "*".'),
      '#default_value' => $config->get('exclude_urls'),
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type'        => 'submit',
      '#value'       => $this->t('Save'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    foreach (user_role_names() as $user => $name) {
      if ($user == "anonymous") {
        continue;
      }
      if (!(preg_match('/^[#?\/]+/', $form_state->getValue($user)) || $form_state->getValue($user) == '<front>')) {
        $form_state->setErrorByName($user, $this->t('This URL %url is not valid for role %role.', [
          '%url'  => $form_state->getValue($user),
          '%role' => $name,
        ]));
      }
      $path = $form_state->getValue($user);
      $is_valid = Drupal::service('path.validator')->isValid($path);
      if ($is_valid == NULL) {
        $form_state->setErrorByName($user, $this->t('Path does not exists.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $loginUrls = [];
    foreach (user_role_names() as $user => $name) {
      if ($form_state->getValue($user) == '<front>') {
        $loginUrls[$user] = '/';
      }
      else {
        $loginUrls[$user] = $form_state->getValue($user);
        $form_state->getValue($user);
      }
    }
    $this->config('redirect_after_login.settings')
      ->set('login_redirection', $loginUrls)
      ->set('exclude_urls', $form_state->getValue('exclude_urls'))
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Get Editable config names.
   *
   * @inheritDoc
   */
  protected function getEditableConfigNames() {
    return ['redirect_after_login.settings'];
  }

}
