<?php

namespace Drupal\webform_shs\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformElement\WebformTermSelect;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\webform_shs\Element\ShsTermSelect as ShsTermSelectShs;

/**
 * Provides a 'webform_shs_term_select' Webform element.
 *
 * @WebformElement(
 *   id = "webform_shs_term_select",
 *   label = @Translation("SHS term select"),
 *   description = @Translation("Provides a form element to select a single or multiple terms displayed an SHS element."),
 *   category = @Translation("Entity reference elements"),
 *   dependencies = {
 *     "taxonomy",
 *   }
 * )
 */
class ShsTermSelect extends WebformTermSelect {

  /**
   * The taxonomy term storage.
   *
   * @var \Drupal\taxonomy\TermStorageInterface
   */
  protected $termStorage;

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperties() {
    $properties = parent::getDefaultProperties() + [
      'force_deepest' => FALSE,
      'force_deepest_error' => '',
      'cache_options' => FALSE,
      'depth_labels' => [],
      'addNewLabel' => '',
    ];

    unset($properties['select2']);
    unset($properties['chosen']);
    unset($properties['breadcrumb']);
    unset($properties['breadcrumb_delimiter']);
    unset($properties['tree_delimiter']);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function getTranslatableProperties() {
    return array_merge(parent::getTranslatableProperties(), ['force_deepest_error']);
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $element_properties = $form_state->get('element_properties');

    $form['term_reference'] = [
      '#type' => 'fieldset',
      '#title' => t('Term reference settings'),
      '#weight' => -40,
    ];
    $form['term_reference']['vocabulary'] = [
      '#type' => 'webform_entity_select',
      '#title' => $this->t('Vocabulary'),
      '#target_type' => 'taxonomy_vocabulary',
      '#selection_handler' => 'default:taxonomy_vocabulary',
    ];
    $form['term_reference']['force_deepest'] = [
      '#type' => 'checkbox',
      '#title' => t('Force selection of deepest level'),
      '#default_value' => $element_properties['force_deepest'] ?? FALSE,
      '#description' => t('Force users to select terms from the deepest level.'),
    ];
    $form['term_reference']['force_deepest_error'] = [
      '#type' => 'textfield',
      '#title' => t('Custom force deepest error message'),
      '#default_value' => $element_properties['force_deepest_error'] ?? FALSE,
      '#description' => t('If set, this message will be used when a user does not choose the deepest option, instead of the default "You need to select a term from the deepest level in field X." message.'),
      '#states' => [
        'visible' => [
          ':input[name="properties[force_deepest]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['term_reference']['cache_options'] = [
      '#type' => 'checkbox',
      '#title' => t('Cache terms'),
      '#default_value' => $element_properties['cache_options'] ?? FALSE,
      '#description' => t('Speeds up the loading time for Vocabularies containing many Taxonomy Terms.'),
    ];

    $form['term_reference']['addNewLabel'] = [
      '#type' => 'textfield',
      '#title' => t('The label of "Add New Item" button'),
      '#default_value' => $element_properties['addNewLabel'] ?? '',
    ];

    $form['term_reference']['depth_labels'] = [
      '#type' => 'fieldset',
      '#title' => t('Depth Labels'),
      '#description' => t('Customize the labels that will appear in the form element for each level of depth. Fields can be left blank for the defaults.'),
      '#access' => TRUE,
      '#tree' => TRUE,
      '#prefix' => '<div id="element-depth-labels">',
      '#suffix' => '</div>',
    ];

    $deltas = $form_state->get('depth_labels_total_items') ?: (count($element_properties['depth_labels']) + 1);
    $form_state->set('depth_labels_total_items', $deltas);

    foreach (range(1, $deltas) as $delta) {
      $form['term_reference']['depth_labels'][$delta] = [
        '#access' => TRUE,
        '#title' => $this->t('Level @level', ['@level' => $delta]),
        '#type' => 'textfield',
        '#default_value' => isset($element_properties['depth_labels'][$delta - 1]) ? $element_properties['depth_labels'][$delta - 1] : '',
      ];
    }

    $form['term_reference']['depth_labels']['add'] = [
      '#type' => 'submit',
      '#value' => t('Add Label'),
      '#validate' => [],
      '#submit' => [[static::class, 'addDepthLevelSubmit']],
      '#access' => TRUE,
      '#ajax' => [
        'callback' => [static::class, 'addDepthLevelAjax'],
        'wrapper' => 'element-depth-labels',
      ],
    ];

    return $form;
  }

  /**
   * Ajax submit callback for depth labels.
   *
   * @param array $form
   *   Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state object.
   */
  public static function addDepthLevelSubmit(array $form, FormStateInterface $form_state) {
    $current_total = $form_state->get('depth_labels_total_items') ?: 1;
    $form_state->set('depth_labels_total_items', $current_total + 1);
    $form_state->setRebuild(TRUE);
  }

  /**
   * Ajax callback for the depth labels.
   *
   * @param array $form
   *   Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state object.
   */
  public static function addDepthLevelAjax(array $form, FormStateInterface $form_state) {
    return $form['properties']['term_reference']['depth_labels'];
  }

  /**
   * {@inheritdoc}
   */
  public function formatHtmlItem(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $entity = $this->getTargetEntity($element, $webform_submission, $options);
    if (!$entity) {
      return '';
    }
    $format = $this->getItemFormat($element);
    // For links, if the user has configured individual depth labels, format
    // links to the whole term tree.
    if ($format === 'link' && !empty($element['#depth_labels'])) {
      /** @var \Drupal\taxonomy\TermStorageInterface $term_storage */
      $parents = array_reverse($this->getTermStorage()
        ->loadAllParents($entity->id()));
      $output = [];
      foreach ($parents as $delta => $parent) {
        $output[] = [
          '#type' => 'container',
          'label' => [
            '#markup' => !empty($element['#depth_labels'][$delta]) ? $element['#depth_labels'][$delta] . '<span class="colon">:</span>' : '',
          ],
          'link' => [
            '#type' => 'link',
            '#title' => $parent->label(),
            '#url' => $parent->toUrl()->setAbsolute(TRUE),
          ],
        ];
      }
      return $output;
    }
    else {
      return parent::formatHtmlItem($element, $webform_submission, $options);
    }
  }

  /**
   * Get the term storage service.
   *
   * @return \Drupal\taxonomy\TermStorageInterface
   *   Taxonomy term storage.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  protected function getTermStorage() {
    if ($this->termStorage === NULL) {
      // Don't attempt to follow constructor changes in webform. Changes cross
      // versions make it impossible to support multiple versions with
      // constructor injection.
      $this->termStorage = \Drupal::entityTypeManager()
        ->getStorage('taxonomy_term');
    }
    return $this->termStorage;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfigurationFormProperties(array &$form, FormStateInterface $form_state) {
    // The webform properties/form/configuration API doesn't support complex
    // form structures. Extract the depth labels out of the form state directly.
    $properties = parent::getConfigurationFormProperties($form, $form_state);
    $depth_labels = [];
    foreach ($form_state->getCompleteFormState()
      ->getValue('depth_labels') as $key => $value) {
      if (is_numeric($key) && !empty($value)) {
        $depth_labels[] = $value;
      }
    }
    $properties['#depth_labels'] = $depth_labels;
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  protected function setOptions(array &$element) {
    ShsTermSelectShs::setOptions($element);
  }

}
