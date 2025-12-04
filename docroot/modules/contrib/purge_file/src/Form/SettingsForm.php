<?php

namespace Drupal\purge_file\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Purge file settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'purge_file_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['purge_file.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('purge_file.settings');
    $url_purger_types = purge_file_supported_url_purgers();
    $url_purger_types_enabled = purge_file_supported_url_purgers_enabled();

    $have_url = in_array('url', $url_purger_types_enabled);
    $have_wildcardurl = in_array('wildcardurl', $url_purger_types_enabled);

    if (empty($url_purger_types_enabled)) {
      $this->messenger()->addError($this->t('No URL purger is enabled. There must exists at least one purger that supports URLs. Supported purgers: @purgers_supported', [
        '@purgers_supported' => implode(', ', $url_purger_types),
      ]));
    }

    if (!$have_wildcardurl && $have_url) {
      $this->messenger()->addWarning($this->t('The "Wildcard URL" purge invalidation is not supported. The Purge File module will use the "URL" purger regardless of the "Wildcard" setting below.'));
    }

    $form['base_urls'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Base URL(s)'),
      '#description' => $this->t('The base URLs of the site, separated by commas. Set it up for sites which front URL is different than the back office URL.'),
      '#default_value' => $config->get('base_urls'),
    ];

    $form['workflow'] = [
      '#type' => 'select',
      '#title' => $this->t('Workflow'),
      '#description' => $this->t('Process immediately or use a queue.'),
      '#default_value' => $config->get('workflow'),
      '#options' => [
        'immediate' => $this->t('Immediately on file updates'),
        'queue' => $this->t('In bulk via Queue'),
      ],
      '#required' => TRUE,
    ];

    $form['wildcard'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Wildcard'),
      '#description' => $this->t('Add wildcard to end of purge URL, purging all variants, such as query string tracking codes. Requires that the purger supports the "wildcardurl" invalidation.'),
      '#default_value' => $config->get('wildcard'),
    ];

    $form['debug'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Debug'),
      '#description' => $this->t('By checking this, a log will be created for each time a file is purged. Use it only for debugging purposes.'),
      '#default_value' => $config->get('debug'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('purge_file.settings')
      ->set('workflow', $form_state->getValue('workflow'))
      ->set('wildcard', (bool) $form_state->getValue('wildcard'))
      ->set('debug', (bool) $form_state->getValue('debug'))
      ->set('base_urls', $form_state->getValue('base_urls'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
