<?php

namespace Drupal\views_autocomplete_filters\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides common methods for all Views Autocomplete Filters.
 */
trait ViewsAutocompleteFiltersTrait {

  /**
   * Add autocomplete options
   *
   * @return array
   *   Returns the options of plugin.
   */
  public function defineOptions() {
    $options = parent::defineOptions();

    $options['expose']['contains']['required'] = ['default' => FALSE, 'bool' => TRUE];

    // All possible options.
    $autocomplete_options = [
      'autocomplete_filter' => ['default' => 0],
      'autocomplete_min_chars' => ['default' => 0],
      'autocomplete_items' => ['default' => 10],
      'autocomplete_field' => ['default' => ''],
      'autocomplete_raw_suggestion' => ['default' => TRUE],
      'autocomplete_raw_dropdown' => ['default' => TRUE],
      'autocomplete_dependent' => ['default' => FALSE],
    ];
    // Get the existing options from plugin schema.
    /** @var \Drupal\Core\Config\TypedConfigManagerInterface $typed_config_manager */
    $typed_config_manager = \Drupal::service('config.typed');
    $plugin_schema_exposed_options = $typed_config_manager->getDefinition('views.filter.' . $this->getPluginId())['mapping']['expose']['mapping'];
    // Add only options defined in the plugin schema.
    $options['expose']['contains'] += array_intersect_key($autocomplete_options, $plugin_schema_exposed_options);

    return $options;
  }

  /**
   * Build the options form.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    if (!$this->canExpose() || empty($form['expose'])) {
      return;
    }

    // Build form elements for the right side of the exposed filter form.
    $states = [
      'visible' => ['
          :input[name="options[expose][autocomplete_filter]"]' => ['checked' => TRUE],
      ],
    ];

    $form['expose']['autocomplete_filter'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use Autocomplete'),
      '#default_value' => $this->options['expose']['autocomplete_filter'],
      '#description' => $this->t('Use Autocomplete for this filter.'),
    ];

    // Add autocomplete options form elements, only if they are defined.
    // The elements are visible only if the autocomplete filter is checked.
    if (array_key_exists('autocomplete_items', $this->options['expose'])) {
      $form['expose']['autocomplete_items'] = [
        '#type' => 'number',
        '#title' => $this->t('Maximum number of items in Autocomplete'),
        '#default_value' => $this->options['expose']['autocomplete_items'],
        '#description' => $this->t('Enter 0 for no limit.'),
        '#min' => 0,
        '#states' => $states,
      ];
    }

    if (array_key_exists('autocomplete_min_chars', $this->options['expose'])) {
      $form['expose']['autocomplete_min_chars'] = [
        '#type' => 'number',
        '#title' => $this->t('Minimum number of characters to start filter'),
        '#default_value' => $this->options['expose']['autocomplete_min_chars'],
        '#min' => 0,
        '#states' => $states,
      ];
    }

    if (array_key_exists('autocomplete_dependent', $this->options['expose'])) {
      $form['expose']['autocomplete_dependent'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Suggestions depend on other filter fields'),
        '#default_value' => $this->options['expose']['autocomplete_dependent'],
        '#description' => $this->t('Autocomplete suggestions will be filtered by other filter fields'),
        '#states' => $states,
      ];
    }

    if (array_key_exists('autocomplete_field', $this->options['expose'])) {
      $field_options = $this->getFieldOptions();
      // Get the autocomplete field with same nane if exists.
      if (empty($this->options['expose']['autocomplete_field']) && !empty($field_options[$this->options['id']])) {
        $this->options['expose']['autocomplete_field'] = $this->options['id'];
      }

      $form['expose']['autocomplete_field'] = [
        '#type' => 'select',
        '#title' => $this->t('Field with autocomplete results'),
        '#default_value' => $this->options['expose']['autocomplete_field'] ?? '',
        '#options' => $field_options,
        '#description' => $this->t('The selected field will be used for drop-down results of the autocompletion. In most cases it should be the same field you use for the filter, and <em>must</em> be included in the field list for the view/display in order to appear here.'),
        '#states' => $states,
      ];
    }

    if (array_key_exists('autocomplete_raw_dropdown', $this->options['expose'])) {
      $form['expose']['autocomplete_raw_dropdown'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Unformatted dropdown'),
        '#default_value' => $this->options['expose']['autocomplete_raw_dropdown'],
        '#description' => $this->t('Use unformatted data from database for dropdown list instead of field formatter result. Value will be printed as plain text.'),
        '#states' => $states,
      ];
    }

    if (array_key_exists('autocomplete_raw_suggestion', $this->options['expose'])) {
      $form['expose']['autocomplete_raw_suggestion'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Unformatted suggestion'),
        '#default_value' => $this->options['expose']['autocomplete_raw_suggestion'],
        '#description' => $this->t('The same as above, but for suggestion (text appearing inside textfield when item is selected).'),
        '#states' => $states,
      ];
    }
  }

  /**
   * Fetches the autocomplete field options.
   *
   * @return array
   *   The list of options.
   */
  protected function getFieldOptions() {
    $field_options = [];

    // Limit options to fields with the same name.
    /** @var \Drupal\views\Plugin\views\field\FieldHandlerInterface $handler */
    foreach ($this->view->display_handler->getHandlers('field') as $id => $handler) {
      if (in_array($this->realField, [
        $handler->field,
        $handler->field . '_value',
        $handler->realField . '_value',
      ])) {
        $field_options_all = $this->view->display_handler->getFieldLabels();
        $field_options[$id] = $field_options_all[$id];
      }
    }

    if (empty($field_options)) {
      $field_options[''] = $this->t('Add some fields to view');
    }

    return $field_options;
  }

  public function valueForm(&$form, FormStateInterface $form_state) {
    parent::valueForm($form, $form_state);
    $exposed = $form_state->get('exposed');
    if (!$exposed || empty($this->options['expose']['autocomplete_filter'])) {
      // It is not an exposed form or autocomplete is not enabled.
      return;
    }

    if (empty($form['value']['#type']) || $form['value']['#type'] !== 'textfield') {
      // Not a textfield.
      return;
    }

    // Add autocomplete path to the exposed textfield.
    $view_args = !empty($this->view->args) ? implode('||', $this->view->args) : 0;
    $form['value']['#autocomplete_route_name'] = 'viewsfilters.autocomplete';
    $form['value']['#autocomplete_route_parameters'] = [
      'view_name' => $this->view->storage->get('id'),
      'view_display' => $this->view->current_display,
      'filter_name' => $this->options['id'],
      'view_args' => $view_args,
    ];

    // Add JS script to expands the behaviour of the default autocompletion.
    // Override the "select" option of the jQueryUI auto-complete for
    // to make sure we do not use quotes for inputs with comma.
    $form['#attached']['library'][] = 'views_autocomplete_filters/drupal.views-autocomplete-filters';

    // Add JS script with core autocomplete overrides to the end of JS files
    // list to be sure it is added after the "misc/autocomplete.js" file. Also
    // mark the field with special class.
    if (!empty($this->options['expose']['autocomplete_dependent'])) {
      $form['#attached']['library'][] = 'views_autocomplete_filters/drupal.views-autocomplete-filters-dependent';
      $form['value']['#attributes']['class'][] = 'views-ac-dependent-filter';
    }
  }

}
