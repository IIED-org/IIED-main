<?php

namespace Drupal\scn\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a form that configures forms module settings.
 */
class SCNSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'scn_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'scn.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('scn.settings');
    $roles = $config->get('scn_roles');

    $form = [];
    $form['fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Send mail to'),
    ];
    $form['fieldset']['scn_admin'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('admin'),
      '#default_value' => $config->get('scn_admin'),
      '#description' => $this->t('Send mail to user with uid=1'),
    ];
    $form['fieldset']['scn_node_author'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Node author'),
      '#default_value' => $config->get('scn_node_author'),
      '#description' => $this->t('Send mail to the Node author'),
    ];
    $form['fieldset']['scn_roles'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Roles'),
      '#default_value' => !empty($roles) ? $roles : [],
      '#options' => array_map('\Drupal\Component\Utility\Html::escape', user_role_names(TRUE)),
      '#description' => $this->t('Send mail to users with selected roles'),
    ];
    $form['fieldset']['scn_maillist'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Custom mail list'),
      '#default_value' => $config->get('scn_maillist'),
      '#description' => $this->t('Send mail to non-registered users. Delimiter: comma'),
    ];
    $form['fieldset']['scn_telegram'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Send message to Telegram users'),
      '#default_value' => $config->get('scn_telegram'),
    ];
    $form['fieldset']['scn_telegram_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Telegram Settings'),
      '#states' => [
        'visible' => [
          '#edit-scn-telegram' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['fieldset']['scn_telegram_fieldset']['scn_telegram_bottoken'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Bot token'),
      '#default_value' => $config->get('scn_telegram_bottoken'),
    ];
    $form['fieldset']['scn_telegram_fieldset']['scn_telegram_chatids'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Chat IDs'),
      '#default_value' => $config->get('scn_telegram_chatids'),
      '#description' => $this->t('Delimiter: comma'),
    ];
    $form['fieldset']['scn_telegram_fieldset']['scn_telegram_proxy'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use proxy'),
      '#default_value' => $config->get('scn_telegram_proxy'),
    ];
    $form['fieldset']['scn_telegram_fieldset']['scn_telegram_proxy_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Proxy Settings (SOCKS 5)'),
      '#states' => [
        'visible' => [
          '#edit-scn-telegram-proxy' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['fieldset']['scn_telegram_fieldset']['scn_telegram_proxy_fieldset']['scn_telegram_proxy_server'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Server name or IP address'),
      '#default_value' => $config->get('scn_telegram_proxy_server'),
      '#description' => $this->t('For example: 127.0.0.1:1234'),
    ];
    $form['fieldset']['scn_telegram_fieldset']['scn_telegram_proxy_fieldset']['scn_telegram_proxy_login'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Server login'),
      '#default_value' => $config->get('scn_telegram_proxy_login'),
    ];
    $form['fieldset']['scn_telegram_fieldset']['scn_telegram_proxy_fieldset']['scn_telegram_proxy_password'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Server password'),
      '#default_value' => $config->get('scn_telegram_proxy_password'),
    ];
    $form['fieldset']['scn_add_admin_overview_link'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Add admin overview link'),
      '#default_value' => $config->get('scn_add_admin_overview_link'),
      '#description' => '',
    ];
    $form['fieldset']['scn_add_admin_comment_link'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Add admin comment link'),
      '#default_value' => $config->get('scn_add_admin_comment_link'),
      '#description' => '',
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->config('scn.settings')
      ->set('scn_admin', $values['scn_admin'])
      ->set('scn_node_author', $values['scn_node_author'])
      ->set('scn_roles', $values['scn_roles'])
      ->set('scn_maillist', $values['scn_maillist'])
      ->set('scn_telegram', $values['scn_telegram'])
      ->set('scn_telegram_bottoken', $values['scn_telegram_bottoken'])
      ->set('scn_telegram_chatids', $values['scn_telegram_chatids'])
      ->set('scn_telegram_proxy', $values['scn_telegram_proxy'])
      ->set('scn_telegram_proxy_server', $values['scn_telegram_proxy_server'])
      ->set('scn_telegram_proxy_login', $values['scn_telegram_proxy_login'])
      ->set('scn_telegram_proxy_password', $values['scn_telegram_proxy_password'])
      ->set('scn_add_admin_overview_link', $values['scn_add_admin_overview_link'])
      ->set('scn_add_admin_comment_link', $values['scn_add_admin_comment_link'])
      ->save();

    $this->messenger()->addMessage($this->t('The configuration options have been saved.'));
  }

}
