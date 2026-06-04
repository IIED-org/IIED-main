<?php

declare(strict_types=1);

namespace Drupal\Tests\name\Unit\Hook;

use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\field\FieldConfigInterface;
use Drupal\name\Hook\FieldConfigHooks;

/**
 * @coversDefaultClass \Drupal\name\Hook\FieldConfigHooks
 *
 * @group name
 */
final class FieldConfigHooksTest extends UnitTestCase {

  /**
   * @covers ::fieldConfigDelete
   */
  public function testFieldConfigDeleteReturnsWhenSyncing(): void {
    $config_factory = $this->createMock(ConfigFactoryInterface::class);
    $config_factory->expects($this->never())->method('get');
    $config_factory->expects($this->never())->method('getEditable');

    $hooks = new FieldConfigHooks($config_factory);

    $entity = $this->createMock(FieldConfigInterface::class);
    $entity->method('isSyncing')->willReturn(TRUE);
    $entity->expects($this->never())->method('getTargetEntityTypeId');

    $hooks->fieldConfigDelete($entity);
  }

  /**
   * @covers ::fieldConfigDelete
   */
  public function testFieldConfigDeleteReturnsWhenNotUserEntityType(): void {
    $config_factory = $this->createMock(ConfigFactoryInterface::class);
    $config_factory->expects($this->never())->method('get');
    $config_factory->expects($this->never())->method('getEditable');

    $hooks = new FieldConfigHooks($config_factory);

    $entity = $this->createMock(FieldConfigInterface::class);
    $entity->method('isSyncing')->willReturn(FALSE);
    $entity->method('getTargetEntityTypeId')->willReturn('node');
    $entity->expects($this->never())->method('getTargetBundle');

    $hooks->fieldConfigDelete($entity);
  }

  /**
   * @covers ::fieldConfigDelete
   */
  public function testFieldConfigDeleteReturnsWhenNotUserBundle(): void {
    $config_factory = $this->createMock(ConfigFactoryInterface::class);
    $config_factory->expects($this->never())->method('get');
    $config_factory->expects($this->never())->method('getEditable');

    $hooks = new FieldConfigHooks($config_factory);

    $entity = $this->createMock(FieldConfigInterface::class);
    $entity->method('isSyncing')->willReturn(FALSE);
    $entity->method('getTargetEntityTypeId')->willReturn('user');
    $entity->method('getTargetBundle')->willReturn('other');
    $entity->expects($this->never())->method('getName');

    $hooks->fieldConfigDelete($entity);
  }

  /**
   * @covers ::fieldConfigDelete
   */
  public function testFieldConfigDeleteReturnsWhenPreferredFieldNameDiffers(): void {
    $settings = $this->createMock(Config::class);
    $settings->expects($this->once())
      ->method('get')
      ->with('user_preferred')
      ->willReturn('field_other');

    $config_factory = $this->createMock(ConfigFactoryInterface::class);
    $config_factory->expects($this->once())
      ->method('get')
      ->with('name.settings')
      ->willReturn($settings);
    $config_factory->expects($this->never())->method('getEditable');

    $hooks = new FieldConfigHooks($config_factory);

    $entity = $this->createMock(FieldConfigInterface::class);
    $entity->method('isSyncing')->willReturn(FALSE);
    $entity->method('getTargetEntityTypeId')->willReturn('user');
    $entity->method('getTargetBundle')->willReturn('user');
    $entity->method('getName')->willReturn('field_preferred');

    $hooks->fieldConfigDelete($entity);
  }

  /**
   * @covers ::fieldConfigDelete
   */
  public function testFieldConfigDeleteClearsPreferredWhenMatching(): void {
    $settings = $this->createMock(Config::class);
    $settings->method('get')
      ->with('user_preferred')
      ->willReturn('field_preferred');

    $editable = $this->createMock(Config::class);
    $editable->expects($this->once())
      ->method('set')
      ->with('user_preferred', '')
      ->willReturnSelf();
    $editable->expects($this->once())->method('save');

    $config_factory = $this->createMock(ConfigFactoryInterface::class);
    $config_factory->method('get')
      ->with('name.settings')
      ->willReturn($settings);
    $config_factory->expects($this->once())
      ->method('getEditable')
      ->with('name.settings')
      ->willReturn($editable);

    $hooks = new FieldConfigHooks($config_factory);

    $entity = $this->createMock(FieldConfigInterface::class);
    $entity->method('isSyncing')->willReturn(FALSE);
    $entity->method('getTargetEntityTypeId')->willReturn('user');
    $entity->method('getTargetBundle')->willReturn('user');
    $entity->method('getName')->willReturn('field_preferred');

    $hooks->fieldConfigDelete($entity);
  }

}
