<?php

declare(strict_types=1);

namespace Drupal\Tests\facets_exposed_filters\Functional;

use Drupal\Tests\facets\Functional\FacetsTestBase;

/**
 * Tests surrounding facets exposed filters configured to use pretty paths.
 *
 * @group facets_pretty_paths
 */
class PrettyFacetsExposedFiltersTest extends FacetsTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'facets_exposed_filters',
    'facets_pretty_paths',
    'node',
    'pretty_facets_exposed_filters_test',
    'search_api',
    'search_api_test_db',
    'views',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->drupalLogin($this->rootUser);
    $this->setUpExampleStructure();
    $this->insertExampleContent();
    $this
      ->assertEquals(5, $this->indexItems($this->indexId), '5 items were indexed.');
  }

  /**
   * Test pretty-paths-enabled facets exposed filters.
   *
   * The test_pretty_facets_exposed_filters view contains two facets exposed
   * filters, each configured to accept multiple choices and to use the
   * standard facets pretty paths coder.
   */
  public function testPrettyFacetsExposedFilters() {
    // Request a non-filtered page.
    $this->drupalGet('test-pretty-facets-exposed-filters');
    // See that both facet filters show with choices.
    $this->assertSession()->pageTextContains('Category');
    $this->assertSession()->pageTextContains('article_category');
    $this->assertSession()->pageTextContains('Keywords');
    $this->assertSession()->pageTextContains('strawberry');
    // Entity 3 doesn't contain any keywords or category, and this should be
    // showing on this initial, unfiltered page.
    $this
      ->assertSession()
      ->pageTextContains('entity:entity_test_mulrev_changed/3:en');

    // Submit the views exposed form with one category selected.
    $this->drupalGet('test-pretty-facets-exposed-filters', [
      'query' => [
        'category' => ['item_category' => 'item_category'],
      ],
    ]);
    // See that we have been redirected to the pretty path.
    $this
      ->assertSession()
      ->addressEquals('test-pretty-facets-exposed-filters/category/item_category');
    // The Keywords filter should be showing without the "strawberry" option
    // because no records in the "item_category" category have the
    // "strawberry" keyword.
    $this->assertSession()->pageTextContains('Keywords');
    $this->assertSession()->pageTextNotContains('strawberry');
    // Entity 1 has the "item_category" category, and thus should show.
    $this
      ->assertSession()
      ->pageTextContains('entity:entity_test_mulrev_changed/1:en');
    // Entity 3 does not, and thus should not show.
    $this
      ->assertSession()
      ->pageTextNotContains('entity:entity_test_mulrev_changed/3:en');

    // Submit the views exposed form with one keyword selected.
    $this->drupalGet('test-pretty-facets-exposed-filters', [
      'query' => [
        'keywords' => ['apple' => 'apple'],
      ],
    ]);
    // See that we have been redirected to the pretty path.
    $this
      ->assertSession()
      ->addressEquals('test-pretty-facets-exposed-filters/keywords/apple');
    // The Keywords filter should be showing with the "strawberry" option
    // because records exist with both the "apple" and "strawberry" keywords.
    $this->assertSession()->pageTextContains('Keywords');
    $this->assertSession()->pageTextContains('strawberry');
    // Entity 2 has the "apple" keyword, and thus should show.
    $this
      ->assertSession()
      ->pageTextContains('entity:entity_test_mulrev_changed/2:en');
    // Entity 3 does not, and thus should not show.
    $this
      ->assertSession()
      ->pageTextNotContains('entity:entity_test_mulrev_changed/3:en');

    // Submit the views exposed form with one category and one keyword selected.
    $this->drupalGet('test-pretty-facets-exposed-filters', [
      'query' => [
        'category' => ['item_category' => 'item_category'],
        'keywords' => ['apple' => 'apple'],
      ],
    ]);
    // See that we have been redirected to the pretty path, category before
    // keywords because that is the filter order configured in the view.
    $this
      ->assertSession()
      ->addressEquals('test-pretty-facets-exposed-filters/category/item_category/keywords/apple');
    // Entity 2 has the "item_category" category and "apple" keyword, and
    // thus should show.
    $this
      ->assertSession()
      ->pageTextContains('entity:entity_test_mulrev_changed/2:en');
    // Entity 3 does not, and thus should not show.
    $this
      ->assertSession()
      ->pageTextNotContains('entity:entity_test_mulrev_changed/3:en');

    // Submit the views exposed form with one two categories and two keywords
    // selected.
    $this->drupalGet('test-pretty-facets-exposed-filters', [
      'query' => [
        'category' => [
          'article_category' => 'article_category',
          'item_category' => 'item_category',
        ],
        'keywords' => [
          'apple' => 'apple',
          'strawberry' => 'strawberry',
        ],
      ],
    ]);
    // See that we have been redirected to the expected path, ordered correctly.
    // The category filter is ordered before of the keywords filter in the view,
    // and the order of the choices in the URL matches the order shown in (and
    // submitted by) the views exposed form (configured ASC by value in the
    // facet settings).
    $this
      ->assertSession()
      ->addressEquals('test-pretty-facets-exposed-filters/category/article_category/category/item_category/keywords/apple/keywords/strawberry');
    // Entity 1 has the "item_category" but no matching keywords, thus should
    // not display (the two filters are joined by an AND operator).
    $this
      ->assertSession()
      ->pageTextNotContains('entity:entity_test_mulrev_changed/1:en');
    // Entity 2 has the "item_category" category and "apple" keyword, thus
    // should display.
    $this
      ->assertSession()
      ->pageTextContains('entity:entity_test_mulrev_changed/2:en');
    // Entity 3 does not have any of these categories or keywords, thus should
    // not display.
    $this
      ->assertSession()
      ->pageTextNotContains('entity:entity_test_mulrev_changed/3:en');
    // Entity 4 has the "article_category" category and "apple" keyword, thus
    // should display.
    $this
      ->assertSession()
      ->pageTextContains('entity:entity_test_mulrev_changed/4:en');
    // Entity 5 has the "article_category" category and "apple" and
    // "strawberry" keywords, thus should display.
    $this
      ->assertSession()
      ->pageTextContains('entity:entity_test_mulrev_changed/5:en');
  }

  /**
   * Test pretty-paths-enabled facets filters combined with standard ones.
   *
   * The test_pretty_facets_exposed_filters_50_50 view contains two facets
   * exposed filters:
   *  - category is configured to accept multiple choices and to use facets
   *    pretty paths, default coder.
   *  - keywords is configured to accept multiple choices and not to use
   *    facets pretty paths.
   */
  public function testPrettyWithStandardFacetsExposedFilters() {
    // Request a non-filtered page.
    $this->drupalGet('test-pretty-facets-exposed-filters-50-50');
    // See that both facet filters show with choices.
    $this->assertSession()->pageTextContains('Category');
    $this->assertSession()->pageTextContains('article_category');
    $this->assertSession()->pageTextContains('Keywords');
    $this->assertSession()->pageTextContains('strawberry');
    // Entity 3 doesn't contain any keywords or category, and this should be
    // showing on this initial, unfiltered page.
    $this
      ->assertSession()
      ->pageTextContains('entity:entity_test_mulrev_changed/3:en');

    // Submit the views exposed form with one category selected.
    $this->drupalGet('test-pretty-facets-exposed-filters-50-50', [
      'query' => [
        'category' => ['item_category' => 'item_category'],
      ],
    ]);
    // See that we have been redirected to the pretty path.
    $this
      ->assertSession()
      ->addressEquals('test-pretty-facets-exposed-filters-50-50/category/item_category');
    // The Keywords filter should be showing without the "strawberry" option
    // because no records in the "item_category" category have the
    // "strawberry" keyword.
    $this->assertSession()->pageTextContains('Keywords');
    $this->assertSession()->pageTextNotContains('strawberry');
    // Entity 1 has the "item_category" category.
    $this
      ->assertSession()
      ->pageTextContains('entity:entity_test_mulrev_changed/1:en');
    // Entity 3 does not.
    $this
      ->assertSession()
      ->pageTextNotContains('entity:entity_test_mulrev_changed/3:en');

    // Submit the views exposed form with one keyword selected.
    $this->drupalGet('test-pretty-facets-exposed-filters-50-50', [
      'query' => [
        'keywords' => ['apple' => 'apple'],
      ],
    ]);
    // See that we are redirected to the expected path views exposed filter
    // path.
    $this
      ->assertSession()
      ->addressEquals('test-pretty-facets-exposed-filters-50-50?keywords%5Bapple%5D=apple');
    // The Keywords filter should be showing with the "strawberry" option
    // because records exist with both the "apple" and "strawberry" keywords.
    $this->assertSession()->pageTextContains('Keywords');
    $this->assertSession()->pageTextContains('strawberry');
    // Entity 2 has the "apple" keyword.
    $this
      ->assertSession()
      ->pageTextContains('entity:entity_test_mulrev_changed/2:en');
    // Entity 3 does not.
    $this
      ->assertSession()
      ->pageTextNotContains('entity:entity_test_mulrev_changed/3:en');

    // Submit the views exposed form with one category and one keyword selected.
    $this->drupalGet('test-pretty-facets-exposed-filters-50-50', [
      'query' => [
        'category' => ['item_category' => 'item_category'],
        'keywords' => ['apple' => 'apple'],
      ],
    ]);
    // See that we have been redirected to the expected path, pretty category
    // part followed by regular views exposed filter keywords query string data.
    $this
      ->assertSession()
      ->addressEquals('test-pretty-facets-exposed-filters-50-50/category/item_category?keywords%5Bapple%5D=apple');
    // Entity 2 has the "item_category" category and "apple" keyword.
    $this
      ->assertSession()
      ->pageTextContains('entity:entity_test_mulrev_changed/2:en');
    // Entity 3 does not.
    $this
      ->assertSession()
      ->pageTextNotContains('entity:entity_test_mulrev_changed/3:en');

    // Submit the views exposed form with one two categories and two keywords
    // selected.
    $this->drupalGet('test-pretty-facets-exposed-filters-50-50', [
      'query' => [
        'category' => [
          'article_category' => 'article_category',
          'item_category' => 'item_category',
        ],
        'keywords' => [
          'apple' => 'apple',
          'strawberry' => 'strawberry',
        ],
      ],
    ]);
    // See that we have been redirected to the expected path, ordered correctly.
    // The category filter is ordered before of the keywords filter in the view,
    // and the order of the choices in the URL matches the order shown in (and
    // submitted by) the views exposed form (configured ASC by value in the
    // facet settings).
    $this
      ->assertSession()
      ->addressEquals('test-pretty-facets-exposed-filters-50-50/category/article_category/category/item_category?keywords%5Bapple%5D=apple&keywords%5Bstrawberry%5D=strawberry');
    // Entity 1 has the "item_category" but no matching keywords, thus should
    // not display (the two filters are joined by an AND operator).
    $this
      ->assertSession()
      ->pageTextNotContains('entity:entity_test_mulrev_changed/1:en');
    // Entity 2 has the "item_category" category and "apple" keyword, thus
    // should display.
    $this
      ->assertSession()
      ->pageTextContains('entity:entity_test_mulrev_changed/2:en');
    // Entity 3 does not have any of these categories or keywords, thus should
    // not display.
    $this
      ->assertSession()
      ->pageTextNotContains('entity:entity_test_mulrev_changed/3:en');
    // Entity 4 has the "article_category" category and "apple" keyword, thus
    // should display.
    $this
      ->assertSession()
      ->pageTextContains('entity:entity_test_mulrev_changed/4:en');
    // Entity 5 has the "article_category" category and "apple" and
    // "strawberry" keywords, thus should display.
    $this
      ->assertSession()
      ->pageTextContains('entity:entity_test_mulrev_changed/5:en');
  }

  /**
   * Test pretty-paths-enabled facets filters that allow a single selection.
   *
   * Filters that allow a single-selection submit a value of "All" when nothing
   * is selected. Ensure facets pretty paths works as designed here.
   *
   * The test_pretty_facets_exposed_filters_single_choice view has two facets
   * exposed filters, each configured to accept a single choice.
   */
  public function testPrettySingleSelectionExposedFilters() {
    // Request a non-filtered page.
    $this->drupalGet('test-pretty-facets-exposed-filters-single-choice');
    // See that both facet filters show with choices.
    $this->assertSession()->pageTextContains('Category');
    $this->assertSession()->pageTextContains('article_category');
    $this->assertSession()->pageTextContains('Keyword');
    $this->assertSession()->pageTextContains('strawberry');
    // Entity 3 doesn't contain any keywords or category, and this should be
    // showing on this initial, unfiltered page.
    $this
      ->assertSession()
      ->pageTextContains('entity:entity_test_mulrev_changed/3:en');

    // Submit the views exposed form with a category selected.
    $this->drupalGet('test-pretty-facets-exposed-filters-single-choice', [
      'query' => [
        'category' => 'item_category',
        'keywords' => 'All',
      ],
    ]);
    // See that we have been redirected to the pretty path.
    $this
      ->assertSession()
      ->addressEquals('test-pretty-facets-exposed-filters-single-choice/category/item_category');
    // The Keyword filter should be showing without the "strawberry" option
    // because no records in the "item_category" category have the
    // "strawberry" keyword.
    $this->assertSession()->pageTextContains('Keyword');
    $this->assertSession()->pageTextNotContains('strawberry');
    // Entity 1 has the "item_category" category.
    $this
      ->assertSession()
      ->pageTextContains('entity:entity_test_mulrev_changed/1:en');
    // Entity 3 does not.
    $this
      ->assertSession()
      ->pageTextNotContains('entity:entity_test_mulrev_changed/3:en');

    // Submit the views exposed form with a keyword selected.
    $this->drupalGet('test-pretty-facets-exposed-filters-single-choice', [
      'query' => [
        'category' => 'All',
        'keyword' => 'apple',
      ],
    ]);
    // See that we have been redirected to the pretty path.
    $this
      ->assertSession()
      ->addressEquals('test-pretty-facets-exposed-filters-single-choice/keyword/apple');
    // Entity 2 has the "apple" keyword.
    $this
      ->assertSession()
      ->pageTextContains('entity:entity_test_mulrev_changed/2:en');
    // Entity 3 does not.
    $this
      ->assertSession()
      ->pageTextNotContains('entity:entity_test_mulrev_changed/3:en');

    // Submit the views exposed form with a category and a keyword selected.
    $this->drupalGet('test-pretty-facets-exposed-filters-single-choice', [
      'query' => [
        'category' => 'item_category',
        'keyword' => 'apple',
      ],
    ]);
    // See that we have been redirected to the pretty path, category before
    // keyword because that is the filter order configured in the view.
    $this
      ->assertSession()
      ->addressEquals('test-pretty-facets-exposed-filters-single-choice/category/item_category/keyword/apple');
    // Entity 2 has the "item_category" category and "apple" keyword.
    $this
      ->assertSession()
      ->pageTextContains('entity:entity_test_mulrev_changed/2:en');
    // Entity 3 does not.
    $this
      ->assertSession()
      ->pageTextNotContains('entity:entity_test_mulrev_changed/3:en');
  }

}
