<?php

declare(strict_types=1);

namespace Drupal\ckeditor5_plugin_pack\Form;

use Drupal\ckeditor5_plugin_pack\Config\SettingsConfigHandlerInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SettingsForm. The config form for the module.
 *
 * @package Drupal\ckeditor5_plugin_pack\Form
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritDoc}
   */
  protected function getEditableConfigNames() {
    return [
      'ckeditor5_plugin_pack.settings',
    ];
  }

  /**
   * {@inheritDoc}
   */
  public function getFormId() {
    return 'ckeditor5_plugin_pack_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('ckeditor5_plugin_pack.settings');

    $form['premium'] = [
      '#markup' => ckeditor5_plugin_pack_premium_info_message(),
    ];

    $dll_location_description = $this->t('
    <b>If the field is empty, the DLL path is set to the CKEditor CDN server by default.</b></br></br>
    Specify the path to the directory with plugins e.g.
    /libraries/ckeditor5_plugins/@token/dll/ </br>
    "@token" - replaced dynamically with the version of your CKEditor.
    </br></br>
    Example of Font plugin directory:</br>
    /libraries/ckeditor5_plugins/41.3.1/dll/font/font.js</br>
    /libraries/ckeditor5_plugins/41.3.1/dll/font/translations/pl.js</br>
    ',
     ['@token' => SettingsConfigHandlerInterface::DLL_PATH_VERSION_TOKEN]);
    $form['dll_location'] = [
      '#type' => 'textfield',
      '#title' => $this->t('DLL location'),
      '#description' => $dll_location_description,
      '#default_value' => $config->get('dll_location'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('ckeditor5_plugin_pack.settings');
    $cleanValues = $form_state->cleanValues()->getValues();

    // Let's make sure the path ends with the trailing slash.
    if (!empty($cleanValues['dll_location'])) {
      $cleanValues['dll_location'] = rtrim($cleanValues['dll_location'], ' /') . '/';
    }

    $config
      ->set('dll_location', $cleanValues['dll_location'])
      ->save();

    $dllChanged = $config->get('dll_location') !== $cleanValues['dll_location'];

    if ($dllChanged) {
      $invalidateTags = [
        'ckeditor5_plugins',
        'editor_plugins',
        'filter_plugins',
      ];
      $invalidateTags[] = 'library_info';
      Cache::invalidateTags($invalidateTags);
    }

    parent::submitForm($form, $form_state);
  }

}
