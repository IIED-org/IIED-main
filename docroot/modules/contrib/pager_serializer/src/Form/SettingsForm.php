<?php

namespace Drupal\pager_serializer\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Configure example settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * Config settings.
   *
   * @var string
   */
  const SETTINGS = 'pager_serializer.settings';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'pager_serializer_admin_config';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      static::SETTINGS,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(static::SETTINGS);

    $form['rows_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Rows'),
      '#default_value' => $config->get('rows_label'),
      '#description' => $this->t('Set the property name the default results will be returned as. Defaults to <em>:rows</em>', [':rows' => 'rows']),
      '#required' => TRUE,
    ];

    $form['pager_object_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Include a pagination object.'),
      '#description' => $this->t('Pagination data will be attached to the main response object if this is unchecked.'),
      '#default_value' => $config->get('pager_object_enabled'),
    ];
    $form['pager_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Pager'),
      '#default_value' => $config->get('pager_label'),
      '#description' => $this->t('Set the property name the paginiation data will be returned as. Defaults to <em>:pager</em>', [':pager' => 'pager']),
      '#required' => TRUE,
      '#states'   => [
        'visible' => [
          ':input[name="pager_object_enabled"][value="1"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['pager_fieldset'] = [
      '#type' => 'details',
      '#title' => $this->t('Pager Settings'),
    ];

    $form['pager_fieldset']['current_page'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Current Page'),
    ];

    $form['pager_fieldset']['current_page']['current_page_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable'),
      '#default_value' => $config->get('current_page_enabled'),
    ];
    $form['pager_fieldset']['current_page']['current_page_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Property'),
      '#default_value' => $config->get('current_page_label'),
      '#required' => TRUE,
      '#states'   => [
        'visible' => [
          ':input[name="current_page_enabled"][value="1"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['pager_fieldset']['total_items'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Total Items'),
    ];
    $form['pager_fieldset']['total_items']['total_items_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable'),
      '#default_value' => $config->get('total_items_enabled'),
    ];
    $form['pager_fieldset']['total_items']['total_items_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Property'),
      '#default_value' => $config->get('total_items_label'),
      '#required' => TRUE,
      '#states'   => [
        'visible' => [
          ':input[name="total_items_enabled"][value="1"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['pager_fieldset']['total_pages'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Total Pages'),
    ];
    $form['pager_fieldset']['total_pages']['total_pages_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable'),
      '#default_value' => $config->get('total_pages_enabled'),
    ];
    $form['pager_fieldset']['total_pages']['total_pages_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Property'),
      '#default_value' => $config->get('total_pages_label'),
      '#required' => TRUE,
      '#states'   => [
        'visible' => [
          ':input[name="total_pages_enabled"][value="1"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['pager_fieldset']['items_per_page'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Items Pre Page'),
    ];
    $form['pager_fieldset']['items_per_page']['items_per_page_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable'),
      '#default_value' => $config->get('items_per_page_enabled'),
    ];
    $form['pager_fieldset']['items_per_page']['items_per_page_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Property'),
      '#default_value' => $config->get('items_per_page_label'),
      '#required' => TRUE,
      '#states'   => [
        'visible' => [
          ':input[name="items_per_page_enabled"][value="1"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $build = parent::buildForm($form, $form_state);

    $build['actions']['reset'] = [
      '#title' => $this->t('Reset'),
      '#type' => 'link',
      '#url' => Url::fromRoute('pager_serializer.settings.reset'),
    ];

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Retrieve the configuration.
    $this->configFactory->getEditable(static::SETTINGS)
      // Set the submitted configuration setting.
      ->set('rows_label', $form_state->getValue('rows_label'))
      ->set('pager_label', $form_state->getValue('pager_label'))
      ->set('pager_object_enabled', $form_state->getValue('pager_object_enabled'))

      ->set('current_page_enabled', $form_state->getValue('current_page_enabled'))
      ->set('current_page_label', $form_state->getValue('current_page_label'))

      ->set('total_items_enabled', $form_state->getValue('total_items_enabled'))
      ->set('total_items_label', $form_state->getValue('total_items_label'))

      ->set('total_pages_enabled', $form_state->getValue('total_pages_enabled'))
      ->set('total_pages_label', $form_state->getValue('total_pages_label'))

      ->set('items_per_page_enabled', $form_state->getValue('items_per_page_enabled'))
      ->set('items_per_page_label', $form_state->getValue('items_per_page_label'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
