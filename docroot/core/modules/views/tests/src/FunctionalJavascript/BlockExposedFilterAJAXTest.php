<?php

declare(strict_types=1);

namespace Drupal\Tests\views\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\views\Tests\ViewTestData;

/**
 * Tests the exposed filter ajax functionality in a block.
 *
 * @group views
 */
class BlockExposedFilterAJAXTest extends WebDriverTestBase {

  use ContentTypeCreationTrait;
  use NodeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node', 'views', 'block', 'views_test_config'];

  public static $testViews = ['test_block_exposed_ajax', 'test_block_exposed_ajax_with_page'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    ViewTestData::createTestViews(self::class, ['views_test_config']);
    $this->createContentType(['type' => 'page']);
    $this->createContentType(['type' => 'article']);
    $this->createNode(['title' => 'Page A']);
    $this->createNode(['title' => 'Page B']);
    $this->createNode(['title' => 'Article A', 'type' => 'article']);

    $this->drupalLogin($this->drupalCreateUser([
      'access content',
    ]));
  }

  /**
   * Tests if exposed filtering and reset works with a views block and ajax.
   */
  public function testExposedFilteringAndReset(): void {
    $node = $this->createNode();
    $block = $this->drupalPlaceBlock('views_block:test_block_exposed_ajax-block_1');
    $this->drupalGet($node->toUrl());

    $page = $this->getSession()->getPage();

    // Ensure that the Content we're testing for is present.
    $html = $page->getHtml();
    $this->assertStringContainsString('Page A', $html);
    $this->assertStringContainsString('Page B', $html);
    $this->assertStringContainsString('Article A', $html);

    // Filter by page type.
    $this->submitForm(['type' => 'page'], 'Apply');
    $this->assertSession()->waitForElementRemoved('xpath', '//*[text()="Article A"]');

    // Verify that only the page nodes are present.
    $html = $page->getHtml();
    $this->assertStringContainsString('Page A', $html);
    $this->assertStringContainsString('Page B', $html);
    $this->assertStringNotContainsString('Article A', $html);

    // Reset the form.
    $this->submitForm([], 'Reset');
    // Assert we are still on the node page.
    $html = $page->getHtml();
    // Repeat the original tests.
    $this->assertStringContainsString('Page A', $html);
    $this->assertStringContainsString('Page B', $html);
    $this->assertStringContainsString('Article A', $html);
    $this->assertSession()->addressEquals('node/' . $node->id());

    $block->delete();
    // Do the same test with a block that has a page display to test the user
    // is redirected to the page display.
    $this->drupalPlaceBlock('views_block:test_block_exposed_ajax_with_page-block_1');
    $this->drupalGet($node->toUrl());
    $this->submitForm(['type' => 'page'], 'Apply');
    $this->assertSession()->waitForElementRemoved('xpath', '//*[text()="Article A"]');
    $this->submitForm([], 'Reset');
    $this->assertSession()->addressEquals('some-path');
  }

  /**
   * Test that AJAX works with two exposed blocks on the same page.
   */
  public function testExposedFilterWithDoubleExposedBlock(): void {
    $node = $this->createNode();
    $block1 = $this->drupalPlaceBlock('views_block:test_block_exposed_ajax-block_1');
    $block2 = $this->drupalPlaceBlock('views_block:test_block_exposed_ajax-block_1');
    $this->drupalGet($node->toUrl());

    $page = $this->getSession()->getPage();

    // Ensure that the Content we're testing for is present.
    $this->assertCount(2, $page->findAll('xpath', '//*[text()="Page A"]'));
    $this->assertCount(2, $page->findAll('xpath', '//*[text()="Page B"]'));
    $this->assertCount(2, $page->findAll('xpath', '//*[text()="Article A"]'));

    $form1 = $page->find('css', '#block-' . $block1->id() . ' form');
    $form1_id = $form1->getAttribute('id');
    // Filter by page type in the first form.
    $this->submitForm(['type' => 'page'], 'Apply', $form1_id);
    $this->waitForCount(1, 'xpath', '//*[text()="Article A"]');
    $this->assertCount(2, $page->findAll('xpath', '//*[text()="Page A"]'));
    $this->assertCount(2, $page->findAll('xpath', '//*[text()="Page B"]'));
    $this->assertCount(1, $page->findAll('xpath', '//*[text()="Article A"]'));

    $form2 = $page->find('css', '#block-' . $block2->id() . ' form');
    $form2_id = $form2->getAttribute('id');
    // Filter by page type in the second form.
    $this->submitForm(['type' => 'page'], 'Apply', $form2_id);
    $this->waitForCount(1, 'xpath', '//*[text()="Article A"]');
    $this->assertCount(2, $page->findAll('xpath', '//*[text()="Page A"]'));
    $this->assertCount(2, $page->findAll('xpath', '//*[text()="Page B"]'));
    $this->assertCount(1, $page->findAll('xpath', '//*[text()="Article A"]'));
  }

