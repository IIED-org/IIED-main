<?php

declare(strict_types=1);

namespace Drupal\Tests\name\Unit\Entity;

use Drupal\Core\Config\Entity\ConfigEntityType;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\name\Entity\NameFormat;

/**
 * @coversDefaultClass \Drupal\name\Entity\NameFormat
 *
 * @group name
 */
final class NameFormatTest extends UnitTestCase {

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
      'id' => 'formal',
    ]);

    $uri = $entity->uri();

    $this->assertSame('admin/config/regional/name/manage/formal', $uri['path']);
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
   * Creates a name format entity with a concrete entity type.
   */
  private function createEntity(array $values): NameFormat {
    $entity = new NameFormat($values, 'name_format');
    $entity_type = new ConfigEntityType([
      'id' => 'name_format',
      'label' => 'Name format',
      'config_prefix' => 'name_format',
      'entity_keys' => [
        'id' => 'id',
        'label' => 'label',
        'uuid' => 'uuid',
      ],
    ]);
    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->method('getDefinition')
      ->with('name_format')
      ->willReturn($entity_type);
    \Drupal::getContainer()->set('entity_type.manager', $entity_type_manager);

    return $entity;
  }

}
