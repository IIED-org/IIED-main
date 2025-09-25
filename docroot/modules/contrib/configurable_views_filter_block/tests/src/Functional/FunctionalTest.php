<?php

namespace Drupal\Tests\configurable_views_filter_block\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Test the block provided by the module.
 *
 * @group configurable_views_filter_block
 */
final class FunctionalTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'olivero';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
    'configurable_views_filter_block',
    'configurable_views_filter_block_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Creates two configurable views filter block instances.
    $this->placeBlock('configurable_views_filter_block_block:view_simple_exposed_filters-page_1', [
      'region' => 'content_above',
      'visible_filters' => ['title'],
    ]);
    $this->placeBlock('configurable_views_filter_block_block:view_simple_exposed_filters-page_1', [
      'region' => 'sidebar',
      'visible_filters' => ['uid'],
    ]);
  }

  /**
   * Test that only visible exposed filters are shown.
   */
  public function testVisibleFields(): void {
    // Go to view page provided by configurable_views_filter_block_test test
    // module.
    $this->drupalGet('/test');
    $assert_session = $this->assertSession();

    // Checks that title is the only visible input filter in the main region.
    $assert_session->elementNotExists('xpath', '//main//div[@class="hidden-exposed-filter"]//input[@name="title"]');
    $assert_session->elementExists('xpath', '//main//div[@class="hidden-exposed-filter"]//input[@name="uid"]');

    // Checks that user ID is the only visible input filter in the sidebar
    // region.
    $assert_session->elementNotExists('xpath', '//aside//div[@class="hidden-exposed-filter"]//input[@name="uid"]');
    $assert_session->elementExists('xpath', '//aside//div[@class="hidden-exposed-filter"]//input[@name="title"]');
  }

}
