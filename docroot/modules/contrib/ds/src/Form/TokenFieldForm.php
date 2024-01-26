<?php

namespace Drupal\ds\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Configures token fields.
 */
class TokenFieldForm extends FieldFormBase {

  /**
   * The type of the dynamic ds field.
   */
  const TYPE = 'token';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ds_field_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $field_key = '') {
    $form = parent::buildForm($form, $form_state, $field_key);
    $field = $this->field;

    $form['content'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Field content'),
      '#default_value' => $field['properties']['content']['value'] ?? '',
      '#format' => $field['properties']['content']['format'] ?? 'plain_text',
      '#base_type' => 'textarea',
      '#required' => TRUE,
    ];

    // Token support.
    if (\Drupal::moduleHandler()->moduleExists('token')) {
      $form['tokens'] = [
        '#title' => $this->t('Tokens'),
        '#type' => 'container',
        '#states' => [
          'invisible' => [
            'input[name="use_token"]' => ['checked' => FALSE],
          ],
        ],
      ];
      $form['tokens']['help'] = [
        '#theme' => 'token_tree_link',
        '#token_types' => 'all',
        '#global_types' => FALSE,
        '#dialog' => TRUE,
      ];
      // Token options.
      $form['token_options'] = [
        '#type' => 'details',
        '#title' => $this->t('Token Options'),
        '#group' => 'advanced',
        '#open' => FALSE,
      ];
      $form['token_options']['use_global_entity'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Use the global entity (e.g. Node).'),
        '#default_value' => $field['properties']['use_global_entity'] ?? FALSE,
        '#description' => $this->t('Replace tokens using the current entity page view via merge - only if current entity is not the same page view entity.'),
      ];
      $form['token_options']['force_global_entity'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Use the global entity (e.g. Node) via Force.'),
        '#default_value' => $field['properties']['force_global_entity'] ?? FALSE,
        '#description' => $this->t('Replace tokens using the current entity page view via force override.'),
      ];
      $form['token_options']['use_global_view_token'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Use the global view token.'),
        '#default_value' => $field['properties']['use_global_view_token'] ?? FALSE,
        '#description' => $this->t('Replace tokens using the current view page instead of the view where the entity is displayed.'),
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getProperties(FormStateInterface $form_state) {
    return [
      'content' => $form_state->getValue('content'),
      'use_global_entity' => $form_state->getValue('use_global_entity'),
      'force_global_entity' => $form_state->getValue('force_global_entity'),
      'use_global_view_token' => $form_state->getValue('use_global_view_token'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return TokenFieldForm::TYPE;
  }

  /**
   * {@inheritdoc}
   */
  public function getTypeLabel() {
    return 'Token field';
  }

}
