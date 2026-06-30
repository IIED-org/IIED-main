<?php

declare(strict_types=1);

namespace Drupal\Tests\name\Unit\Plugin\migrate\field;

use Drupal\Tests\UnitTestCase;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\name\Plugin\migrate\field\NameField;

/**
 * Tests the name migrate field plugin.
 *
 * @coversDefaultClass \Drupal\name\Plugin\migrate\field\NameField
 *
 * @group name
 */
class NameFieldTest extends UnitTestCase {

  /**
   * Minimal plugin definition for PluginBase.
   *
   * @return array<string, mixed>
   *   Keyed values for PluginBase construction.
   */
  private function pluginDefinition(): array {
    return [
      'id' => 'name',
      'class' => NameField::class,
      'provider' => 'name',
    ];
  }

  /**
   * Returns a new plugin instance for assertions.
   */
  private function createPlugin(): NameField {
    return new NameField([], 'name', $this->pluginDefinition());
  }

  /**
   * @covers ::getFieldFormatterMap
   */
  public function testGetFieldFormatterMap(): void {
    $this->assertSame(
      ['name_formatter' => 'name_default'],
      $this->createPlugin()->getFieldFormatterMap(),
    );
  }

  /**
   * @covers ::getFieldWidgetMap
   */
  public function testGetFieldWidgetMap(): void {
    $this->assertSame(
      ['name_widget' => 'name_default'],
      $this->createPlugin()->getFieldWidgetMap(),
    );
  }

  /**
   * @covers ::processFieldValues
   */
  public function testProcessFieldValuesMergesIteratorProcess(): void {
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

    $this->createPlugin()->processFieldValues($migration, $field_name, []);
  }

  /**
   * Ensures $data is not required for merge behavior.
   *
   * @covers ::processFieldValues
   */
  public function testProcessFieldValuesIgnoresDataParameter(): void {
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

    $this->createPlugin()->processFieldValues(
      $migration,
      $field_name,
      ['ignored' => TRUE],
    );
  }

}
