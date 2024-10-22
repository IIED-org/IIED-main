<?php

namespace Drupal\Tests\cloudflare\Functional;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Tests\BrowserTestBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Ensure middleware does not introduce a side effect
 *
 * @group cloudflare
 */
#[\AllowDynamicProperties]
class OverrideGlobalsTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['cloudflare'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->container->get('config.factory')->getEditable('cloudflare.settings')
      ->set('client_ip_restore_enabled', TRUE)
      ->set('remote_addr_validate', FALSE)
      ->save();
  }

  /**
   * Test that the ajax page state is not corrupted.
   */
  public function testOverrides() {
    $http_kernel = $this->container->get('http_kernel');

    $request = Request::create('/');
    $request->server->set('HTTP_CF_CONNECTING_IP', '1.1.1.1');
    // Pass the AJAX page state to the request.
    $compressed_libraries = UrlHelper::compressQueryParameter('foo/bar,bar/zip');
    $request->query->set('ajax_page_state', ['libraries' => $compressed_libraries]);

    $http_kernel->handle($request);

    $expected = http_build_query(['ajax_page_state' => ['libraries' => $compressed_libraries]]);
    $this->assertEquals($expected, $request->server->get('QUERY_STRING'));

  }
}
