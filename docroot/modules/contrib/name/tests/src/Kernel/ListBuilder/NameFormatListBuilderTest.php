<?php

declare(strict_types=1);

namespace Drupal\Tests\name\Kernel\ListBuilder;

use Drupal\KernelTests\KernelTestBase;
use Drupal\name\ListBuilder\NameFormatListBuilder;
use Drupal\name\Utility\NameFormatHelp;

/**
 * @coversDefaultClass \Drupal\name\ListBuilder\NameFormatListBuilder
 *
 * @group name
 */
final class NameFormatListBuilderTest extends KernelTestBase {

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
      ->getHandler('name_format', 'list_builder');

    $this->assertInstanceOf(NameFormatListBuilder::class, $handler);
    $this->assertSame(
      $this->container->get('name.format_parser'),
      $this->readProperty($handler, 'parser'),
    );
    $this->assertSame(
      $this->container->get('name.generator'),
      $this->readProperty($handler, 'generator'),
    );
    $this->assertSame(
      $this->container->get('name.formatter'),
      $this->readProperty($handler, 'formatter'),
    );
  }

  /**
   * @covers ::render
   */
  public function testRenderIncludesListAndTokenHelp(): void {
    /** @var \Drupal\name\ListBuilder\NameFormatListBuilder $handler */
    $handler = $this->container->get('entity_type.manager')
      ->getHandler('name_format', 'list_builder');

    $render = $handler->render();
    $expected_help = NameFormatHelp::renderableTokenHelp();

    $this->assertArrayHasKey('list', $render);
    $this->assertArrayHasKey('help', $render);
    $this->assertNotEmpty($render['list']);
    $this->assertSame($expected_help['#type'], $render['help']['#type']);
    $this->assertSame((string) $expected_help['#title'], (string) $render['help']['#title']);
    $this->assertSame(
      array_keys($expected_help['format_parameters']),
      array_keys($render['help']['format_parameters']),
    );
  }

  /**
   * Reads a protected property from the builder.
   */
  private function readProperty(NameFormatListBuilder $builder, string $property): mixed {
    $reflection = new \ReflectionProperty(NameFormatListBuilder::class, $property);
    $reflection->setAccessible(TRUE);
    return $reflection->getValue($builder);
  }

}
