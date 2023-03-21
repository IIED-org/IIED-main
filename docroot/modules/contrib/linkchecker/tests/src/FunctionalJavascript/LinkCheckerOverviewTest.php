<?php

namespace Drupal\Tests\linkchecker\FunctionalJavascript;

use Drupal\Core\Url;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Test Link checker overview view.
 *
 * @group linkchecker
 */
class LinkCheckerOverviewTest extends WebDriverTestBase {

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
    // Check that the states part does what it is supposed to do.
    // Unset a couple of things that used to end up triggering an undefined
    // index.
    $page = $this->getSession()->getPage();
    $page->fillField('result', '2');
    self::assertTrue($page->find('css', '[data-drupal-selector="edit-code"]')->hasAttribute('disabled'));
    $page->fillField('result', 'All');
    self::assertFalse($page->find('css', '[data-drupal-selector="edit-code"]')->hasAttribute('disabled'));
    $page->fillField('code', '200');
    self::assertTrue($page->find('css', '[data-drupal-selector="edit-result"]')->hasAttribute('disabled'));
    $page->fillField('code', '');
    self::assertFalse($page->find('css', '[data-drupal-selector="edit-result"]')->hasAttribute('disabled'));
    $data = $view_config->getRawData();
    foreach (['code', 'code_1'] as $field) {
      unset($data["display"]["default"]["display_options"]["filters"][$field]);
    }
    $view_config->setData($data);
    $view_config->save();
    $this->drupalGet(Url::fromRoute($route)->toString());
  }

}
