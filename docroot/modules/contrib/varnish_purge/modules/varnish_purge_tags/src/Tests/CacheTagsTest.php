<?php

namespace Drupal\varnish_purge_tags\Tests;

use Drupal\purge\Tests\KernelTestBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests \Drupal\varnish_purge_tags\Plugin\Purge\Tags\CacheTags.
 *
 * @group varnish_purge_tags
 */
class CacheTagsTest extends KernelTestBase {

  /**
   * List of modules to enable for this test.
   *
   * @var array
   */
  protected static $modules = ['system', 'varnish_purge_tags'];

  /**
   * {@inheritdoc}
   */
  public function setUp($switch_to_memory_queue = TRUE) {
    parent::setUp();
    $this->installSchema('system', ['router']);
    \Drupal::service('router.builder')->rebuild();
  }

  /**
   * Test that the header value is exactly as expected (space separated).
   */
  public function testHeaderValue() {
    $request = Request::create('/system/401');
    $response = $this->container->get('http_kernel')->handle($request);
    $this->assertEquals(200, $response->getStatusCode());
    $this->assertEquals($response->headers->get('Cache-Tags'), 'config:user.role.anonymous rendered');
  }

}
