<?php

namespace Drupal\term_merge\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\taxonomy\TermInterface;
use Drupal\taxonomy\VocabularyInterface;
use Drupal\term_merge\TermMergerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Term merge confirm form.
 */
class MergeTermsConfirm extends FormBase {

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
   * The term merger.
   *
   * @var \Drupal\term_merge\TermMergerInterface
   */
  private $termMerger;

  /**
   * The vocabulary.
   *
   * @var \Drupal\taxonomy\VocabularyInterface
   */
  private $vocabulary;

  /**
   * Constructs a MergeTermsConfirm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity manager service.
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $tempStoreFactory
   *   The private temporary storage factory.
   * @param \Drupal\term_merge\TermMergerInterface $termMerger
   *   The term merger service.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, PrivateTempStoreFactory $tempStoreFactory, TermMergerInterface $termMerger) {
    $this->entityTypeManager = $entityTypeManager;
    $this->termStorage = $entityTypeManager->getStorage('taxonomy_term');
    $this->tempStoreFactory = $tempStoreFactory;
    $this->termMerger = $termMerger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('tempstore.private'),
      $container->get('term_merge.term_merger')
    );
  }

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'taxonomy_merge_terms_confirm';
  }

  /**
   * {@inheritdoc}
   *
   * @SuppressWarnings(camelCase)
   * @SuppressWarnings("else")
   */
  public function buildForm(array $form, FormStateInterface $form_state, VocabularyInterface $taxonomy_vocabulary = NULL) {
    $this->vocabulary = $taxonomy_vocabulary;
    $selectedTermIds = $this->getSelectedTermIds();

    if (empty($selectedTermIds)) {
      $this->messenger()->addError($this->t("You must submit at least one term."), 'error');
      return $form;
    }

    $target = $this->tempStoreFactory->get('term_merge')->get('target');

    if (!is_string($target) && !$target instanceof TermInterface) {
      throw new \LogicException("Invalid target type. Should be string or implement TermInterface");
    }

    $arguments = [
      '%termCount' => count($selectedTermIds),
      '%termName' => is_string($target) ? $target : $target->label(),
    ];

    if (is_string($target)) {
      $form['message']['#markup'] = $this->t("You are about to merge %termCount terms into new term %termName. This action can't be undone. Are you sure you wish to continue with merging the terms below?", $arguments);
    }
    else {
      $form['message']['#markup'] = $this->t("You are about to merge %termCount terms into existing term %termName. This action can't be undone. Are you sure you wish to continue with merging the terms below?", $arguments);
    }

    $form['terms'] = [
      '#title' => $this->t("Terms to be merged"),
      '#theme' => 'item_list',
      '#items' => $this->getSelectedTermLabels(),
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#button_type' => 'primary',
      '#type' => 'submit',
      '#value' => $this->t('Confirm merge'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * @SuppressWarnings(camelCase)
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $selectedTerms = $this->loadSelectedTerms();

    $target = $this->tempStoreFactory->get('term_merge')->get('target');
    if (is_string($target)) {
      $this->termMerger->mergeIntoNewTerm($selectedTerms, $target);
      $this->setSuccessfullyMergedMessage(count($selectedTerms), $target);
      $this->redirectToTermMergeForm($form_state);
      return;
    }

    $this->termMerger->mergeIntoTerm($selectedTerms, $target);
    $this->setSuccessfullyMergedMessage(count($selectedTerms), $target->label());
    $this->redirectToTermMergeForm($form_state);
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
    $termCount = count($this->getSelectedTermIds());

    $arguments = ['%termCount' => $termCount];
    return $this->t("Are you sure you wish to merge %termCount terms?", $arguments);
  }

  /**
   * Gets a list of selected term ids from the temp store.
   *
   * @return int[]
   *   The selected term ids.
   */
  private function getSelectedTermIds() {
    $selectedTerms = $this->tempStoreFactory->get('term_merge')->get('terms');

    if ($selectedTerms === NULL) {
      $selectedTerms = [];
    }
    return $selectedTerms;
  }

  /**
   * Gets a list of selected term labels from the temp store.
   *
   * @return \Drupal\taxonomy\TermInterface[]
   *   The labels of the selected terms.
   */
  private function getSelectedTermLabels() {
    $selectedTerms = $this->loadSelectedTerms();

    $items = [];
    foreach ($selectedTerms as $term) {
      $items[] = $term->label();
    }

    return $items;
  }

  /**
   * Loads the selected terms.
   *
   * @return \Drupal\taxonomy\TermInterface[]
   *   The selected terms.
   */
  private function loadSelectedTerms() {
    $termStorage = $this->entityTypeManager->getStorage('taxonomy_term');
    /** @var \Drupal\taxonomy\TermInterface[] $selectedTerms */
    $selectedTerms = $termStorage->loadMultiple($this->getSelectedTermIds());
    return $selectedTerms;
  }

  /**
   * Sets a redirect to the term merge form.
   *
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   The form state object to set the redirect on.
   */
  private function redirectToTermMergeForm(FormStateInterface $formState) {
    $parameters['taxonomy_vocabulary'] = $this->vocabulary->id();
    $routeName = 'entity.taxonomy_vocabulary.merge_form';
    $formState->setRedirect($routeName, $parameters);
  }

  /**
   * Sets the successfully merged terms message.
   *
   * @param int $count
   *   The numner of terms merged.
   * @param string $targetName
   *   The name of the target term.
   */
  private function setSuccessfullyMergedMessage($count, $targetName) {
    $arguments = [
      '%count' => $count,
      '%target' => $targetName,
    ];
    $this->messenger()->addStatus($this->t('Successfully merged %count terms into %target', $arguments));
  }

}
