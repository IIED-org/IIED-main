<?php

declare(strict_types=1);

namespace Drupal\Tests\name\Unit\Service;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\name\Service\WidgetLayoutInterface;
use Drupal\name\Service\WidgetLayoutService;

/**
 * @coversDefaultClass \Drupal\name\Service\WidgetLayoutService
 *
 * @group name
 */
class WidgetLayoutServiceTest extends UnitTestCase {

  /**
   * @covers ::__construct
   */
  public function testConstruct(): void {
    $cache = $this->createMock(CacheBackendInterface::class);
    $moduleHandler = $this->createMock(ModuleHandlerInterface::class);

    $service = new WidgetLayoutService($cache, $moduleHandler);

    $this->assertInstanceOf(WidgetLayoutInterface::class, $service);
    $reflection = new \ReflectionClass($service);

    $cacheProp = $reflection->getProperty('cache');
    $cacheProp->setAccessible(TRUE);
    $this->assertSame($cache, $cacheProp->getValue($service));

    $moduleHandlerProp = $reflection->getProperty('moduleHandler');
    $moduleHandlerProp->setAccessible(TRUE);
    $this->assertSame($moduleHandler, $moduleHandlerProp->getValue($service));
  }

  /**
   * @covers ::getLayouts
   */
  public function testCacheHitSkipsInvokeAllAndSecondCallSkipsCacheGet(): void {
    $cached = [
      'stacked' => [
        'label' => 'Stacked',
        'library' => [],
        'wrapper_attributes' => ['class' => ['name-widget-wrapper']],
      ],
    ];

    $cache = $this->createMock(CacheBackendInterface::class);
    $cache->expects($this->once())
      ->method('get')
      ->with('name:widget_layouts')
      ->willReturn((object) ['data' => $cached]);
    $cache->expects($this->never())->method('set');

    $moduleHandler = $this->createMock(ModuleHandlerInterface::class);
    $moduleHandler->expects($this->never())->method('invokeAll');

    $service = new WidgetLayoutService($cache, $moduleHandler);
    $this->assertSame($cached, $service->getLayouts());
    $this->assertSame($cached, $service->getLayouts());
  }

  /**
   * @covers ::getLayouts
   */
  public function testCacheMissInvokesHookMergesDefaultsAndSetsCache(): void {
    $cache = $this->createMock(CacheBackendInterface::class);
    $cache->expects($this->once())
      ->method('get')
      ->with('name:widget_layouts')
      ->willReturn(FALSE);
    $cache->expects($this->once())
      ->method('set')
      ->with(
        'name:widget_layouts',
        $this->callback(static function (array $layouts): bool {
          if (!isset($layouts['custom'])) {
            return FALSE;
          }
          $layout = $layouts['custom'];
          return $layout['label'] === 'Custom'
            && $layout['library'] === []
            && isset($layout['wrapper_attributes']['class'])
            && in_array('name-widget-wrapper', $layout['wrapper_attributes']['class'], TRUE);
        }),
      );

    $moduleHandler = $this->createMock(ModuleHandlerInterface::class);
    $moduleHandler->expects($this->once())
      ->method('invokeAll')
      ->with('name_widget_layouts')
      ->willReturn([
        'custom' => [
          'label' => 'Custom',
        ],
      ]);

    $service = new WidgetLayoutService($cache, $moduleHandler);
    $result = $service->getLayouts();
    $this->assertSame('Custom', $result['custom']['label']);
    $this->assertSame([], $result['custom']['library']);
    $this->assertContains('name-widget-wrapper', $result['custom']['wrapper_attributes']['class']);
  }

  /**
   * @covers ::getLayouts
   */
  public function testEmptyHookResultCachesAndSecondCallUsesPersistentCache(): void {
    $cache = $this->createMock(CacheBackendInterface::class);
    $cache->expects($this->exactly(2))
      ->method('get')
      ->with('name:widget_layouts')
      ->willReturnOnConsecutiveCalls(
        FALSE,
        (object) ['data' => []],
      );
    $cache->expects($this->once())
      ->method('set')
      ->with('name:widget_layouts', []);

    $moduleHandler = $this->createMock(ModuleHandlerInterface::class);
    $moduleHandler->expects($this->once())
      ->method('invokeAll')
      ->with('name_widget_layouts')
      ->willReturn([]);

    $service = new WidgetLayoutService($cache, $moduleHandler);
    $this->assertSame([], $service->getLayouts());
    $this->assertSame([], $service->getLayouts());
  }

}
