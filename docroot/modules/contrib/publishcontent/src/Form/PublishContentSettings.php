<?php

namespace Drupal\publishcontent\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class PublishContentSettings.
 */
class PublishContentSettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'publishcontent.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'publishcontent_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $config = $this->config('publishcontent.settings');

    $form['ui'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('User interface preferences'),
      '#description' => $this->t('Configure how users interact with the publish and unpublish toggle.'),
    ];

    $form['ui_localtask'] = [
      '#type' => 'checkbox',
      '#group' => 'ui',
      '#title' => $this->t('Publish and unpublish via local task'),
      '#default_value' => $config->get('ui_localtask'),
      '#description' => $this->t('A Publish/Unpublish link will appear alongside the node’s View and Edit links for users who have appropriate permissions.'),

    ];

    $form['ui_checkbox'] = [
      '#type' => 'checkbox',
      '#group' => 'ui',
      '#title' => $this->t('Publish and unpublish via checkbox'),
      '#default_value' => $config->get('ui_checkbox'),
      '#description' => $this->t('A checkbox will appear near the bottom of node edit forms for users who have permission to publish/unpublish. Users who do not have permission will see the checkbox but will not be able to change its value.'),
    ];

    return $form;
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
    $config = $this->config('publishcontent.settings');
    $config->set('ui_localtask', $form_state->getValue('ui_localtask'));
    $config->set('ui_checkbox', $form_state->getValue('ui_checkbox'));
    $config->save();
    return parent::submitForm($form, $form_state);
  }

}
