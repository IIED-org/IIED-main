<?php
/**
 * @file
 * Contains \Drupal\taxonomy_import\Form\ImportForm.
 */

namespace Drupal\taxonomy_import\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Contribute form.
 */
class ImportFormSettings extends ConfigFormBase {

  /**
   * The default File extensions.
   *
   * @var int
   */
  const DEFAULT_FILE_EXTENSION = 'csv xml';

  /**
   * The default size of uploading files.
   *
   * @var int
   */
  const DEFAULT_FILE_SIZE = 256000000;

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'import_taxonomy.config',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
	  return 'import_taxonomy_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('import_taxonomy.config');
    $form['file_extensions'] = [
      '#type' => 'textfield',
      '#size' => 40,
      '#title' => $this->t('Allowed file extensions'),
      '#required' => TRUE,
      '#default_value' => $config->get('file_extensions') ?? static::DEFAULT_FILE_EXTENSION,
	    '#description' => t('Extensions of files.'),
    ];
    $form['file_max_size'] = [
      '#type' => 'number',
      '#title' => $this->t('Max size of file'),
      '#required' => TRUE,
      '#default_value' => $config->get('file_max_size') ?? static::DEFAULT_FILE_SIZE,
      '#description' => t('Max size of file in bytes'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->configFactory->getEditable('import_taxonomy.config')
      ->set('file_extensions', $form_state->getValue('file_extensions'))
      ->save();
    $this->configFactory->getEditable('import_taxonomy.config')
      ->set('file_max_size', $form_state->getValue('file_max_size'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
