<?php

namespace Drupal\view_password\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * {@inheritdoc}
 */
class PasswordSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'password_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form = parent::buildForm($form, $form_state);

    $config = $this->config('view_password.settings');

    $form['#attached']['library'][] = 'view_password/pwd_lb_backend';

    $form['form_id_pwd'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Enter the form id(s) here.'),
      '#description' => $this->t('<p>Please enter the form id(s) by separating it with a comma (<em>make sure there are no spaces between form ids</em>). For example, the usual form ids are <code>user_login_form</code> and <code>user_register_form</code>. .</p>'),
      '#default_value' => $config->get('form_ids'),
    ];

    $form['span_classes'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Enter the form class here.'),
      '#description' => $this->t('Please enter the icon span classes separated with a space.'),
      '#default_value' => $config->get('span_classes'),
    ];

    $trailing_slash_message = 'Enter a relative path to your project\'s root folder, with a trailing /, like "/themes/custom/my_theme/my_icon.svg" (defaults to <span class="@icon-class" aria-label="@icon-description"></span>).';
    $form['icon_hidden'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Enter the path to the SVG icon here that represents a hidden password.'),
      '#description' => $this->t($trailing_slash_message,
        array('@icon-class' => 'eye-close', '@icon-description' => $this->t('a crossed out eye'))),
      '#default_value' => $config->get('icon_hidden'),
    ];

    $form['icon_exposed'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Enter the path to the SVG icon here that represents an exposed password.'),
      '#description' => $this->t($trailing_slash_message,
        array('@icon-class' => 'eye-open', '@icon-description' => $this->t('an open eye'))),
      '#default_value' => $config->get('icon_exposed'),
    ];

    return $form;

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('view_password.settings')
      ->set('form_ids', $form_state->getValue('form_id_pwd'))
      ->set('span_classes', $form_state->getValue('span_classes'))
      ->set('icon_hidden', $form_state->getValue('icon_hidden'))
      ->set('icon_exposed', $form_state->getValue('icon_exposed'))
      ->save();
    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $values = $form_state->getValue('form_id_pwd');
    $value_array = explode(',', $values);
    // Iterate over the array and check for spaces.
    foreach ($value_array as $value) {
      if (preg_match('/\s/', $value)) {
        // If a space is found, set an error.
        $form_state->setErrorByName('form_id_pwd', $this->t('The form ids should contain values separated by commas only. Spaces are not allowed.'));
        continue;
      }
    }

    $icon_hidden = $form_state->getValue('icon_hidden');
    if ($icon_hidden && !str_starts_with($icon_hidden, '/')) {
      $form_state->setErrorByName('icon_hidden', $this->t("The path to the hidden password icon should start with a trailing slash (/)."));
    }


    $icon_exposed = $form_state->getValue('icon_exposed');
    if ($icon_exposed && !str_starts_with($icon_exposed, '/')) {
      $form_state->setErrorByName('icon_exposed', $this->t("The path to the exposed password icon should start with a trailing slash (/)."));
    }

  }


  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'view_password.settings',
    ];
  }


}
