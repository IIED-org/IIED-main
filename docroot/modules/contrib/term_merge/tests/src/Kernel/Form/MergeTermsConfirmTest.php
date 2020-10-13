<?php

namespace Drupal\Tests\term_merge\Kernel\Form;

use Drupal\Core\Form\FormState;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\term_merge\Form\MergeTermsConfirm;
use Drupal\Tests\term_merge\Kernel\MergeTermsTestBase;
use Drupal\Tests\term_merge\Kernel\TestDoubles\TermMergerDummy;
use Drupal\Tests\term_merge\Kernel\TestDoubles\TermMergerSpy;

/**
 * Tests the Merge terms confirm form.
 *
 * @group term_merge
 */
class MergeTermsConfirmTest extends MergeTermsTestBase {

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    \Drupal::getContainer()->set('term_merge.term_merger', new TermMergerDummy());
  }

  /**
   * Returns possible merge options that can be selected in the interface.
   *
   * @return array
   *   An array of options. Each option has contains the following values:
   *   - terms: an array of source taxonomy term ids.
   *   - target: a string representing the target taxonomy term.
   */
  public function selectedTermsProvider() {

    $testData['no terms new target'] = [
      'terms' => [],
      'target' => 'New term',
    ];

    $testData['no terms existing target'] = [
      'terms' => [],
      'target' => '',
    ];

    $testData['one term new target'] = [
      'terms' => [1],
      'target' => 'New term',
    ];

    $testData['one term existing target'] = [
      'terms' => [1],
      'target' => '',
    ];

    $testData['two terms new target'] = [
      'terms' => [1, 2],
      'target' => 'New term',
    ];

    $testData['two terms existing target'] = [
      'terms' => [1, 2],
      'target' => '',
    ];

    $testData['three terms new target'] = [
      'terms' => [1, 2, 3],
      'target' => 'New term',
    ];

    $testData['three terms existing target'] = [
      'terms' => [1, 2, 3],
      'target' => '',
    ];

    $testData['four terms new target'] = [
      'terms' => [1, 2, 3, 4],
      'target' => 'New term',
    ];

    $testData['four terms existing target'] = [
      'terms' => [1, 2, 3, 4],
      'target' => '',
    ];

    return $testData;
  }

  /**
   * Tests the title callback for the confirm form.
   *
   * @test
   * @dataProvider selectedTermsProvider
   */
  public function titleCallback(array $selectedTerms) {
    $sut = $this->createSubjectUnderTest();
    $this->privateTempStoreFactory->get('term_merge')->set('terms', $selectedTerms);

    $expected = new TranslatableMarkup('Are you sure you wish to merge %termCount terms?', ['%termCount' => count($selectedTerms)]);
    self::assertEquals($expected, $sut->titleCallback());
  }

  /**
   * Tests the form build for the confirm form.
   *
   * @test
   * @dataProvider selectedTermsProvider
   */
  public function buildForm(array $selectedTerms, $target) {
    $target = $this->prepareTarget($target);
    $sut = $this->createSubjectUnderTest();
    $this->privateTempStoreFactory->get('term_merge')->set('terms', $selectedTerms);
    $this->privateTempStoreFactory->get('term_merge')->set('target', $target);

    $actual = $sut->buildForm([], new FormState(), $this->vocabulary);

    if (empty($selectedTerms)) {
      self::assertEquals([], $actual);
      $this->assertSingleErrorMessage(new TranslatableMarkup("You must submit at least one term."));
    }
    else {
      $this->assertConfirmationForm($selectedTerms, $actual, $target);
    }
  }

  /**
   * Tests the confirm form build structure for a given set of taxonomy terms.
   *
   * @param \Drupal\taxonomy\TermInterface[] $selectedTerms
   *   An array of selected taxonomy terms.
   * @param array $actual
   *   The form structure.
   * @param \Drupal\taxonomy\Entity\Term|string $target
   *   A newly created term if the target was an empty string, the original
   *   string otherwise.
   */
  private function assertConfirmationForm(array $selectedTerms, array $actual, $target) {
    $items = [];
    foreach ($selectedTerms as $termIndex) {
      $items[] = $this->terms[$termIndex]->label();
    }

    $arguments = [
      '%termCount' => count($selectedTerms),
      '%termName' => is_string($target) ? $target : $target->label(),
    ];
    if (is_string($target)) {
      $message = new TranslatableMarkup("You are about to merge %termCount terms into new term %termName. This action can't be undone. Are you sure you wish to continue with merging the terms below?", $arguments);
    }
    else {
      $message = new TranslatableMarkup("You are about to merge %termCount terms into existing term %termName. This action can't be undone. Are you sure you wish to continue with merging the terms below?", $arguments);
    }

    $expected = [
      'message' => [
        '#markup' => $message,
      ],
      'terms' => [
        '#title' => new TranslatableMarkup("Terms to be merged"),
        '#theme' => 'item_list',
        '#items' => $items,
      ],
      'actions' => [
        '#type' => 'actions',
        'submit' => [
          '#button_type' => 'primary',
          '#type' => 'submit',
          '#value' => new TranslatableMarkup('Confirm merge'),
        ],
      ],
    ];

    self::assertEquals($expected, $actual);
  }

  /**
   * Tests a status message is available.
   *
   * @param string $expectedMessage
   *   The status message text.
   */
  private function assertSingleErrorMessage($expectedMessage) {
    $messages = \Drupal::messenger()->all();
    $errorMessages = \Drupal::messenger()->messagesByType('error');

    self::assertCount(1, $messages);
    self::assertEquals($expectedMessage, array_pop($errorMessages));
  }

  /**
   * Tests an exception is thrown for for invalid target types.
   *
   * @test
   * @expectedException \LogicException
   * @expectedExceptionMessage Invalid target type. Should be string or implement TermInterface
   */
  public function incorrectTargetThrowsException() {
    $sut = $this->createSubjectUnderTest();

    $this->privateTempStoreFactory->get('term_merge')->set('terms', [1, 2]);
    $this->privateTempStoreFactory->get('term_merge')->set('target', (object) []);

    $formState = new FormState();
    $build = $sut->buildForm([], $formState, $this->vocabulary);
    $sut->submitForm($build, $formState);
  }

  /**
   * Returns possible merge options that can be selected in the interface.
   *
   * @return array
   *   An array of options. Each option has contains the following values:
   *   - methodName: the method name associated with the selected merge option.
   *   - target: a string representing the target taxonomy term.
   */
  public function termMergerMethodProvider() {
    $methods['new term'] = [
      'methodName' => 'mergeIntoNewTerm',
      'target' => 'New term',
    ];

    $methods['existing term'] = [
      'methodName' => 'mergeIntoTerm',
      'target' => '',
    ];

    return $methods;
  }

  /**
   * Tests the correct method is invoked on the term merger after confirmation.
   *
   * @test
   * @dataProvider termMergerMethodProvider
   */
  public function submitFormInvokesCorrectTermMergerMethod($methodName, $target) {
    $termMergerSpy = new TermMergerSpy();
    \Drupal::getContainer()->set('term_merge.term_merger', $termMergerSpy);
    $sut = $this->createSubjectUnderTest();
    $terms = [reset($this->terms)->id(), end($this->terms)->id()];
    $this->privateTempStoreFactory->get('term_merge')->set('terms', $terms);
    $this->privateTempStoreFactory->get('term_merge')->set('target', $this->prepareTarget($target));

    $formState = new FormState();
    $build = $sut->buildForm([], $formState, $this->vocabulary);

    $sut->submitForm($build, $formState);

    self::assertEquals([$methodName], $termMergerSpy->calledFunctions());
  }

  /**
   * Tests the redirect after merging terms.
   *
   * @test
   * @dataProvider termMergerMethodProvider
   */
  public function submitRedirectsToMergeRoute($methodName, $target) {
    $sut = $this->createSubjectUnderTest();
    $terms = [reset($this->terms)->id(), end($this->terms)->id()];
    $this->privateTempStoreFactory->get('term_merge')->set('terms', $terms);
    $this->privateTempStoreFactory->get('term_merge')->set('target', $this->prepareTarget($target));

    $formState = new FormState();
    $build = $sut->buildForm([], $formState, $this->vocabulary);

    $sut->submitForm($build, $formState);

    $routeName = 'entity.taxonomy_vocabulary.merge_form';
    self::assertRedirect($formState, $routeName, $this->vocabulary->id());
  }

  /**
   * Tests a status message is displayed after merging terms.
   *
   * @test
   */
  public function submitSetsSuccessMessage() {
    $sut = $this->createSubjectUnderTest();
    $terms = [reset($this->terms)->id(), end($this->terms)->id()];
    $this->privateTempStoreFactory->get('term_merge')->set('terms', $terms);
    $this->privateTempStoreFactory->get('term_merge')->set('target', 'Target');

    $formState = new FormState();
    $build = $sut->buildForm([], $formState, $this->vocabulary);

    $sut->submitForm($build, $formState);

    $arguments = [
      '%count' => 2,
      '%target' => 'Target',
    ];
    $expected = [
      new TranslatableMarkup('Successfully merged %count terms into %target', $arguments),
    ];

    self::assertEquals($expected, \Drupal::messenger()->messagesByType('status'));
  }

  /**
   * Creates the form class used for rendering the confirm form.
   *
   * @return \Drupal\term_merge\Form\MergeTermsConfirm
   *   The form class used for rendering the confirm form.
   */
  private function createSubjectUnderTest() {
    return new MergeTermsConfirm($this->entityTypeManager, $this->privateTempStoreFactory, \Drupal::service('term_merge.term_merger'));
  }

  /**
   * {@inheritdoc}
   */
  protected function numberOfTermsToSetUp() {
    return 4;
  }

}
