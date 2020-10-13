<?php

namespace Drupal\Tests\term_merge\Kernel;

use Drupal\Core\Form\FormState;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\Url;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\taxonomy\Functional\TaxonomyTestTrait;

/**
 * Base class for Term merge kernel tests.
 */
abstract class MergeTermsTestBase extends KernelTestBase {

  use TaxonomyTestTrait {
    TaxonomyTestTrait::createVocabulary as traitCreateVocabulary;
  }

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'filter',
    'term_merge',
    'term_reference_change',
    'taxonomy',
    'text',
    'user',
    'system',
  ];

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The tempstore factory.
   *
   * @var \Drupal\user\PrivateTempStoreFactory
   */
  protected $privateTempStoreFactory;

  /**
   * A vocabulary for testing purposes.
   *
   * @var \Drupal\taxonomy\Entity\Vocabulary
   */
  protected $vocabulary;

  /**
   * An array of taxonomy terms.
   *
   * @var \Drupal\taxonomy\TermInterface[]
   */
  protected $terms;

  /**
   * Create a new vocabulary with random properties.
   *
   * @return \Drupal\taxonomy\Entity\Vocabulary
   *   The created vocabulary
   */
  public function createVocabulary() {
    /** @var \Drupal\taxonomy\Entity\Vocabulary $vocabulary */
    $vocabulary = $this->traitCreateVocabulary();
    return $vocabulary;
  }

  /**
   * Returns the number of terms that should be set up by the setUp function.
   *
   * @return int
   *   The number of terms that should be set up by the setUp function.
   */
  abstract protected function numberOfTermsToSetUp();

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installConfig(['filter']);
    $this->installEntitySchema('taxonomy_term');
    $this->installEntitySchema('taxonomy_vocabulary');
    $this->installSchema('system', ['key_value_expire']);

    $accountProxy = new AccountProxy();
    $account = $this->createMock(AccountInterface::class);
    $account->method('id')->willReturn(24);
    /** @var \Drupal\Core\Session\AccountInterface $account */
    $accountProxy->setAccount($account);
    \Drupal::getContainer()->set('current_user', $accountProxy);
    $this->privateTempStoreFactory = \Drupal::service('user.private_tempstore');

    $this->entityTypeManager = \Drupal::entityTypeManager();

    $this->vocabulary = $this->createVocabulary();

    $this->createTerms($this->numberOfTermsToSetUp());
  }

  /**
   * Prepares the target provided by mergeTermFunctionsProvider for use.
   *
   * Dataproviders run before the tests are set up and are therefore unable to
   * create proper taxonomy terms. Which means we'll have to do so in the test.
   *
   * @param string $target
   *   The label for the taxonomy term target.
   *
   * @return \Drupal\taxonomy\Entity\Term|string
   *   A newly created term if the target was an empty string, the original
   *   string otherwise.
   */
  protected function prepareTarget($target) {
    if (!empty($target)) {
      return $target;
    }

    return $this->createTerm($this->vocabulary);
  }

  /**
   * Asserts whether a given formState has its redirect set to a given route.
   *
   * @param \Drupal\Core\Form\FormState $formState
   *   The current form state.
   * @param string $routeName
   *   The name of the route.
   * @param string $vocabularyId
   *   The target vocabulary machine name.
   */
  protected function assertRedirect(FormState $formState, $routeName, $vocabularyId) {
    $routeParameters['taxonomy_vocabulary'] = $vocabularyId;
    $expected = new Url($routeName, $routeParameters);
    KernelTestBase::assertEquals($expected, $formState->getRedirect());
  }

  /**
   * Create a given amount of taxonomy terms.
   *
   * @param int $count
   *   The amount of taxonomy terms to create.
   */
  protected function createTerms($count) {
    for ($i = 0; $i < $count; $i++) {
      $term = $this->createTerm($this->vocabulary);
      $this->terms[$term->id()] = $term;
    }
  }

}
