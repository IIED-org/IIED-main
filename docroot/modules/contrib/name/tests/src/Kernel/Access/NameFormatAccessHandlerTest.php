<?php

declare(strict_types=1);

namespace Drupal\Tests\name\Kernel\Access;

use Drupal\Core\Session\AccountInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\name\Access\NameFormatAccessHandler;
use Drupal\name\Entity\NameFormat;
use Drupal\name\Entity\NameListFormat;

/**
 * @coversDefaultClass \Drupal\name\Access\NameFormatAccessHandler
 *
 * @group name
 */
class NameFormatAccessHandlerTest extends KernelTestBase {

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
   * Verifies both entity types resolve the renamed access handler.
   *
   * @coversNothing
   */
  public function testEntityTypesResolveRenamedAccessHandler(): void {
    $entity_type_manager = $this->container->get('entity_type.manager');

    $this->assertInstanceOf(
      NameFormatAccessHandler::class,
      $entity_type_manager->getAccessControlHandler('name_format'),
    );
    $this->assertInstanceOf(
      NameFormatAccessHandler::class,
      $entity_type_manager->getAccessControlHandler('name_list_format'),
    );
  }

  /**
   * @covers ::checkAccess
   */
  public function testUpdateAccessRequiresPermission(): void {
    $entity = NameFormat::create([
      'id' => 'kernel_access_update',
      'label' => 'Kernel access update',
      'pattern' => '!g',
    ]);
    $entity->save();

    $handler = $this->container->get('entity_type.manager')
      ->getAccessControlHandler('name_format');

    $this->assertTrue(
      $handler->access($entity, 'update', $this->createAccount(TRUE), TRUE)->isAllowed(),
    );
    $this->assertFalse(
      $handler->access($entity, 'update', $this->createAccount(FALSE, 2), TRUE)->isAllowed(),
    );
  }

  /**
   * @covers ::checkAccess
   */
  public function testLockedEntitiesCannotBeDeleted(): void {
    $name_format = NameFormat::create([
      'id' => 'kernel_locked_format',
      'label' => 'Kernel locked format',
      'pattern' => '!g',
      'locked' => TRUE,
    ]);
    $name_format->save();

    $list_format = NameListFormat::create([
      'id' => 'kernel_locked_list',
      'label' => 'Kernel locked list',
      'locked' => TRUE,
    ]);
    $list_format->save();

    $entity_type_manager = $this->container->get('entity_type.manager');
    $allowed_account = $this->createAccount(TRUE);

    $this->assertTrue(
      $entity_type_manager->getAccessControlHandler('name_format')
        ->access($name_format, 'delete', $allowed_account, TRUE)
        ->isForbidden(),
    );
    $this->assertTrue(
      $entity_type_manager->getAccessControlHandler('name_list_format')
        ->access($list_format, 'delete', $allowed_account, TRUE)
        ->isForbidden(),
    );
  }

  /**
   * @covers ::checkAccess
   */
  public function testUnlockedDeleteAndUnknownOperationBehavior(): void {
    $entity = NameListFormat::create([
      'id' => 'kernel_unlocked_list',
      'label' => 'Kernel unlocked list',
      'locked' => FALSE,
    ]);
    $entity->save();

    $handler = $this->container->get('entity_type.manager')
      ->getAccessControlHandler('name_list_format');

    $this->assertTrue(
      $handler->access($entity, 'delete', $this->createAccount(TRUE), TRUE)->isAllowed(),
    );
    $this->assertFalse(
      $handler->access($entity, 'delete', $this->createAccount(FALSE, 2), TRUE)->isAllowed(),
    );
    $this->assertTrue(
      $handler->access($entity, 'view', $this->createAccount(FALSE), TRUE)->isNeutral(),
    );
  }

  /**
   * Creates an account mock with the requested permission result.
   */
  private function createAccount(bool $has_permission, int $id = 1): AccountInterface {
    $account = $this->createMock(AccountInterface::class);
    $account->method('id')
      ->willReturn($id);
    $account->method('hasPermission')
      ->with('administer site configuration')
      ->willReturn($has_permission);

    return $account;
  }

}
