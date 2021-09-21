<?php

namespace Drupal\dropdown_language\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a configuration form for global settings.
 */
class DropdownLanguageSettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'dropdown_language_config';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'dropdown_language.setting',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('dropdown_language.setting');

    $form['labels'] = [
      '#type' => 'details',
      '#title' => $this->t('Display Language Labelling'),
      '#open' => TRUE,
    ];
    $form['labels']['display_language_id'] = [
      '#type' => 'radios',
      '#options' => [
        '0' => $this->t('Language Name'),
        '1' => $this->t('Language ID'),
        '2' => $this->t('Native Name'),
        '3' => $this->t('Custom Labels'),
      ],
      '#title' => $this->t('Select which label option type'),
      '#title_display' => 'invisible',
      '#default_value' => $config->get('display_language_id'),
    ];
    $form['labels']['display_language_id'][2]['#description'] = $this->t('Language Name used as title attribute');
    $form['labels']['display_language_id'][3]['#description'] = $this->t('Each block instance will provide fields for label names.');

    $form['decor'] = [
      '#type' => 'details',
      '#title' => $this->t('<q>@switch</q> Decor', ['@switch' => $this->t('Switch Language')]),
      '#open' => TRUE,
    ];

    $form['decor']['wrapper'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show block with fieldset wrapping'),
      '#default_value' => $config->get('wrapper'),
    ];

    $form['seo'] = [
      '#type' => 'details',
      '#title' => $this->t('Filter Untranslated'),
      '#open' => TRUE,
      '#description' => $this->t('Enhances SEO by not adding links that do not actually exist.  Works with Entity based objects.'),
    ];
    $form['seo']['filter_untranslated'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Remove links if no translation is detected.'),
      '#default_value' => $config->get('filter_untranslated'),
    ];

    $form['seo']['always_show_block'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Keep block visible if only one language is available'),
      '#default_value' => $config->get('always_show_block'),
      '#description' => $this->t('Placed blocks will not output anything if there is not other translation available.  User role access to translation is checked.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('dropdown_language.setting')
      ->set('wrapper', $form_state->getValue('wrapper'))
      ->set('display_language_id', $form_state->getValue('display_language_id'))
      ->set('filter_untranslated', $form_state->getValue('filter_untranslated'))
      ->set('always_show_block', $form_state->getValue('always_show_block'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
