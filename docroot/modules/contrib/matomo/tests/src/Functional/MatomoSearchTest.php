<?php

declare(strict_types=1);

namespace Drupal\Tests\matomo\Functional;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\Traits\Core\CronRunTrait;

/**
 * Test search functionality of Matomo module.
 *
 * @group Matomo
 */
class MatomoSearchTest extends BrowserTestBase {

  use CronRunTrait;
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'matomo',
    'search',
    'node',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Admin user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->drupalCreateContentType(['type' => 'page', 'name' => 'Basic page']);

    $permissions = [
      'access administration pages',
      'administer matomo',
      'search content',
      'create page content',
      'edit own page content',
    ];

    // User to set up matomo.
    $this->adminUser = $this->drupalCreateUser($permissions);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests if search tracking is properly added to the page.
   */
  public function testMatomoSearchTracking(): void {
    $site_id = '1';
    $this->config('matomo.settings')->set('site_id', $site_id)->save();
    $this->config('matomo.settings')->set('url_http', 'http://www.example.com/matomo/')->save();
    $this->config('matomo.settings')->set('url_https', 'https://www.example.com/matomo/')->save();

    // Check tracking code visibility.
    $this->drupalGet('');
    $this->assertSession()->responseContains($site_id);

    $this->drupalGet('search/node');
    $this->assertSession()->responseNotContains('_paq.push(["trackSiteSearch", ');

    // Enable site search support.
    $this->config('matomo.settings')->set('track.site_search', 1)->save();

    // Search for random string.
    $search = [];
    $search['keys'] = $this->randomMachineName(8);

    // Create a node to search for.
    $edit = [];
    $edit['title[0][value]'] = 'This is a test title';
    $edit['body[0][value]'] = 'This test content contains ' . $search['keys'] . ' string.';
    $this->drupalGet('search/node');

    // Fire a search, it's expected to get 0 results.
    $this->submitForm($search, 'Search');
    $this->assertSession()->responseContains('_paq.push(["trackSiteSearch", ');
    $this->assertSession()->responseContains('window.matomo_search_results = 0;');
    $this->drupalGet('node/add/page');

    // Save the node.
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains($this->t('@type @title has been created.', [
      '@type' => 'Basic page',
      '@title' => $edit['title[0][value]'],
    ]));

    // Index the node or it cannot found.
    $this->cronRun();
    $this->drupalGet('search/node');

    $this->submitForm($search, 'Search');
    $this->assertSession()->responseContains('_paq.push(["trackSiteSearch", ');
    $this->assertSession()->responseContains('window.matomo_search_results = 1;');
    $this->drupalGet('node/add/page');

    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains($this->t('@type @title has been created.', [
      '@type' => 'Basic page',
      '@title' => $edit['title[0][value]'],
    ]));

    // Index the node or it cannot found.
    $this->cronRun();
    $this->drupalGet('search/node');

    $this->submitForm($search, 'Search');
    $this->assertSession()->responseContains('_paq.push(["trackSiteSearch", ');
    $this->assertSession()->responseContains('window.matomo_search_results = 2;');
  }

}
