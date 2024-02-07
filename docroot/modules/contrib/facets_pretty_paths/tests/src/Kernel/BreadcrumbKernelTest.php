<?php

namespace Drupal\Tests\facets_pretty_paths\Kernel;

use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\facets_pretty_paths\Traits\FacetsRequestTrait;

/**
 * Testing the Pretty Paths breadcrumb builder.
 *
 * @group facets_pretty_paths
 */
class BreadcrumbKernelTest extends KernelTestBase {

  use FacetsRequestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'facets',
    'facets_pretty_paths',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installConfig(['system']);
  }

  /**
   * Tests the Pretty Paths breadcrumb builder.
   */
  public function testBreadcrumb() {
    // Initialise the current request and route match.
    $stack = $this->container->get('request_stack');
    $this->pushRequest($stack, 'My search title', 'content_type/page');

    $current_route_match = $this->container->get('current_route_match');

    /** @var \Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface $breadcrumb_builder */
    $breadcrumb_builder = $this->container->get('facets_pretty_paths.breadcrumb');
    $this->assertTrue($breadcrumb_builder->applies($current_route_match));
    $breadcrumb = $breadcrumb_builder->build($current_route_match);
    $this->assertInstanceOf(Breadcrumb::class, $breadcrumb);
    $this->assertCount(2, $breadcrumb->getLinks());

    $links = $breadcrumb->getLinks();
    $this->assertEquals('Home', $links[0]->getText()->render());
    $this->assertEquals('<front>', $links[0]->getUrl()->getRouteName());
    $this->assertEquals('My search title', $links[1]->getText()->render());
    $this->assertEquals('system.admin', $links[1]->getUrl()->getRouteName());
  }

}
