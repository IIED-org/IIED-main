<?php

namespace Drupal\better_passwords\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Implements a form for general settings on better passwords.
 */
class BetterPasswordsSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['better_passwords.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'better_passwords_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('better_passwords.settings');

    $form['length'] = [
      '#type' => 'number',
      '#title' => $this->t('Minimum password length'),
      '#default_value' => $config->get('length'),
      '#size' => '4',
      '#description' => $this->t('"Verifiers SHALL require subscriber-chosen memorized secrets to be at least 8 characters in length."'),
    ];

    $form['strength'] = [
      '#type' => 'select',
      '#title' => $this->t('Minimum password strength'),
      '#default_value' => $config->get('strength'),
      '#options' => [
        4 => $this->t('4: Strongest'),
        3 => $this->t('3: Strong'),
        2 => $this->t('2: Moderate'),
        1 => $this->t('1: Weak'),
        0 => $this->t('0: Do not check strength'),
      ],
      '#description' => $this->t('This module uses @zxcvbn to check prospective passwords against brute-force attacks, sequential or repeated characters, dates, and English-language dictionaries. This seems to at least partially meet the NIST requirement that: <br/>"When processing requests to establish and change memorized secrets, verifiers SHALL compare the prospective secrets against a list that contains values known to be commonly-used, expected, or compromised."', ['@zxcvbn' => Link::fromTextAndUrl('zxcvbn-php', Url::fromUri('https://github.com/bjeavons/zxcvbn-php/'))->toString()]),
    ];

    $form['auto_generate'] = [
      '#type' => 'select',
      '#title' => $this->t('Auto-generate passwords for new users when added by administrators'),
      '#default_value' => $config->get('auto_generate'),
      '#options' => [
        0 => $this->t('Never'),
        1 => $this->t('Optional'),
        2 => $this->t('Required'),
      ],
      '#description' => $this->t('Forcing administrators to create initial passwords for new users is annoying and possibly insecure, unless those administrators know how to create good passwords. This option employs the Drupal "user_password" function to generate initial passwords.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('better_passwords.settings')
      ->set('length', $form_state->getValue('length'))
      ->set('strength', $form_state->getValue('strength'))
      ->set('auto_generate', $form_state->getValue('auto_generate'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
