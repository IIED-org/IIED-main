<?php

declare(strict_types=1);

namespace Drupal\Tests\name\Kernel\Service;

use Drupal\KernelTests\KernelTestBase;
use Drupal\name\Service\WidgetLayoutService;

/**
 * @coversDefaultClass \Drupal\name\Service\WidgetLayoutService
 *
 * @group name
 */
class WidgetLayoutServiceTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'name',
    'field',
    'system',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['field', 'name']);
    $this->installSchema('system', ['sequences']);
    $this->installEntitySchema('user');
  }

  /**
   * @covers ::getLayouts
   */
  public function testServiceRegistered(): void {
    $service = $this->container->get('name.widget_layouts');
    $this->assertInstanceOf(WidgetLayoutService::class, $service);
  }

  /**
   * @covers ::getLayouts
   */
  public function testLayoutsFromHookIncludeDefaults(): void {
    /** @var \Drupal\name\Service\WidgetLayoutService $service */
    $service = $this->container->get('name.widget_layouts');
    $layouts = $service->getLayouts();

    $this->assertArrayHasKey('stacked', $layouts);
    $this->assertArrayHasKey('inline', $layouts);

    foreach (['stacked', 'inline'] as $key) {
      $this->assertIsArray($layouts[$key]['library']);
      $this->assertIsArray($layouts[$key]['wrapper_attributes']);
      $this->assertContains('name-widget-wrapper', $layouts[$key]['wrapper_attributes']['class']);
    }

    $this->assertContains('name/widget.inline', $layouts['inline']['library']);
  }

}
