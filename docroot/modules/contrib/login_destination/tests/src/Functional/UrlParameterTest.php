<?php

namespace Drupal\Tests\login_destination\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Core\Url;

/**
 * Tests adding current parameter to links.
 *
 * @group login_destination
 */
class UrlParameterTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'classy';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['login_destination'];

  /**
   * Ensure no "current" query parameter is added to unrouted link elements.
   */
  public function testNoParameterOnUnroutedLink() {
    $element = [
      '#title' => $this->randomString(),
      '#type' => 'link',
      '#url' => Url::fromUserInput('/' . $this->randomMachineName()),
    ];
    $rendered_link = $this->container->get('renderer')->renderPlain($element)->__toString();
    $this->assertNotContains('?current=', $rendered_link);
  }

}
