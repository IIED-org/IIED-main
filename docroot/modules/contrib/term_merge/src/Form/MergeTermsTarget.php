<?php

namespace Drupal\term_merge\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\taxonomy\VocabularyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Term merge target terms form.
 */
class MergeTermsTarget extends FormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The term storage handler.
   *
   * @var \Drupal\taxonomy\TermStorageInterface
   */
  protected $termStorage;

  /**
   * The private temporary storage factory.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  private $tempStoreFactory;

  /**
   * The vocabulary.
   *
   * @var \Drupal\taxonomy\VocabularyInterface
   */
  private $vocabulary;

  /**
   * Constructs an OverviewTerms object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity manager service.
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $tempStoreFactory
   *   The private temporary storage factory.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, PrivateTempStoreFactory $tempStoreFactory) {
    $this->entityTypeManager = $entityTypeManager;
    $this->termStorage = $entityTypeManager->getStorage('taxonomy_term');
    $this->tempStoreFactory = $tempStoreFactory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('tempstore.private')
    );
  }

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'taxonomy_merge_terms_target';
  }

  /**
   * Callback for the form title.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The title.
   *
   * @SuppressWarnings(camelCase)
   */
  public function titleCallback() {
    return $this->t('Please select a target term');
  }

  /**
   * {@inheritdoc}
   *
   * @SuppressWarnings(camelCase)
   */
  public function buildForm(array $form, FormStateInterface $form_state, VocabularyInterface $taxonomy_vocabulary = NULL) {
    $this->vocabulary = $taxonomy_vocabulary;

    $form['description']['#markup'] = $this->t('Please enter a new term or select an existing term to merge into.');

    $form['new'] = [
      '#type' => 'textfield',
      '#title' => $this->t('New term'),
    ];

    $form['existing'] = [
      '#type' => 'select',
      '#title' => $this->t('Existing term'),
      '#empty_option' => $this->t('Select an existing term'),
      '#options' => $this->buildExistingTermsOptions(),
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#button_type' => 'primary',
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * @SuppressWarnings(camelCase)
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $new = !empty($form_state->getValue('new'));
    $existing = !empty($form_state->getValue('existing'));

    if ($new !== $existing) {
      return;
    }

    $form_state->setErrorByName('new', $this->t('You must either select an existing term or enter a new term.'));
  }

  /**
   * {@inheritdoc}
   *
   * @SuppressWarnings(camelCase)
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if (!empty($form_state->getValue('new'))) {
      $this->getTempStore()->set('target', $form_state->getValue('new'));
    }

    if (!empty($form_state->getValue('existing'))) {
      $term = $this->termStorage->load($form_state->getValue('existing'));
      $this->getTempStore()->set('target', $term);
    }

    $routeName = 'entity.taxonomy_vocabulary.merge_confirm';
    $routeParameters['taxonomy_vocabulary'] = $this->vocabulary->id();
    $form_state->setRedirect($routeName, $routeParameters);
  }

  /**
   * Builds an array of existing terms.
   *
   * @return string[]
   *   Existing term labels keyed by id.
   */
  private function buildExistingTermsOptions() {
    $query = $this->termStorage->getQuery();
    $query->condition('vid', $this->vocabulary->id())
      ->condition('tid', $this->getSelectedTermIds(), 'NOT IN');

    $terms = $this->termStorage->loadMultiple($query->execute());

    $options = [];
    foreach ($terms as $term) {
      $options[$term->id()] = $term->label();
    }

    return $options;
  }

  /**
   * Retrieves the selected term ids from the temp store.
   *
   * @return array
   *   The selected term ids.
   */
  private function getSelectedTermIds() {
    return (array) $this->getTempStore()->get('terms');
  }

  /**
   * Retrieves the term_merge private temp store.
   *
   * @return \Drupal\user\PrivateTempStore
   *   The private temp store.
   */
  private function getTempStore() {
    return $this->tempStoreFactory->get('term_merge');
  }

}
