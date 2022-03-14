<?php

namespace Drupal\tablefield\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Implements table configuration form.
 */
class TablefieldConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'tablefield_config_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['tablefield.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['csv_separator'] = [
      '#type' => 'textfield',
      '#title' => $this->t('CSV separator'),
      '#size' => 1,
      '#length' => 1,
      '#maxlength' => 1,
      '#required' => TRUE,
      '#default_value' => $this->config('tablefield.settings')->get('csv_separator'),
      '#description' => $this->t('Select the separator for the CSV import/export. Most common are a comma (,) or a semicolon (;).'),
    ];

    $form['rows'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default number of table rows'),
      '#size' => 3,
      '#maxlength' => 3,
      '#default_value' => $this->config('tablefield.settings')->get('rows'),
      '#description' => $this->t('You can override this in field settings or in your custom form element.'),
    ];

    $form['cols'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default number of table columns'),
      '#size' => 2,
      '#maxlength' => 2,
      '#default_value' => $this->config('tablefield.settings')->get('cols'),
      '#description' => $this->t('You can override this in field settings or in your custom form element.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('tablefield.settings')
      ->set('csv_separator', $form_state->getValue('csv_separator'))
      ->set('rows', $form_state->getValue('rows'))
      ->set('cols', $form_state->getValue('cols'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
