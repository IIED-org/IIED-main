<?php

namespace Drupal\configurable_views_filter_block\Plugin\Block;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\views\Plugin\Block\ViewsExposedFilterBlock as CoreViewsExposedFilterBlock;

/**
 * Views Exposed Filter block type with configurable form fields per instance.
 *
 * @Block(
 *   id = "configurable_views_filter_block_block",
 *   admin_label = @Translation("Views Exposed Filter Block (configurable form)"),
 *   deriver = "Drupal\views\Plugin\Derivative\ViewsExposedFilterBlock"
 * )
 */
class ConfigurableViewsExposedFilterBlock extends CoreViewsExposedFilterBlock {

  /**
   * The views filters.
   *
   * @var array
   */
  protected $filters = NULL;

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'visible_filters' => [],
      'no_groups' => FALSE,
      'no_reset' => FALSE,
      'no_sort' => FALSE,
      'no_pager' => FALSE,
      'filter_instance_id' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    // Visible filters.
    $this->buildFilterConfigurationFormOptions($form, $form_state);

    // Additional visibility options.
    $this->buildAdditionalConfigurationFormOptions($form, $form_state);

    return $form;
  }

  /**
   * Build filters configuration form options.
   *
   * @param array $form
   *   The configuration form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  protected function buildFilterConfigurationFormOptions(array &$form, FormStateInterface $form_state) {
    $options = $this->getExposedFilters();

    $form['visible_filters'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Visible filters'),
      '#options' => $options,
      '#default_value' => array_filter($this->configuration['visible_filters']),
    ];

    if (empty($options)) {
      $form['visible_filters']['info'] = ['#markup' => $this->t('The view has no exposed filters.')];
    }
  }

  /**
   * Build additional configuration form options.
   *
   * @param array $form
   *   The configuration form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  protected function buildAdditionalConfigurationFormOptions(array &$form, FormStateInterface $form_state) {
    $options = [
      'no_groups' => $this->t('Remove form groups'),
    ];
    $default_value = [
      'no_groups' => $this->configuration['no_groups'] ? 'no_groups' : FALSE,
    ];

    // Reset option only if the reset button is enabled.
    if (($exposed_form_plugin = $this->view->display_handler->getPlugin('exposed_form'))
      && !empty($exposed_form_plugin->options['reset_button'])) {
      $options['no_reset'] = $this->t('Remove reset button');
      $default_value['no_reset'] = $this->configuration['no_reset'] ? 'no_reset' : FALSE;
    }

    // Sort option only if some sort criteria is exposed in view.
    if ($this->viewsHasExposedSorts()) {
      $options['no_sort'] = $this->t('Hide sort criteria');
      $default_value['no_sort'] = $this->configuration['no_sort'] ? 'no_sort' : FALSE;
    }

    // Pager options only if exposed.
    if ($this->view->getPager()->usesExposed()) {
      $options['no_pager'] = $this->t('Hide pager options');
      $default_value['no_pager'] = $this->configuration['no_pager'] ? 'no_pager' : FALSE;
    }

    $form['form_options'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Other visibility options'),
      '#options' => $options,
      '#default_value' => $default_value,
    ];

    $form['filter_instance_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Filter Instance ID'),
      '#description' => $this->t('Enter a unique identifier for this filter instance.'),
      '#default_value' => $this->configuration['filter_instance_id'],
      '#size' => 30,
    ];
  }

  /**
   * Checks if the current view has exposed sorts.
   *
   * @return bool
   *  TRUE if the view has exposed sorts, FALSE otherwise.
   */
  protected function viewsHasExposedSorts() {
    $sorts = $this->view->display_handler->getHandlers('sort');
    foreach ($sorts as $sort_plugin) {
      if ($sort_plugin->isExposed()) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);

    $this->configuration['visible_filters'] = array_filter($form_state->getValue('visible_filters'));

    $form_options = $form_state->getValue('form_options');
    $this->configuration['no_groups'] = !empty($form_options['no_groups']);
    $this->configuration['no_reset'] = !empty($form_options['no_reset']);
    $this->configuration['no_sort'] = !empty($form_options['no_sort']);
    $this->configuration['no_pager'] = !empty($form_options['no_pager']);
    $this->configuration['filter_instance_id'] = $form_state->getValue('filter_instance_id');
  }

  /**
   * {@inheritdoc}
   */
  public function build() : array {
    $build = parent::build();

    // Apply visibility options.
    $this->applyVisibilityOptions($build);

    if ($instance_id = $this->configuration['filter_instance_id']) {
      // Stamp the property, not an HTML attribute.
      $this->applyInstanceIdProperty($build, $instance_id);
    }

    // Set an new unique form id from the build id if available, or by adding a
    // random number to the current id.
    $new_form_id = isset($build['#build_id']) ? $build['#build_id'] : $build['#id'] . rand(100, 999);
    $build['#id'] = $new_form_id;
    $build['#attributes']['data-drupal-selector'] = $new_form_id;

    // Add CSS.
    $build['#attached']['library'][] = 'configurable_views_filter_block/configurable_views_filter_block.theme';

    return $build;
  }

  /**
   * Apply instance ID property to the element and its children.
   *
   * Recursive method.
   *
   * @param array $element
   *   The current element to process, passed by reference.
   * @param string $instance_id
   *   The instance ID to apply to the element.
   */
  protected function applyInstanceIdProperty(array &$element, string $instance_id): void {
    $element['#filter_instance_id'] = $instance_id;

    foreach (Element::children($element) as $child) {
      $this->applyInstanceIdProperty($element[$child], $instance_id);
    }
  }

  /**
   * Apply visibility options to the exposed filters form or element.
   *
   * Recursive method.
   *
   * @param array $element
   *   The current element to process.
   */
  protected function applyVisibilityOptions(array &$element) {
    foreach (Element::children($element) as $child_name) {
      if (!$this->applyCommonVisibilityOptions($child_name, $element[$child_name])
        && !$this->applyFieldVisibilityOptions($child_name, $element[$child_name])) {

        // Check and apply fieldset visibility options.
        $this->applyFieldsetVisibilityOptions($child_name, $element[$child_name]);

        // Check inner elements for field groups and unmanaged form elements.
        $this->applyVisibilityOptions($element[$child_name]);
      }
    }
  }

  /**
   * Checks and apply common visibility options to a given form element.
   *
   * @param string $name
   *   The element name in the build array.
   * @param array $element
   *   The element to evaluate and be processed.
   *
   * @return bool
   *   Boolean indicating if the options were actually applied.
   */
  protected function applyCommonVisibilityOptions($name, array &$element) {
    $applied = FALSE;
    switch ($name) {
      case 'reset':
        // Found the reset submit button.
        if ($applied = !empty($this->configuration['no_reset'])) {
          $this->hideElement($element);
        }
        break;

      case 'sort_by':
      case 'sort_order':
        // Sort form fields found.
        if ($applied = !empty($this->configuration['no_sort'])) {
          $this->hideElement($element);
        }
        break;

      case 'items_per_page':
      case 'offset':
        // Pager options related field found.
        if ($applied = !empty($this->configuration['no_pager'])) {
          $this->hideElement($element);
        }
        break;
    }

    return $applied;
  }

  /**
   * Checks and apply field visibility options to a given form element.
   *
   * @param string $name
   *   The element name in the build array.
   * @param array $element
   *   The element to evaluate and be processed.
   *
   * @return bool
   *   Boolean indicating if the options were actually applied.
   */
  protected function applyFieldVisibilityOptions($name, array &$element) {
    $filters = $this->getExposedFilters();
    if (!isset($filters[$name])) {
      // Element is not a filter field.
      return FALSE;
    }

    if (!in_array($name, $this->configuration['visible_filters'])) {
      // Non visible field found, it will be hide.
      $this->hideElement($element);
    }

    return TRUE;
  }

  /**
   * Checks and apply fieldset visibility options to a given form element.
   *
   * @param string $name
   *   The element name in the build array.
   * @param array $element
   *   The element to evaluate and be processed.
   *
   * @return bool
   *   Boolean indicating if the options were actually applied.
   */
  protected function applyFieldsetVisibilityOptions($name, array &$element) {
    // Fieldsets have wrapper elements containing the legend, so we need to hide
    // the full fieldset.
    if (
      isset($element['#type']) &&
      $element['#type'] == 'fieldset' &&
      str_ends_with($name, '_wrapper')
    ) {
      // Get the name of the actual element that is wrapped.
      $str = substr($name, 0, strlen($name) - 8);
      if (!in_array($str, $this->configuration['visible_filters'])) {
        $this->hideElement($element);
        return TRUE;
      }
    }

    $applies = isset($element['#type'])
      && $element['#type'] == 'details'
      && $this->configuration['no_groups'];

    // Convert collapsible containers into simple containers.
    if ($applies) {
      $element['#type'] = 'container';
      unset($element['#title']);
      unset($element['#open']);
      $element['#theme_wrappers'] = ['container'];
    }

    return $applies;
  }

  /**
   * Get the view existing exposed filters.
   *
   * @return array
   *   The view's exposed filter labels keyed by name.
   */
  protected function getExposedFilters() {
    if ($this->filters === NULL) {
      $this->filters = [];
      foreach ($this->view->display_handler->getHandlers('filter') as $plugin) {
        if ($plugin->isExposed() && $exposed_info = $plugin->exposedInfo()) {
          $this->filters[$exposed_info['value']] = trim($exposed_info['label'] . ' (' . $exposed_info['value'] . ')');
        }
      }
    }

    return $this->filters;
  }

  /**
   * Hides a given element.
   *
   * Current form field values are lost by using the "#access" property, so
   * form fields are only visually hidden.
   *
   * @param array $element
   *   The form element to be hide.
   */
  protected function hideElement(&$element) {
    $element['#prefix'] = '<div class="hidden-exposed-filter">';
    $element['#suffix'] = '</div>';
  }

}
