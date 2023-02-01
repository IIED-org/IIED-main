<?php

namespace Drupal\gin_login\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManager;

/**
 * Class SettingsForm.
 */
class GinLoginConfigurationForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'gin_login.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'gin_login_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('gin_login.settings');
    $default_scheme = $this->config('system.file')->get('default_scheme');
    $form['logo'] = [
      '#type' => 'details',
      '#title' => t('Logo'),
      '#open' => TRUE,
    ];
    $form['logo']['default_logo'] = [
      '#type' => 'checkbox',
      '#title' => t('Use default logo'),
      '#default_value' => $config->get('logo.use_default'),
      '#tree' => FALSE,
    ];
    $form['logo']['settings'] = [
      '#type' => 'container',
      '#states' => [
        // Hide the logo settings when using the default logo.
        'invisible' => [
          'input[name="default_logo"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['logo']['settings']['logo_path'] = [
      '#type' => 'textfield',
      '#title' => t('Path to custom logo'),
      '#default_value' => $config->get('logo.path') ? str_replace($default_scheme . '://', "", $config->get('logo.path')) : '',
    ];
    $form['logo']['settings']['logo_upload'] = [
      '#type' => 'file',
      '#title' => t('Upload image'),
      '#description' => t("If you don't have direct file access to the server, use this field to upload your logo."),
      '#upload_validators' => [
        'file_validate_extensions' => [
          'png gif jpg jpeg apng webp avif svg',
        ],
      ],
    ];

    $form['brand_image'] = [
      '#type' => 'details',
      '#title' => t('Wallpaper'),
      '#open' => TRUE,
    ];
    $form['brand_image']['default_brand_image'] = [
      '#type' => 'checkbox',
      '#title' => t('Use random image'),
      '#default_value' => $config->get('brand_image.use_default'),
      '#tree' => FALSE,
    ];
    $form['brand_image']['settings'] = [
      '#type' => 'container',
      '#states' => [
        // Hide the logo settings when using the default logo.
        'invisible' => [
          'input[name="default_brand_image"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['brand_image']['settings']['brand_image_path'] = [
      '#type' => 'textfield',
      '#title' => t('Path to custom image'),
      '#default_value' => $config->get('brand_image.path') ? str_replace($default_scheme . '://', "", $config->get('brand_image.path')) : '',
    ];
    $form['brand_image']['settings']['brand_image_upload'] = [
      '#type' => 'file',
      '#title' => t('Upload image'),
      '#description' => t("If you don't have direct file access to the server, use this field to upload your brand image."),
      '#upload_validators' => [
        'file_validate_is_image' => [],
      ],
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $moduleHandler = \Drupal::service('module_handler');

    if ($moduleHandler->moduleExists('file')) {
      // Check for a new uploaded logo.
      if (isset($form['logo'])) {
        $file = _file_save_upload_from_form($form['logo']['settings']['logo_upload'], $form_state, 0);
        if ($file) {
          // Put the temporary file in form_values so we can save it on submit.
          $form_state->setValue('logo_upload', $file);
        }
      }
      // When intending to use the default logo, unset the logo_path.
      if ($form_state->getValue('default_logo')) {
        $form_state->unsetValue('logo_path');
      }
      // If the user provided a path for a logo or favicon file,
      // make sure a file exists at that path.
      if ($form_state->getValue('logo_path')) {
        $path = $this->validatePath($form_state->getValue('logo_path'));
        if (!$path) {
          $form_state->setErrorByName('logo_path', $this->t('The custom logo path is invalid.'));
        }
      }

      // Check for a new uploaded Brand Image.
      if (isset($form['brand_image'])) {
        $file = _file_save_upload_from_form($form['brand_image']['settings']['brand_image_upload'], $form_state, 0);
        if ($file) {
          // Put the temporary file in form_values so we can save it on submit.
          $form_state->setValue('brand_image_upload', $file);
        }
      }
      // When intending to use the default brand image,
      // unset the brand_image_path.
      if ($form_state->getValue('default_brand_image')) {
        $form_state->unsetValue('brand_image_path');
      }
      // If the user provided a path for a brand image make sure a file
      // exists at that path.
      if ($form_state->getValue('brand_image_path')) {
        $path = $this->validatePath($form_state->getValue('brand_image_path'));
        if (!$path) {
          $form_state->setErrorByName('brand_image_path', $this->t('The custom brand image path is invalid.'));
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $values = $form_state->getValues();
    $file_system = \Drupal::service('file_system');
    $default_scheme = $this->config('system.file')->get('default_scheme');
    $config = $this->config('gin_login.settings');
    try {
      if (!empty($values['logo_upload'])) {
        $filename = $file_system->copy($values['logo_upload']->getFileUri(), $default_scheme . '://');
        $values['default_logo'] = 0;
        $values['logo_path'] = $filename;
      }
    }
    catch (FileException $e) {
      // Ignore.
    }

    try {
      if (!empty($values['brand_image_upload'])) {
        $filename = $file_system->copy($values['brand_image_upload']->getFileUri(), $default_scheme . '://');
        $values['default_brand_image'] = 0;
        $values['brand_image_path'] = $filename;
      }
    }
    catch (FileException $e) {
      // Ignore.
    }
    unset($values['logo_upload']);
    unset($values['favicon_upload']);

    // If the user entered a path relative to the system files directory for
    // a logo store a public:// URI so the theme system can handle it.
    if (!empty($values['logo_path'])) {
      $values['logo_path'] = $this->validatePath($values['logo_path']);
    }

    // If the user entered a path relative to the system files directory for
    // a brand images, store a public:// URI so the theme system can handle it.
    if (!empty($values['brand_image_path'])) {
      $values['brand_image_path'] = $this->validatePath($values['brand_image_path']);
    }

    foreach ($values as $key => $value) {
      if ($key == 'default_logo') {
        $config->set('logo.use_default', $value);
      }
      elseif ($key == 'logo_path') {
        $config->set('logo.path', $value);
      }
      elseif ($key == 'default_brand_image') {
        $config->set('brand_image.use_default', $value);
      }
      elseif ($key == 'brand_image_path') {
        $config->set('brand_image.path', $value);
      }
    }

    $config->save();
    // Rebuild the router.
    \Drupal::service('router.builder')->rebuild();
  }

  /**
   * Helper function for the system_theme_settings form.
   *
   * Attempt to validate normal system paths, paths relative to the public files
   * directory, or stream wrapper URIs. If the given path is any of the above,
   * returns a valid path or URI that the theme system can display.
   *
   * @param string $path
   *   A path relative to the Drupal root or to the public files directory, or
   *   a stream wrapper URI.
   *
   * @return mixed
   *   A valid path that can be displayed through the theme system, or FALSE if
   *   the path could not be validated.
   */
  protected function validatePath($path) {
    $file_system = \Drupal::service('file_system');
    // Absolute local file paths are invalid.
    if ($file_system->realpath($path) == $path) {
      return FALSE;
    }
    // A path relative to the Drupal root or a fully qualified URI is valid.
    if (is_file($path)) {
      return $path;
    }
    // Prepend 'public://' for relative file paths within public filesystem.
    if (StreamWrapperManager::getScheme($path) === FALSE) {
      $path = 'public://' . $path;
    }
    if (is_file($path)) {
      return $path;
    }
    return FALSE;
  }

}
