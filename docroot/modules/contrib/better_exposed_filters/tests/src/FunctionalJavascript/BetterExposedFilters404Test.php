<?php

namespace Drupal\Tests\better_exposed_filters\FunctionalJavascript;

/**
 * Tests that BEF exposed forms do not cause errors on 404 pages.
 *
 * @group better_exposed_filters
 */
class BetterExposedFilters404Test extends BetterExposedFiltersTestBase {

  /**
   * Tests that a 404 page.
   *
   * With an exposed bef_links filter does not cause an error.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testExposedFormOn404Page(): void {
    // Load a random URL to trigger a 404.
    $this->drupalGet('page-not-found/456737');
    // Check for error.
    $this->assertSession()->pageTextNotContains('Symfony\Component\Routing\Exception\ResourceNotFoundException');

    $config_factory = $this->container->get('config.factory');
    // Set the test node as the 404 page.
    $config_factory->getEditable('system.site')
      ->set('page.404', '/bef-test')
      ->save();

    $this->drupalGet('page-not-found/456737');

    // Check random element on page.
    $this->assertSession()->pageTextContains('Page one');
    $this->assertSession()->pageTextContains('Page two');
  }

}
