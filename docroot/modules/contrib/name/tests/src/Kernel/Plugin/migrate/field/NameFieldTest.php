<?php

declare(strict_types=1);

namespace Drupal\Tests\name\Kernel\Plugin\migrate\field;

use Drupal\KernelTests\KernelTestBase;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate_drupal\Plugin\MigrateFieldPluginManagerInterface;
use Drupal\name\Plugin\migrate\field\NameField;

/**
 * Tests discovery and behavior of the name migrate field plugin.
 *
 * @coversDefaultClass \Drupal\name\Plugin\migrate\field\NameField
 *
 * @group name
 */
class NameFieldTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field',
    'migrate',
    'migrate_drupal',
    'name',
    'system',
  ];

  /**
   * The migrate field plugin manager.
   */
  private MigrateFieldPluginManagerInterface $migrateFieldManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->migrateFieldManager = $this->container->get('plugin.manager.migrate.field');
  }

  /**
   * Verifies the migrate field plugin is discoverable under id "name".
   */
  public function testPluginDefinition(): void {
    $definition = $this->migrateFieldManager->getDefinition('name');
    $this->assertSame(NameField::class, $definition['class']);
  }

  /**
   * Verifies the plugin manager returns a NameField instance.
   */
  public function testCreateInstance(): void {
    $plugin = $this->migrateFieldManager->createInstance('name', []);
    $this->assertInstanceOf(NameField::class, $plugin);
  }

  /**
   * @covers ::getFieldFormatterMap
   * @covers ::getFieldWidgetMap
   * @covers ::processFieldValues
   */
  public function testMapsAndProcessFieldValuesViaContainer(): void {
    /** @var \Drupal\name\Plugin\migrate\field\NameField $plugin */
    $plugin = $this->migrateFieldManager->createInstance('name', []);

    $this->assertSame(
      ['name_formatter' => 'name_default'],
      $plugin->getFieldFormatterMap(),
    );
    $this->assertSame(
      ['name_widget' => 'name_default'],
      $plugin->getFieldWidgetMap(),
    );

    $field_name = 'field_real_name';
    $expected_process = [
      'plugin' => 'iterator',
      'source' => $field_name,
      'process' => [
        'title' => 'title',
        'given' => 'given',
        'middle' => 'middle',
        'family' => 'family',
        'generational' => 'generational',
        'credentials' => 'credentials',
      ],
    ];

    $migration = $this->createMock(MigrationInterface::class);
    $migration->expects($this->once())
      ->method('mergeProcessOfProperty')
      ->with($field_name, $expected_process);

    $plugin->processFieldValues($migration, $field_name, []);
  }

}
