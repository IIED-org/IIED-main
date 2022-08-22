<?php

namespace Drupal\Tests\search_api_sorts\Functional;

use Drupal\search_api\Entity\Index;
use Drupal\Tests\search_api\Functional\ExampleContentTrait;
use Drupal\Tests\search_api\Functional\SearchApiBrowserTestBase;

/**
 * Base class for sorts web tests.
 */
abstract class SortsFunctionalBase extends SearchApiBrowserTestBase {

  use ExampleContentTrait;

  /**
   * The ID of the search display used for this test.
   *
   * @var string
   */
  protected $displayId;

  /**
   * The escaped ID of the search display used for this test.
   *
   * @var string
   */
  protected $escapedDisplayId;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
    'search_api_sorts',
    'search_api_test_db',
    'search_api_sorts_test_views',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create an index and server to work with.
    $this->getTestServer();
    $this->getTestIndex();
    $this->indexId = 'database_search_index';

    $index = Index::load($this->indexId);

    // Do not use a batch for tracking the initial items after creating an
    // index when running the tests via the GUI. Otherwise the test fails.
    \Drupal::state()->set('search_api_use_tracking_batch', FALSE);

    // Create content.
    $this->setUpExampleStructure();
    $this->insertExampleContent();

    $this->escapedDisplayId = 'views_page---search_api_sorts_test_view__page_1';
    $this->displayId = 'views_page:search_api_sorts_test_view__page_1';

    // Log in, so we can test all the things.
    $this->drupalLogin($this->adminUser);

    // Index all items.
    \Drupal::getContainer()
      ->get('search_api.index_task_manager')
      ->addItemsAll($index);
    $this->assertEquals(5, $this->indexItems($this->indexId));
  }

  /**
   * Asserts to position of an array of string in the page result.
   *
   * An array of positions is passed in here, we check for each of them what
   * their position is in the resulting html of the page.
   *
   * @param array $params
   *   An array of strings to assert positions for.
   */
  protected function assertPositions(array $params) {
    $webAssert = $this->assertSession();
    $pageContent = $this->getSession()->getPage()->getContent();
    foreach ($params as $k => $string) {
      $webAssert->responseContains($string);

      if ($k > 0) {
        $x_position = strpos($pageContent, $params[$k - 1]);
        $y_position = strpos($pageContent, $params[$k]);

        $this->assertTrue($x_position < $y_position, 'Position of ' . $params[$k - 1] . ' is before ' . $params[$k]);
      }
    }
  }

}
