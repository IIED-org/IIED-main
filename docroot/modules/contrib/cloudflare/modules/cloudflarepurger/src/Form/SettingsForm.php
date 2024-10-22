<?php

namespace Drupal\cloudflarepurger\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form for the Cloudflare Purger settings.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'cloudflarepurger.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cloudflarepurger_admin';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('cloudflarepurger.settings');
    $blacklist = $config->get('edge_cache_tag_header_blacklist');
    $blacklist = is_array($blacklist) ? implode(PHP_EOL, $blacklist) : '';
    $form['cloudflare_config']['edge_cache_tag_header_blacklist'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Cache Tag Blacklist'),
      '#default_value' =>  $blacklist,
      '#description' => $this->t('List of tag prefixes to blacklist from the Edge-Cache-Tag header. One per line.'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $formState) {
    $config = $this->configFactory()->getEditable('cloudflarepurger.settings');
    $config->set('edge_cache_tag_header_blacklist', explode(PHP_EOL, $formState->getValue('edge_cache_tag_header_blacklist')));
    $config->save();
  }

}
