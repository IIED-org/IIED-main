<?php

namespace Drupal\tfa_test_plugins\Plugin\TfaSetup;

use Drupal\Core\Form\FormStateInterface;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\tfa\Plugin\TfaBasePlugin;
use Drupal\tfa\Plugin\TfaSetupInterface;
use Drupal\user\Entity\User;

/**
 * TFA Test Validation Plugin Setup Plugin.
 *
 * @package Drupal\tfa_test_plugins
 *
 * @TfaSetup(
 *   id = "tfa_test_plugins_validation_setup",
 *   label = @Translation("TFA Test Validation Plugin Setup"),
 *   description = @Translation("TFA Test Validation Plugin Setup Plugin"),
 *   helpLinks = {},
 *   setupMessages = {}
 * )
 */
class TfaTestValidationPluginSetupPlugin extends TfaBasePlugin implements TfaSetupInterface {
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function ready() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getSetupForm(array $form, FormStateInterface $form_state) {
    $form['user']['#markup'] = $this->t('<p>TFA Setup for @name</p>', [
      '@name' => User::load($this->configuration['uid'])->getDisplayName(),
    ]);
    $form['expected_field'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Expected field'),
      '#required' => TRUE,
    ];
    $form['actions']['#type'] = 'actions';
    $form['actions']['login'] = [
      '#type'  => 'submit',
      '#button_type' => 'primary',
      '#value' => $this->t('Verify and save'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateSetupForm(array $form, FormStateInterface $form_state) {
    $expected_value = $form_state->getValue('expected_field');

    if (empty($expected_value)) {
      $form_state->setError($form['expected_field'], $this->t('Missing expected value.'));
      return FALSE;
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function submitSetupForm(array $form, FormStateInterface $form_state) {
    $encrypted = $this->encrypt($form_state->getValue('expected_field'));
    $record = [
      'test_data' => [
        'expected_field' => base64_encode($encrypted),
      ],
    ];
    $this->setUserData($this->pluginDefinition['id'], $record, $this->uid, $this->userData);

    return TRUE;
  }

  /**
   * Get and decode the data expected during setup.
   *
   * @return null|string
   *   The string if found, otherwise NULL;
   *
   * @throws \Drupal\encrypt\Exception\EncryptionMethodCanNotDecryptException
   * @throws \Drupal\encrypt\Exception\EncryptException
   */
  public function getExpectedFieldData() {
    $data = $this->getUserData($this->pluginDefinition['id'], 'test_data', $this->uid, $this->userData);
    if (!empty($data['expected_field'])) {
      return $this->decrypt(base64_decode($data['expected_field']));
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getHelpLinks() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getSetupMessages() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getOverview(array $params) {
    return [
      'heading' => [
        '#type' => 'html_tag',
        '#tag' => 'h2',
        '#value' => $this->t('TFA application'),
      ],
      'link' => [
        '#theme' => 'links',
        '#links' => [
          'admin' => [
            'title' => !$params['enabled'] ? $this->t('Set up test application') : $this->t('Reset test application'),
            'url' => Url::fromRoute('tfa.validation.setup', [
              'user' => $params['account']->id(),
              'method' => $params['plugin_id'],
            ]),
          ],
        ],
      ],
    ];
  }

}
