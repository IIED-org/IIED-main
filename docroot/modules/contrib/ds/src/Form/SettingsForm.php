<?php

namespace Drupal\ds\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\ds\Ds;

/**
 * Configures Display Suite settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ds_admin_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('ds.settings');

    $form['additional_settings'] = [
      '#type' => 'vertical_tabs',
      '#attached' => [
        'library' => ['ds/admin'],
      ],
    ];

    $form['fs1'] = [
      '#type' => 'details',
      '#title' => $this->t('Field Templates'),
      '#group' => 'additional_settings',
      '#weight' => 1,
      '#tree' => TRUE,
      '#collapsed' => FALSE,
    ];

    $form['fs1']['field_template'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Field Templates'),
      '#description' => $this->t('Customize the labels and the HTML output of your fields.'),
      '#default_value' => $config->get('field_template'),
    ];

    $form['fs1']['ft_expert_prefix_suffix_textarea'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use a textarea for the prefix and suffix in the expert template config form'),
      '#description' => $this->t('The default is a textfield which is limited to 128 characters.'),
      '#default_value' => $config->get('ft_expert_prefix_suffix_textarea'),
      '#states' => [
        'visible' => [
          'input[name="fs1[field_template]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['fs1']['ft_layout_builder'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable field templates in Layout Builder'),
      '#description' => $this->t('Enable field templates on Layout Builder field blocks. Note that disabling this after having configured layout builder field templates will require a cache clear.'),
      '#default_value' => $config->get('ft_layout_builder'),
      '#states' => [
        'visible' => [
          'input[name="fs1[field_template]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $theme_functions = Ds::getFieldLayoutOptions();
    $url = new Url('ds.classes');
    $description = $this->t('<ul><li>Default will output the field as defined in Drupal Core.' .
      '<li>Reset will strip all HTML.' .
      '<li>Minimal adds a simple wrapper around the field.' .
      '<li>Expert Field Template gives full control over the HTML. For some wrappers you can select whether default classes are added, but these do not contain the classes which are added in the core field.html.twig file!</ul>' .
      'You can override this setting per field on the "Manage display" screens or when creating fields on the instance level.<br/><br/>' .
      '<strong>Template suggestions</strong>: ' .
      'You can create .html.twig files as well for these field theme functions, e.g. field--reset.html.twig, field--minimal.html.twig<br/><br/>' .
      '<strong>CSS classes</strong>: You can add custom CSS classes on the <a href=":url">classes form</a>. These classes can be added to fields using the Default Field Template.<br/><br/>' .
      '<strong>Advanced</strong>: You can create your own custom field templates plugin. See Drupal\ds_test\Plugin\DsFieldTemplate for an example.', [':url' => $url->toString()]);

    $form['fs1']['ft_default'] = [
      '#type' => 'select',
      '#title' => $this->t('Default Field Template'),
      '#options' => $theme_functions,
      '#default_value' => $config->get('ft_default'),
      '#description' => $description,
      '#states' => [
        'visible' => [
          'input[name="fs1[field_template]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['fs1']['ft_show_colon'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show colon'),
      '#default_value' => $config->get('ft_show_colon'),
      '#description' => $this->t('Show the colon on the reset field template.'),
      '#states' => [
        'visible' => [
          'select[name="fs1[ft_default]"]' => ['value' => 'reset'],
          'input[name="fs1[field_template]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['fs3'] = [
      '#type' => 'details',
      '#title' => $this->t('Other'),
      '#group' => 'additional_settings',
      '#weight' => 3,
      '#tree' => TRUE,
    ];
    $form['fs3']['use_field_names'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use field names in templates'),
      '#default_value' => $config->get('use_field_names'),
      '#description' => $this->t('Use field names in twig templates instead of the key'),
    ];
    $form['fs3']['exclude_layout_builder_blocks_on_block_field'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Exclude Layout Builder blocks on the Block field form'),
      '#default_value' => $config->get('exclude_layout_builder_blocks_on_block_field'),
    ];
    $form['fs3']['exclude_ds_layout_layout_builder'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Exclude Display Suite layouts in Layout Builder'),
      '#default_value' => $config->get('exclude_ds_layout_layout_builder'),
    ];

    $form['fs4'] = [
      '#type' => 'details',
      '#title' => $this->t('BC settings'),
      '#group' => 'additional_settings',
      '#weight' => 4,
      '#tree' => TRUE,
      '#collapsed' => FALSE,
    ];

    $form['fs4']['ft_bc'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use original field templates'),
      '#description' => $this->t('Field templates were changed in DS 8.x-3.17. Toggle this checkbox in case you want to use the templates from before.') . '<br />' . $this->t('This setting will be disabled in case field templates are not enabled.'),
      '#default_value' => $config->get('ft_bc'),
      '#states' => [
        'disabled' => [
          'input[name="fs1[field_template]"]' => ['checked' => FALSE],
        ],
      ],
    ];

    $form['fs4']['layout_icon_image_bc'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use Display Suite icon images'),
      '#description' => $this->t('When selecting a layout either on the manage display pages or layout builder (or else), use the original Display Suite images. Otherwise, use the icon map configuration.') . '<br />' . $this->t('Introduced in DS 8.x-3.17.'),
      '#default_value' => $config->get('layout_icon_image_bc'),
    ];

    $form['fs4']['layout_suggestion_bc'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Undo layout suggestions fix'),
      '#description' => $this->t('Layout suggestions provided by Display Suite were using the internal ID which caused problems in some cases. The theme hook is now used.') . '<br />' . $this->t('You can safely ignore this setting on new installations.') . ' '  . $this->t('Introduced in DS 8.x-3.17.'),
      '#default_value' => $config->get('layout_suggestion_bc'),
    ];

    $form['fs4']['ft_default_bc'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Undo default field template fix'),
      '#description' => $this->t('When setting the default field template to something different than "Default" (e.g. Minimal), the selected template would still be the core field template in case you did not change any formatter settings for a field.') . '<br />' . $this->t('You can safely ignore this setting on new installations.') . ' ' . $this->t('This setting will be disabled in case field templates are not enabled.') . ' '  . $this->t('Introduced in DS 8.x-3.17.'),
      '#default_value' => $config->get('ft_default_bc'),
      '#states' => [
        'disabled' => [
          'input[name="fs1[field_template]"]' => ['checked' => FALSE],
        ],
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $values = $form_state->getValues();
    $this->config('ds.settings')
      ->set('field_template', $values['fs1']['field_template'])
      ->set('ft_default', $values['fs1']['ft_default'])
      ->set('ft_expert_prefix_suffix_textarea', $values['fs1']['ft_expert_prefix_suffix_textarea'])
      ->set('ft_show_colon', $values['fs1']['ft_show_colon'])
      ->set('ft_layout_builder', $values['fs1']['ft_layout_builder'])
      ->set('use_field_names', $values['fs3']['use_field_names'])
      ->set('exclude_layout_builder_blocks_on_block_field', $values['fs3']['exclude_layout_builder_blocks_on_block_field'])
      ->set('exclude_ds_layout_layout_builder', $values['fs3']['exclude_ds_layout_layout_builder'])
      ->set('ft_bc', $values['fs4']['ft_bc'])
      ->set('ft_default_bc', $values['fs4']['ft_default_bc'])
      ->set('layout_icon_image_bc', $values['fs4']['layout_icon_image_bc'])
      ->set('layout_suggestion_bc', $values['fs4']['layout_suggestion_bc'])
      ->save();

    drupal_flush_all_caches();
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'ds.settings',
    ];
  }

}
