<?php

declare(strict_types=1);

namespace Drupal\Tests\name\Kernel\ListBuilder;

use Drupal\KernelTests\KernelTestBase;
use Drupal\name\ListBuilder\NameListFormatListBuilder;

/**
 * @coversDefaultClass \Drupal\name\ListBuilder\NameListFormatListBuilder
 *
 * @group name
 */
final class NameListFormatListBuilderTest extends KernelTestBase {

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
   * @covers ::createInstance
   * @covers ::__construct
   */
  public function testHandlerUsesInjectedServicesFromContainer(): void {
    $handler = $this->container->get('entity_type.manager')
      ->getHandler('name_list_format', 'list_builder');

    $this->assertInstanceOf(NameListFormatListBuilder::class, $handler);
    $this->assertSame(
      $this->container->get('name.formatter'),
      $this->readProperty($handler, 'formatter'),
    );
    $this->assertSame(
      $this->container->get('name.format_parser'),
      $this->readProperty($handler, 'parser'),
    );
    $this->assertSame(
      $this->container->get('name.generator'),
      $this->readProperty($handler, 'generator'),
    );
  }

  /**
   * Verifies render output exists for installed list format entities.
   *
   * @coversNothing
   */
  public function testRenderReturnsListMarkupForInstalledConfigEntities(): void {
    /** @var \Drupal\name\ListBuilder\NameListFormatListBuilder $handler */
    $handler = $this->container->get('entity_type.manager')
      ->getHandler('name_list_format', 'list_builder');

    $render = $handler->render();

    $this->assertIsArray($render);
    $this->assertNotEmpty($render);
  }

  /**
   * Reads a protected property from the builder.
   */
  private function readProperty(NameListFormatListBuilder $builder, string $property): mixed {
    $reflection = new \ReflectionProperty(NameListFormatListBuilder::class, $property);
    $reflection->setAccessible(TRUE);
    return $reflection->getValue($builder);
  }

}
