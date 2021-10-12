<?php

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
  public function setUp(): void {
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
  public function testExposedFilteringAndReset() {
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
  public function testExposedFilterWithDoubleExposedBlock() {
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
    $this->submitForm(['type' => 'page'], t('Apply'), $form1_id);
    $this->waitForCount(1, 'xpath', '//*[text()="Article A"]');
    $this->assertCount(2, $page->findAll('xpath', '//*[text()="Page A"]'));
    $this->assertCount(2, $page->findAll('xpath', '//*[text()="Page B"]'));
    $this->assertCount(1, $page->findAll('xpath', '//*[text()="Article A"]'));

    $form2 = $page->find('css', '#block-' . $block2->id() . ' form');
    $form2_id = $form2->getAttribute('id');
    // Filter by page type in the second form.
    $this->submitForm(['type' => 'page'], t('Apply'), $form2_id);
    $this->waitForCount(0, 'xpath', '//*[text()="Article A"]');
    $this->assertCount(2, $page->findAll('xpath', '//*[text()="Page A"]'));
    $this->assertCount(2, $page->findAll('xpath', '//*[text()="Page B"]'));
    $this->assertCount(0, $page->findAll('xpath', '//*[text()="Article A"]'));
  }

  /**
   * Looks for the selector and waits for the the count is matched.
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

}
