<?php

namespace Drupal\tfa\Plugin\TfaSetup;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\tfa\Plugin\TfaSetupInterface;
use Drupal\tfa\Plugin\TfaValidation\TfaRecoveryCode;

/**
 * TFA Recovery Code Setup Plugin.
 *
 * @TfaSetup(
 *   id = "tfa_recovery_code_setup",
 *   label = @Translation("TFA Recovery Code Setup"),
 *   description = @Translation("TFA Recovery Code Setup Plugin"),
 *   setupMessages = {
 *    "saved" = @Translation("Recovery codes saved."),
 *    "skipped" = @Translation("Recovery codes not saved.")
 *   }
 * )
 */
class TfaRecoveryCodeSetup extends TfaRecoveryCode implements TfaSetupInterface {

  /**
   * {@inheritdoc}
   */
  public function ready() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getOverview(array $params) {
    $ret = [
      'heading' => [
        '#type' => 'html_tag',
        '#tag' => 'h3',
        '#value' => $this->t('Recovery Codes'),
      ],
      'description' => [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => $this->t('Generate one-time use codes for two-factor login. These are generally used to recover your account in case you lose access to another 2nd-factor device.'),
      ],
      'setup' => [
        '#theme' => 'links',
        '#links' => [
          'reset' => [
            'title' => !$params['enabled'] ? $this->t('Generate codes') : $this->t('Reset codes'),
            'url' => Url::fromRoute('tfa.plugin.reset', [
              'user' => $params['account']->id(),
              'method' => $params['plugin_id'],
              'reset' => 1,
            ]),
          ],
        ],
      ],
      'show_codes' => [
        '#theme' => 'links',
        '#access' => $params['enabled'],
        '#links' => [
          'show' => [
            'title' => $this->t('Show codes'),
            'url' => Url::fromRoute('tfa.validation.setup', [
              'user' => $params['account']->id(),
              'method' => $params['plugin_id'],
            ]),
          ],
        ],
      ],
    ];

    // Don't show codes to other users.
    if ($this->currentUser->id() !== $this->uid) {
      unset($ret['show_codes']);
    }

    return $ret;
  }

  /**
   * Get the setup form for the validation method.
   *
   * @param array $form
   *   The configuration form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param int $reset
   *   Whether or not the user is resetting the application.
   *
   * @return array
   *   Form API array.
   */
  public function getSetupForm(array $form, FormStateInterface $form_state, $reset = 0) {
    $codes = $this->getCodes();

    // If $reset has a value, we're setting up new codes.
    if (!empty($reset)) {
      $codes = $this->generateCodes();

      // Make the human friendly.
      foreach ($codes as $key => $code) {
        $codes[$key] = implode(' ', str_split($code, 3));
      }
      $form['recovery_codes'] = [
        '#type' => 'value',
        '#value' => $codes,
      ];
    }

    $form['recovery_codes_output'] = [
      '#title' => $this->t('Recovery Codes'),
      '#theme' => 'item_list',
      '#items' => $codes,
    ];
    $form['description'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('Print or copy these codes and store them somewhere safe before continuing.'),
    ];

    if (!empty($reset)) {
      $form['actions'] = ['#type' => 'actions'];
      $form['actions']['save'] = [
        '#type' => 'submit',
        '#button_type' => 'primary',
        '#value' => $this->t('Save codes to account'),
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateSetupForm(array $form, FormStateInterface $form_state) {
    if (!empty($form_state->getValue('recovery_codes'))) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function submitSetupForm(array $form, FormStateInterface $form_state) {
    $this->storeCodes($form_state->getValue('recovery_codes'));
    return TRUE;
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
    return ($this->pluginDefinition['setupMessages']) ?: [];
  }

}
