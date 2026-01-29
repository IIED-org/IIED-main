<?php

declare(strict_types=1);

namespace Drupal\taxonomy_manager_merge\Form;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\Core\Url;
use Drupal\taxonomy\VocabularyInterface;
use Drupal\taxonomy_manager\Form\TaxonomyManagerForm;
use Drupal\term_merge\Form\MergeTerms;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for merging the selected terms into a target term.
 */
class MergeTermsForm extends MergeTerms {

  use StringTranslationTrait;

  /**
   * The vocabulary.
   *
   * @var \Drupal\taxonomy\VocabularyInterface
   */
  protected VocabularyInterface $vocabulary;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected ModuleHandlerInterface $moduleHandler;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected Connection $database;

  /**
   * Constructs a MergeTermsForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity manager service.
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $tempStoreFactory
   *   The private temporary storage factory service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    PrivateTempStoreFactory $tempStoreFactory,
    ModuleHandlerInterface $moduleHandler,
    Connection $database,
  ) {
    parent::__construct($entityTypeManager, $tempStoreFactory);
    $this->moduleHandler = $moduleHandler;
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): MergeTermsForm {
    // @phpstan-ignore-next-line
    return new static(
      $container->get('entity_type.manager'),
      $container->get('tempstore.private'),
      $container->get('module_handler'),
      $container->get('database')
    );
  }

  /**
   * Form constructor.
   *
   * @param array $form
   *   Form render array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   * @param \Drupal\taxonomy\VocabularyInterface|null $taxonomy_vocabulary
   *   Selected terms.
   * @param array $selected_terms
   *   Selected terms.
   *
   * @return array
   *   Return render array of form or nothing if term_merge module not exists.
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?VocabularyInterface $taxonomy_vocabulary = NULL, array $selected_terms = []): array {
    $form = parent::buildForm($form, $form_state, $taxonomy_vocabulary);
    if (is_null($taxonomy_vocabulary)) {
      return $form;
    }

    // We still need a private storage.
    $form_state->disableCache();
    $this->vocabulary = $taxonomy_vocabulary;

    $form['description'] = [
      '#type' => 'markup',
      '#markup' => $this->t('Select terms to merge'),
      '#weight' => -1,
    ];
    $form['terms']['#ajax'] = [
      'url' => Url::fromRoute(
        'taxonomy_manager_merge.admin_vocabulary.merge_terms',
        [
          'taxonomy_vocabulary' => $taxonomy_vocabulary->id(),
        ],
      ),
      'options' => [
        'query' => [
          FormBuilderInterface::AJAX_FORM_REQUEST => TRUE,
        ],
      ],
      'event' => 'change',
      'callback' => '::rebuildExistingTermsOptions',
      'wrapper' => 'existing-terms',
      'disable-refocus' => FALSE,
    ];

    /** @var array $selected_terms */
    $selected_terms = count($selected_terms) > 0 ? $selected_terms : (!empty($form_state->getUserInput()['terms']) ? $form_state->getUserInput()['terms'] : []);
    $form['terms']['#default_value'] = $selected_terms;
    // Limit options due to memory issues on large vocabularies.
    $form['terms']['#options'] = $this->getSelectedOrAllTermOptions($taxonomy_vocabulary, $selected_terms);

    // Optionally add all child terms to the target term,
    // that means that child terms will get new parent.
    $form['add_child_terms_to_target'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Add child terms to target'),
      '#description' => $this->t('Allow all sub children to be merged into the target term otherwise child terms gets a new parent.'),
    ];

    $form['terms_to_synonym'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Add source term as synonym'),
      '#description' => $this->t('Selected terms titles will get moved to the target term.'),
    ];

    $form['description']['#markup'] = $this->t('Enter a new term or select an existing term to merge into.');

    $form['new'] = [
      '#type' => 'textfield',
      '#title' => $this->t('New term'),
    ];

    $form['existing'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Existing term'),
      '#target_type' => 'taxonomy_term',
      '#selection_handler' => 'default:filter_existing_terms',
      '#selection_settings' => [
        'target_bundles' => [
          $taxonomy_vocabulary->id(),
        ],
      ],
      '#empty_option' => $this->t('Select an existing term'),
      '#prefix' => '<div id="existing-terms">',
      '#suffix' => '</div>',
    ];

