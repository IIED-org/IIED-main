<?php

namespace Drupal\Tests\ng_lightbox\Functional;


use Drupal\Tests\BrowserTestBase;

/**
 * A web test for NG Lightbox.
 *
 * @group ng_lightbox
 */
class NgLightboxWebTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $profile = 'minimal';

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['ng_lightbox', 'views', 'node', 'filter', 'path_alias', 'path'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $config = \Drupal::configFactory()->getEditable('ng_lightbox.settings');
    $this->createContentType(['type' => 'page']);
    $node = $this->drupalCreateNode();
    $config->set('patterns', '/node/' . $node->id());
    $config->save();
  }

  /**
   * Test that we can render a modal even before selecting one from the admin.
   */
  public function testDefaultModal() {
    $this->drupalGet('/node');
    $this->assertSession()->responseContains('data-dialog-type="modal"');
  }

}
