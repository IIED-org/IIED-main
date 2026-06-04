<?php

declare(strict_types=1);

namespace Drupal\Tests\name\Unit\Entity;

use Drupal\Core\Config\Entity\ConfigEntityType;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\name\Entity\NameListFormat;

/**
 * @coversDefaultClass \Drupal\name\Entity\NameListFormat
 *
 * @group name
 */
final class NameListFormatTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    \Drupal::setContainer(new ContainerBuilder());
  }

  /**
   * @covers ::uri
   */
  public function testUriIncludesExpectedPathAndOptions(): void {
    $entity = $this->createEntity([
      'id' => 'default',
    ]);

    $uri = $entity->uri();

    $this->assertSame('admin/config/regional/name/list/manage/default', $uri['path']);
    $this->assertSame($entity, $uri['options']['entity']);
    $this->assertSame($entity->getEntityType(), $uri['options']['entity_type']);
  }

  /**
   * @covers ::isLocked
   */
  public function testIsLockedCastsValuesToBoolean(): void {
    $locked_entity = $this->createEntity([
      'id' => 'locked',
      'locked' => 1,
    ]);
    $unlocked_entity = $this->createEntity([
      'id' => 'unlocked',
      'locked' => 0,
    ]);

    $this->assertTrue($locked_entity->isLocked());
    $this->assertFalse($unlocked_entity->isLocked());
  }

  /**
   * @covers ::listSettings
   */
  public function testListSettingsReturnsConfiguredValuesWhenNoClampIsNeeded(): void {
    $entity = $this->createEntity([
      'id' => 'pass_through',
      'delimiter' => '; ',
      'and' => 'symbol',
      'delimiter_precedes_last' => 'always',
      'el_al_min' => 4,
      'el_al_first' => 2,
    ]);

    $this->assertSame([
      'delimiter' => '; ',
      'and' => 'symbol',
      'delimiter_precedes_last' => 'always',
      'el_al_min' => 4,
      'el_al_first' => 2,
    ], $entity->listSettings());
  }

  /**
   * @covers ::listSettings
   */
  public function testListSettingsClampsDisplayedCountToReduceThreshold(): void {
    $entity = $this->createEntity([
      'id' => 'clamped',
      'el_al_min' => 2,
      'el_al_first' => 5,
    ]);

    $this->assertSame(2, $entity->listSettings()['el_al_first']);
  }

  /**
   * Creates a name list format entity with a concrete entity type.
   */
  private function createEntity(array $values): NameListFormat {
    $entity = new NameListFormat($values, 'name_list_format');
    $entity_type = new ConfigEntityType([
      'id' => 'name_list_format',
      'label' => 'Name list format',
      'config_prefix' => 'name_list_format',
      'entity_keys' => [
        'id' => 'id',
        'label' => 'label',
        'uuid' => 'uuid',
      ],
    ]);
    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->method('getDefinition')
      ->with('name_list_format')
      ->willReturn($entity_type);
    \Drupal::getContainer()->set('entity_type.manager', $entity_type_manager);

    return $entity;
  }

}