    if (count($selected_terms) > 0) {
      $form['existing']['#selection_settings']['filter'] = ['tid' => $selected_terms];
    }

    return $form;
  }

  /**
   * Rebuild existing element with new data.
   *
   * @param array $form
   *   Form render array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   *
   * @return array
   *   Returns existing terms element with new data.
   */
  public function rebuildExistingTermsOptions(array $form, FormStateInterface $form_state): array {
    return $form['existing'];
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    parent::validateForm($form, $form_state);

    $new = !empty($form_state->getValue('new'));
    $existing = !empty($form_state->getValue('existing'));

    if ($new !== $existing) {
      return;
    }

    $form_state->setErrorByName('existing', (string) $this->t('You must either select an existing term or enter a new term.'));
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $term_store = $this->tempStoreFactory->get('term_merge');
    /** @var array $selected_terms */
    $selected_terms = $form_state->getValue('terms', []);
    if (count($selected_terms) > 0) {
      $term_store->set('terms', $selected_terms);
    }

    if (!empty($form_state->getValue('new'))) {
      $term_store->set('target', $form_state->getValue('new'));
    }

    if (!empty($form_state->getValue('existing'))) {
      $term = $this->termStorage->load($form_state->getValue('existing'));
      $term_store->set('target', $term);
    }

    if (!empty($form_state->getValue('add_child_terms_to_target')) || $form_state->hasValue('add_child_terms_to_target')) {
      $term_store->set('merge_children', $form_state->getValue('add_child_terms_to_target'));
    }

    if (!empty($form_state->getValue('terms_to_synonym')) || $form_state->hasValue('terms_to_synonym')) {
      $term_store->set('terms_to_synonym', $form_state->getValue('terms_to_synonym'));
    }

    $route_name = 'entity.taxonomy_vocabulary.merge_confirm';
    $route_parameters = [
      'taxonomy_vocabulary' => $this->vocabulary->id(),
    ];
    // We have to specify destination to redirect back to the vocabulary.
    // It can happen due to internal redirect from the term merge module to
    // taxonomy list overview.
    $destination = Url::fromRoute('taxonomy_manager.admin_vocabulary', ['taxonomy_vocabulary' => $this->vocabulary->id()]);
    $form_state->setRedirect($route_name, $route_parameters, ['query' => ['destination' => $destination->toString()]]);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'taxonomy_manager_merge_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getTermOptions(VocabularyInterface $vocabulary) {
    // Parent method is overridden with a faster implementation.
    // @see \Drupal\taxonomy_manager\Form\MergeTermsForm::getSelectedOrAllTermOptions()
    return [];
  }

  /**
   * Builds a list of all or selected terms in this vocabulary.
   *
   * @param \Drupal\taxonomy\VocabularyInterface $vocabulary
   *   The vocabulary.
   * @param array $selected_terms
   *   The selected terms to merge.
   * @param int $limit
   *   The limit of terms to load after which is used only selected terms.
   *
   * @return string[]
   *   An array of taxonomy term labels keyed by their id.
   */
  protected function getSelectedOrAllTermOptions(VocabularyInterface $vocabulary, array $selected_terms, int $limit = 1000): array {
    $options = [];
    $query = $this->database->select('taxonomy_term_field_data', 't')
      ->fields('t', ['tid', 'name'])
      ->condition('vid', $vocabulary->id())
      ->orderBy('name');
    $total = (clone $query)->countQuery()->execute()->fetchField();

    if ($total < $limit) {
      $options = $query
        ->execute()
        ->fetchAllKeyed();
    }
    if ($total > $limit) {
      $options = $query
        ->condition('tid', $selected_terms, 'IN')
        ->execute()
        ->fetchAllKeyed();
    }
    asort($options);

    return $options;
  }

  /**
   * AJAX callback handler for merge terms to a target term.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state of the current (parent) form.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The ajax response.
   */
  public static function mergeTermsFormCallback(array $form, FormStateInterface $form_state) {
    return TaxonomyManagerForm::modalHelperStatic($form_state, self::class, 'taxonomy_manager_merge.admin_vocabulary.merge_terms', (string) t('Merge terms'));
  }

}
