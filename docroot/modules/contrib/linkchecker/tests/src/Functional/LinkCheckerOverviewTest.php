<?php

namespace Drupal\Tests\linkchecker\Functional;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Test Link checker overview view.
 *
 * @group linkchecker
 */
class LinkCheckerOverviewTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'filter',
    'content_translation',
    'linkchecker',
    'node',
    'path',
    'views',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    $this->adminUser = $this->drupalCreateUser([
      'administer linkchecker',
      'bypass node access',
      'access broken links report',
    ]);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Test that we can go to the overview and see our URLs there.
   *
   * Also tests that our states functionality works and does not trigger any PHP
   * notices.
   */
  public function testOverviewWorks() {
    // Remove the result exposed filter.
    /** @var \Drupal\Core\Config\ImmutableConfig $view_config */
    $view_config = $this->container->get('config.factory')->getEditable('views.view.broken_links_report');
    // Now visit the view of broken links.
    $route = 'view.broken_links_report.page_1';
    $this->drupalGet(Url::fromRoute($route)->toString());
    self::assertEquals(200, $this->getSession()->getStatusCode());
    $data = $view_config->getRawData();
    foreach (['code', 'code_1'] as $field) {
      unset($data["display"]["default"]["display_options"]["filters"][$field]);
    }
    $view_config->setData($data);
    $view_config->save();
    $this->drupalGet(Url::fromRoute($route)->toString());
    self::assertEquals(200, $this->getSession()->getStatusCode());
  }

  /**
   * Test that the overview page works when using the result filter.
   *
   * @see https://www.drupal.org/project/linkchecker/issues/3248989
   */
  public function testOverViewWorksWithResultFilter() {
    $route = 'view.broken_links_report.page_1';
    $this->drupalGet(Url::fromRoute($route)->toString(), [
      'query' => [
        'result' => 1,
      ],
    ]);
    self::assertEquals(200, $this->getSession()->getStatusCode());
  }

}
