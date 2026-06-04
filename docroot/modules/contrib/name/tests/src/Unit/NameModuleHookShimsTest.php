<?php

declare(strict_types=1);

namespace Drupal\Tests\name\Unit;

use Drupal\Core\Field\FieldTypeCategoryManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\field\FieldConfigInterface;
use Drupal\field\FieldStorageConfigInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

require_once __DIR__ . '/../../../name.module';

/**
 * Unit tests for the NULL-service branch of every #[LegacyHook] shim.
 *
 * Each shim in name.module fetches its OOP hook service from the container
 * with NULL_ON_INVALID_REFERENCE so that early-bootstrap and rebuild paths do
 * not throw. These tests install a mock container that returns NULL for every
 * service request, then assert the shim returns its documented no-op value.
 *
 * @group name
 */
final class NameModuleHookShimsTest extends UnitTestCase {

  /**
   * Installs a container that returns NULL for any service request.
   */
  private function installNullContainer(): ContainerInterface {
    $container = $this->createMock(ContainerInterface::class);
    $container->method('get')->willReturn(NULL);
    \Drupal::setContainer($container);
    return $container;
  }

  /**
   * @covers ::name_help
   */
  public function testHelpReturnsEmptyArrayWhenHelpServiceIsNull(): void {
    $this->installNullContainer();

    $route_match = $this->createMock(RouteMatchInterface::class);

    $this->assertSame([], name_help('help.page.name', $route_match));
  }

  /**
   * @covers ::name_name_widget_layouts
   */
  public function testNameWidgetLayoutsReturnsEmptyArrayWhenServiceIsNull(): void {
    $this->installNullContainer();

    $this->assertSame([], name_name_widget_layouts());
  }

  /**
   * @covers ::name_theme
   */
  public function testThemeReturnsEmptyArrayWhenServiceIsNull(): void {
    $this->installNullContainer();

    $this->assertSame([], name_theme());
  }

  /**
   * @covers ::name_user_format_name_alter
   */
  public function testUserFormatNameAlterNoOpsWhenUserServiceIsNull(): void {
    $this->installNullContainer();

    $account = $this->createMock(AccountInterface::class);
    $name = 'Original';
    name_user_format_name_alter($name, $account);

    $this->assertSame('Original', $name);
  }

  /**
   * @covers ::name_user_load
   */
  public function testUserLoadNoOpsWhenUserServiceIsNull(): void {
    $this->installNullContainer();

    $users = [];
    name_user_load($users);

    $this->assertSame([], $users);
  }

  /**
   * @covers ::name_field_config_create
   */
  public function testFieldConfigCreateNoOpsWhenServiceIsNull(): void {
    $this->installNullContainer();

    $entity = $this->createMock(FieldConfigInterface::class);

    $this->assertNull(name_field_config_create($entity));
  }

  /**
   * @covers ::name_field_config_delete
   */
  public function testFieldConfigDeleteNoOpsWhenServiceIsNull(): void {
    $this->installNullContainer();

    $entity = $this->createMock(FieldConfigInterface::class);

    $this->assertNull(name_field_config_delete($entity));
  }

  /**
   * @covers ::name_field_views_data
   */
  public function testFieldViewsDataReturnsEmptyArrayWhenServiceIsNull(): void {
    $this->installNullContainer();

    $storage = $this->createMock(FieldStorageConfigInterface::class);

    $this->assertSame([], name_field_views_data($storage));
  }

  /**
   * @covers ::name_field_type_category_info_alter
   */
  public function testFieldTypeCategoryInfoAlterNoOpsWhenServiceIsNull(): void {
    $this->installNullContainer();

    $original = [
      FieldTypeCategoryManagerInterface::FALLBACK_CATEGORY => [
        'libraries' => [],
      ],
    ];
    $definitions = $original;
    name_field_type_category_info_alter($definitions);

    $this->assertSame($original, $definitions);
  }

}
