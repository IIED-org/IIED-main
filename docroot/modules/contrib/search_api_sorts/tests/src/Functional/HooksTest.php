<?php

namespace Drupal\Tests\search_api_sorts\Functional;

/**
 * Tests the Search API sorts hooks.
 *
 * @group search_api_sorts
 */
class HooksTest extends SortsFunctionalBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['search_api_sorts_test_hook'];

  /**
   * Tests sorting.
   */
  public function testHookDefaultSortsAlter() {
    // Add a sorting on the ID field.
    $this->drupalGet('admin/config/search/search-api/index/' . $this->indexId . '/fields');
    $sorts_config = 'admin/config/search/search-api/index/' . $this->indexId . '/sorts/' . $this->escapedDisplayId;
    $this->drupalGet($sorts_config);
    $edit = ['sorts[id][status]' => TRUE, 'default_sort' => 'id'];
    $this->drupalPostForm(NULL, $edit, 'Save settings');

    // Add and place the sorts block in the footer.
    $block_settings = ['region' => 'footer', 'id' => 'sorts-id'];
    $this->drupalPlaceBlock('search_api_sorts_block:' . $this->displayId, $block_settings);

    // Set the sorting to descending.
    \Drupal::state()->set('search_api_sorts_default_sort', 'desc');

    // Go to the search page and check that the drupal_set_message() has made
    // output to the search page. And make sure that the positions are actually
    // in descending order.
    $this->drupalGet('search-api-sorts-test');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Hook hook_search_api_sorts_default_sort_alter');
    $this->assertSession()->linkExists('ID');
    $this->assertPositions([
      'default | bar baz',
      'default | foo baz',
      'default | foo test foobuz',
      'default | foo bar baz foobaz föö',
    ]);

    // Revert the sorting output.
    \Drupal::state()->set('search_api_sorts_default_sort', 'asc');

    // Go to the search page and check that the drupal_set_message() has made
    // output to the search page. And make sure that the positions are actually
    // in ascending order.
    $this->drupalGet('search-api-sorts-test');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Hook hook_search_api_sorts_default_sort_alter');
    $this->assertSession()->linkExists('ID');
    $this->assertPositions([
      'default | foo bar baz foobaz föö',
      'default | foo test foobuz',
      'default | foo baz',
      'default | bar baz',
    ]);
  }

}
