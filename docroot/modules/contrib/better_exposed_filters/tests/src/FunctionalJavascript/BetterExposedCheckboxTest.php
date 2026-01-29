<?php

namespace Drupal\Tests\better_exposed_filters\FunctionalJavascript;

use Drupal\views\Views;

/**
 * Tests functionality around checkboxes.
 *
 * @group better_exposed_filters
 */
class BetterExposedCheckboxTest extends BetterExposedFiltersTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create a few test nodes.
    $this->createNode([
      'title' => 'Page published',
      'field_bef_price' => '10',
      'type' => 'bef_test',
      'status' => 1,
    ]);
    $this->createNode([
      'title' => 'Page unpublished',
      'field_bef_price' => '75',
      'type' => 'bef_test',
      'status' => 0,
    ]);
  }

  /**
   * Tests the single checkbox.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testSingleCheckbox(): void {
    $view = Views::getView('bef_test');
    $session = $this->assertSession();

    $this->drupalGet('/bef-test-checkboxes');
    $session->checkboxChecked('status');
    $session->pageTextContains('Page published');
    $session->pageTextNotContains('Page unpublished');

    $page = $this->getSession()->getPage();
    $page->findField('status')->uncheck();
    $page->pressButton('Apply');
    $this->assertSession()->assertWaitOnAjaxRequest();

    $session->checkboxNotChecked('status');
    // Both should display because treat_as_false is unchecked.
    $session->pageTextContains('Page published');
    $session->pageTextContains('Page unpublished');

    $this->setBetterExposedOptions($view, [
      'filter' => [
        'status' => [
          'plugin_id' => 'bef_single',
          'treat_as_false' => TRUE,
        ],
      ],
    ], 'page_5');

    // Now test the same again.
    $this->drupalGet('/bef-test-checkboxes');
    $this->drupalGet('/bef-test-checkboxes');
    $session->checkboxChecked('status');
    $session->pageTextContains('Page published');
    $session->pageTextNotContains('Page unpublished');

    $page = $this->getSession()->getPage();
    $page->findField('status')->uncheck();
    $page->pressButton('Apply');
    $this->assertSession()->assertWaitOnAjaxRequest();

    $session->checkboxNotChecked('status');
    // Now only the unpublished should appear.
    $session->pageTextNotContains('Page published');
    $session->pageTextContains('Page unpublished');
  }

  /**
   * Tests the soft limit feature.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testBefCheckboxSoftLimit(): void {
    $view = Views::getView('bef_test');
    $session = $this->assertSession();

    $this->setBetterExposedOptions($view, [
      'filter' => [
        'field_bef_letters_value' => [
          'plugin_id' => 'bef',
          'soft_limit' => '3',
          'soft_limit_label_less' => 'Less test',
          'soft_limit_label_more' => 'More test',
        ],
      ],
    ], 'page_5');

    $this->drupalGet('/bef-test-checkboxes');
    $session->elementTextEquals('css', '.bef-soft-limit-link', 'More test');
    $session->pageTextContains('Aardvark');
    $session->pageTextContains('Bumble & the Bee');
    $session->pageTextContains('Le Chimpanzé');
    $session->pageTextNotContains('Donkey');
    $session->pageTextNotContains('Elephant');
    $this->clickLink('More test');
    $session->pageTextContains('Aardvark');
    $session->pageTextContains('Bumble & the Bee');
    $session->pageTextContains('Le Chimpanzé');
    $session->pageTextContains('Donkey');
    $session->pageTextContains('Elephant');
    $session->elementTextEquals('css', '.bef-soft-limit-link', 'Less test');

    // Now lets test soft limit on links.
    $this->setBetterExposedOptions($view, [
      'filter' => [
        'field_bef_letters_value' => [
          'plugin_id' => 'bef_links',
          'soft_limit' => '3',
          'soft_limit_label_less' => 'Less test',
          'soft_limit_label_more' => 'More test',
        ],
      ],
    ], 'page_5');

    $this->drupalGet('/bef-test-checkboxes');
    $session->elementTextEquals('css', '.bef-soft-limit-link', 'More test');
    $session->pageTextContains('Aardvark');
    $session->pageTextContains('Bumble & the Bee');
    $session->pageTextContains('Le Chimpanzé');
    $session->pageTextNotContains('Donkey');
    $session->pageTextNotContains('Elephant');
    $this->clickLink('More test');
    $session->pageTextContains('Aardvark');
    $session->pageTextContains('Bumble & the Bee');
    $session->pageTextContains('Le Chimpanzé');
    $session->pageTextContains('Donkey');
    $session->pageTextContains('Elephant');
    $session->elementTextEquals('css', '.bef-soft-limit-link', 'Less test');
  }

}
