<?php

namespace Drupal\Tests\ng_lightbox\Kernel;

use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;

/**
 * Test the NG Lightbox service.
 *
 * @group ng_lightbox
 */
class NgLightboxTest extends KernelTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  protected static $modules = ['system', 'node', 'user', 'path_alias', 'ng_lightbox', 'path'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    \Drupal::service('router.builder')->rebuild();

    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installEntitySchema('path_alias');
    $this->installConfig(['ng_lightbox']);

    // Create the node type.
    NodeType::create(['type' => 'page'])->save();
  }
  /**
   * Test the pattern matching for link paths.
   */
  public function testPatternMatching() {

    // Test the patterns are enabled on links as expected.
    $node = Node::create(['type' => 'page', 'title' => $this->randomString()]);
    $node->save();
    $value_patterns = $node->toUrl()->toString();
    \Drupal::configFactory()->getEditable('ng_lightbox.settings')
      ->set('patterns', $value_patterns)
      ->save();
    $this->assertLightboxEnabled(Link::fromTextAndUrl('Normal Path', $node->toUrl())->toString(TRUE)->getGeneratedLink());

    // Create a second node and make sure it doesn't get lightboxed.
    $secondnode = Node::create(['type' => 'page', 'title' => $this->randomString()]);
    $secondnode->save();
    $this->assertLightboxNotEnabled(Link::fromTextAndUrl('Second Path', $secondnode->toUrl())->toString(TRUE)->getGeneratedLink());

    $this->assertLightboxNotEnabled(Link::fromTextAndUrl('Empty Path', Url::fromRoute('<nolink>'))->toString(TRUE)->getGeneratedLink());
  }

  /**
   * Asserts the lightbox was enabled for the generated link.
   *
   * @param string $link
   *   The rendered link.
   */
  protected function assertLightboxEnabled(string $link) {
    $this->assertStringContainsString('use-ajax', $link);
    $this->assertStringContainsString('data-dialog-type', $link);
  }

  /**
   * Asserts the lightbox was not enabled for the generated link.
   *
   * @param string $link
   *   The rendered link.
   */
  protected function assertLightboxNotEnabled(string $link) {
    $this->assertStringNotContainsString('use-ajax', $link);
    $this->assertStringNotContainsString('data-dialog-type', $link);
  }

  /**
   * Test a URL that only has a hash.
   */
  public function testHashOnlyUrls() {
    $url = Url::fromUserInput('#hash-only-url');
    $this->assertFalse($this->container->get('ng_lightbox')->isNgLightboxEnabledPath($url));
  }

}