  /**
   * Looks for the selector and waits for the count to be matched.
   *
   * @param int $count
   *   The count to match.
   * @param string $selector
   *   The selector engine name. See ElementInterface::findAll() for the
   *   supported selectors.
   * @param string|array $locator
   *   The selector locator.
   * @param int $timeout
   *   (Optional) Timeout in milliseconds, defaults to 10000.
   *
   * @return bool
   *   TRUE if count was matched, FALSE if not.
   */
  protected function waitForCount($count, $selector, $locator, $timeout = 10000) {
    $page = $this->getSession()->getPage();

    $result = $page->waitFor($timeout / 1000, function () use ($page, $count, $selector, $locator) {
      return count($page->findAll($selector, $locator)) === $count;
    });

    return $result;
  }

  /**
   * Tests if exposed forms work with multiple instances of the same view.
   */
  public function testMultipleExposedFormsForTheSameView() {
    $this->drupalPlaceBlock('views_exposed_filter_block:test_block_exposed_ajax_with_page-page_2', ['region' => 'content', 'weight' => -10, 'id' => 'page-exposed-form']);
    $this->drupalPlaceBlock('views_block:test_block_exposed_ajax_with_page-block_1', ['id' => 'block-one-exposed-form', 'weight' => 50]);
    $this->drupalPlaceBlock('views_block:test_block_exposed_ajax_with_page-block_1', ['id' => 'block-two-exposed-form', 'weight' => 100]);

    $assert_session = $this->assertSession();

    // Go to the page and check that all 3 views are displaying the correct
    // results.
    $this->drupalGet('some-other-path');

    $views = $this->getSession()->getPage()->findAll('css', '.views-element-container');
    $content = $views[0]->getHtml();
    $this->assertStringContainsString('Page A', $content);
    $this->assertStringContainsString('Page B', $content);
    $this->assertStringContainsString('Article A', $content);

    $content = $views[1]->getHtml();
    $this->assertStringContainsString('Page A', $content);
    $this->assertStringContainsString('Page B', $content);
    $this->assertStringContainsString('Article A', $content);

    $content = $views[2]->getHtml();
    $this->assertStringContainsString('Page A', $content);
    $this->assertStringContainsString('Page B', $content);
    $this->assertStringContainsString('Article A', $content);

    // Ensure that page view exposed form (displayed as block) does not
    // affect other two block views.
    // Find the form HTML ID.
    $element = $assert_session->elementExists('css', '#block-page-exposed-form .views-exposed-form');
    // Filter by article.
    $element->find('css', 'select')->selectOption('article');
    $element->findButton('Apply')->click();
    $assert_session->waitForElementRemoved('xpath', '//div[@id="block-page-exposed-form"]/following::span[1][text()="Page A"]');

    // Verify that only page view has been filtered.
    $views = $this->getSession()->getPage()->findAll('css', '.views-element-container');
    $content = $views[0]->getHtml();
    $this->assertStringNotContainsString('Page A', $content);
    $this->assertStringNotContainsString('Page B', $content);
    $this->assertStringContainsString('Article A', $content);
    $content = $views[1]->getHtml();
    $this->assertStringContainsString('Page A', $content);
    $this->assertStringContainsString('Page B', $content);
    $this->assertStringContainsString('Article A', $content);
    $content = $views[2]->getHtml();
    $this->assertStringContainsString('Page A', $content);
    $this->assertStringContainsString('Page B', $content);
    $this->assertStringContainsString('Article A', $content);

    // Find the form HTML ID.
    $element = $assert_session->elementExists('css', '#block-page-exposed-form .views-exposed-form');
    // Filter by page.
    $element->find('css', 'select')->selectOption('page');
    $element->findButton('Apply')->click();
    $assert_session->waitForElementRemoved('xpath', '//div[@id="block-page-exposed-form"]/following::span[1][text()="Article A"]');

    // Verify that only page view has been filtered.
    $views = $this->getSession()->getPage()->findAll('css', '.views-element-container');
    $content = $views[0]->getHtml();
    $this->assertStringContainsString('Page A', $content);
    $this->assertStringContainsString('Page B', $content);
    $this->assertStringNotContainsString('Article A', $content);
    $content = $views[1]->getHtml();
    $this->assertStringContainsString('Page A', $content);
    $this->assertStringContainsString('Page B', $content);
    $this->assertStringContainsString('Article A', $content);
    $content = $views[2]->getHtml();
    $this->assertStringContainsString('Page A', $content);
    $this->assertStringContainsString('Page B', $content);
    $this->assertStringContainsString('Article A', $content);

    // Find the form HTML ID.
    $element = $assert_session->elementExists('css', '#block-page-exposed-form .views-exposed-form');
    // Disable filter.
    $element->find('css', 'select')->selectOption('All');
    $element->findButton('Apply')->click();
    $assert_session->waitForElement('xpath', '//div[@id="block-page-exposed-form"]/following::span[1][text()="Article A"]');

    // Ensure that the first block view exposed form does not affect the page
    // view and the other block view.
    // Find the form HTML ID.
    $element = $assert_session->elementExists('css', '#block-block-one-exposed-form .views-exposed-form');
    // Filter by article.
    $element->find('css', 'select')->selectOption('article');
    $element->findButton('Apply')->click();
    $assert_session->waitForElementRemoved('xpath', '//div[@id="block-block-one-exposed-form"]//*[text()="Page A"]');

    // Verify that only the first block view has been filtered.
    $views = $this->getSession()->getPage()->findAll('css', '.views-element-container');
    $content = $views[0]->getHtml();
    $this->assertStringContainsString('Page A', $content);
    $this->assertStringContainsString('Page B', $content);
    $this->assertStringContainsString('Article A', $content);
    $content = $views[1]->getHtml();
    $this->assertStringNotContainsString('Page A', $content);
    $this->assertStringNotContainsString('Page B', $content);
    $this->assertStringContainsString('Article A', $content);
    $content = $views[2]->getHtml();
    $this->assertStringContainsString('Page A', $content);
    $this->assertStringContainsString('Page B', $content);
    $this->assertStringContainsString('Article A', $content);

    // Find the form HTML ID.
    $element = $assert_session->elementExists('css', '#block-block-one-exposed-form .views-exposed-form');
    // Filter by page.
    $element->find('css', 'select')->selectOption('page');
    $element->findButton('Apply')->click();
    $assert_session->waitForElementRemoved('xpath', '//div[@id="block-block-one-exposed-form"]//*[text()="Article A"]');

    // Verify that only the first block view has been filtered.
    $views = $this->getSession()->getPage()->findAll('css', '.views-element-container');
    $content = $views[0]->getHtml();
    $this->assertStringContainsString('Page A', $content);
    $this->assertStringContainsString('Page B', $content);
    $this->assertStringContainsString('Article A', $content);
    $content = $views[1]->getHtml();
    $this->assertStringContainsString('Page A', $content);
    $this->assertStringContainsString('Page B', $content);
    $this->assertStringNotContainsString('Article A', $content);
    $content = $views[2]->getHtml();
    $this->assertStringContainsString('Page A', $content);
    $this->assertStringContainsString('Page B', $content);
    $this->assertStringContainsString('Article A', $content);

    // Find the form HTML ID.
    $element = $assert_session->elementExists('css', '#block-block-one-exposed-form .views-exposed-form');
    // Disable filter.
    $element->find('css', 'select')->selectOption('All');
    $element->findButton('Apply')->click();
    $assert_session->waitForElement('xpath', '//div[@id="block-block-one-exposed-form"]//*[text()="Article A"]');

    // Ensure that the second block view exposed form does not affect the page
    // view and the other block view.
    // Find the form HTML ID.
    $element = $assert_session->elementExists('css', '#block-block-two-exposed-form .views-exposed-form');
    // Filter by article.
    $element->find('css', 'select')->selectOption('article');
    $element->findButton('Apply')->click();
    $assert_session->waitForElementRemoved('xpath', '//div[@id="block-block-two-exposed-form"]//*[text()="Page A"]');

    // Verify that only the second block view has been filtered.
    $views = $this->getSession()->getPage()->findAll('css', '.views-element-container');
    $content = $views[0]->getHtml();
    $this->assertStringContainsString('Page A', $content);
    $this->assertStringContainsString('Page B', $content);
    $this->assertStringContainsString('Article A', $content);
    $content = $views[1]->getHtml();
    $this->assertStringContainsString('Page A', $content);
    $this->assertStringContainsString('Page B', $content);
    $this->assertStringContainsString('Article A', $content);
    $content = $views[2]->getHtml();
    $this->assertStringNotContainsString('Page A', $content);
    $this->assertStringNotContainsString('Page B', $content);
    $this->assertStringContainsString('Article A', $content);

    // Find the form HTML ID.
    $element = $assert_session->elementExists('css', '#block-block-two-exposed-form .views-exposed-form');
    // Filter by page.
    $element->find('css', 'select')->selectOption('page');
    $element->findButton('Apply')->click();
    $assert_session->waitForElementRemoved('xpath', '//div[@id="block-block-two-exposed-form"]//*[text()="Article A"]');

    // Verify that only the second block view has been filtered.
    $views = $this->getSession()->getPage()->findAll('css', '.views-element-container');
    $content = $views[0]->getHtml();
    $this->assertStringContainsString('Page A', $content);
    $this->assertStringContainsString('Page B', $content);
    $this->assertStringContainsString('Article A', $content);
    $content = $views[1]->getHtml();
    $this->assertStringContainsString('Page A', $content);
    $this->assertStringContainsString('Page B', $content);
    $this->assertStringContainsString('Article A', $content);
    $content = $views[2]->getHtml();
    $this->assertStringContainsString('Page A', $content);
    $this->assertStringContainsString('Page B', $content);
    $this->assertStringNotContainsString('Article A', $content);

    // Find the form HTML ID.
    $element = $assert_session->elementExists('css', '#block-block-two-exposed-form .views-exposed-form');
    // Disable filter.
    $element->find('css', 'select')->selectOption('All');
    $element->findButton('Apply')->click();
    $assert_session->waitForElement('xpath', '//div[@id="block-block-two-exposed-form"]//*[text()="Article A"]');

    // Ensure that the all forms works when used one by one.
    // Find the form HTML ID.
    $element = $assert_session->elementExists('css', '#block-page-exposed-form .views-exposed-form');
    // Filter by article.
    $element->find('css', 'select')->selectOption('article');
    $element->findButton('Apply')->click();
    $assert_session->waitForElementRemoved('xpath', '//div[@id="block-page-exposed-form"]/following::span[1][text()="Page A"]');

    // Find the form HTML ID.
    $element = $assert_session->elementExists('css', '#block-block-one-exposed-form .views-exposed-form');
    // Filter by page.
    $element->find('css', 'select')->selectOption('page');
    $element->findButton('Apply')->click();
    $assert_session->waitForElementRemoved('xpath', '//div[@id="block-block-one-exposed-form"]//*[text()="Page A"]');

    // Find the form HTML ID.
    $element = $assert_session->elementExists('css', '#block-block-two-exposed-form .views-exposed-form');
    // Filter by page.
    $element->find('css', 'select')->selectOption('article');
    $element->findButton('Apply')->click();
    $assert_session->waitForElementRemoved('xpath', '//div[@id="block-block-two-exposed-form"]//*[text()="Page A"]');

    // Verify that all views has been filtered.
    $views = $this->getSession()->getPage()->findAll('css', '.views-element-container');
    $content = $views[0]->getHtml();
    $this->assertStringNotContainsString('Page A', $content);
    $this->assertStringNotContainsString('Page B', $content);
    $this->assertStringContainsString('Article A', $content);
    $content = $views[1]->getHtml();
    $this->assertStringContainsString('Page A', $content);
    $this->assertStringContainsString('Page B', $content);
    $this->assertStringNotContainsString('Article A', $content);
    $content = $views[2]->getHtml();
    $this->assertStringNotContainsString('Page A', $content);
    $this->assertStringNotContainsString('Page B', $content);
    $this->assertStringContainsString('Article A', $content);

    // Find the form HTML ID.
    $element = $assert_session->elementExists('css', '#block-block-two-exposed-form .views-exposed-form');
    // Disable filter.
    $element->find('css', 'select')->selectOption('All');
    $element->findButton('Apply')->click();
    $assert_session->waitForElement('xpath', '//div[@id="block-block-two-exposed-form"]//*[text()="Page A"]');

    // Verify that all views has been filtered one more time.
    $views = $this->getSession()->getPage()->findAll('css', '.views-element-container');
    $content = $views[0]->getHtml();
    $this->assertStringNotContainsString('Page A', $content);
    $this->assertStringNotContainsString('Page B', $content);
    $this->assertStringContainsString('Article A', $content);
    $content = $views[1]->getHtml();
    $this->assertStringContainsString('Page A', $content);
    $this->assertStringContainsString('Page B', $content);
    $this->assertStringNotContainsString('Article A', $content);
    $content = $views[2]->getHtml();
    $this->assertStringContainsString('Page A', $content);
    $this->assertStringContainsString('Page B', $content);
    $this->assertStringContainsString('Article A', $content);
  }

}
