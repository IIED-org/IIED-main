<?php

declare(strict_types=1);

namespace Drupal\Tests\name\Unit\Access;

use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\name\Access\NameFormatAccessHandler;
use Drupal\name\Entity\NameFormatInterface;

/**
 * @coversDefaultClass \Drupal\name\Access\NameFormatAccessHandler
 *
 * @group name
 */
class NameFormatAccessHandlerTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $container = new ContainerBuilder();
    $container->set('cache_contexts_manager', new class {

      /**
       * Accepts cache context validation requests in unit tests.
       */
      public function assertValidTokens(array $tokens = []): bool {
        return TRUE;
      }

    });
    \Drupal::setContainer($container);
  }

  /**
   * @covers ::checkAccess
   */
  public function testCreateAccessRequiresAdministerSiteConfiguration(): void {
    $handler = $this->createHandler();
    $entity = $this->createMock(EntityInterface::class);

    $this->assertTrue(
      $handler->checkEntityAccess(
        $entity,
        'create',
        $this->createAccount(TRUE),
      )->isAllowed(),
    );
    $this->assertFalse(
      $handler->checkEntityAccess(
        $entity,
        'create',
        $this->createAccount(FALSE),
      )->isAllowed(),
    );
  }

  /**
   * @covers ::checkAccess
   */
  public function testUpdateAccessRequiresAdministerSiteConfiguration(): void {
    $handler = $this->createHandler();
    $entity = $this->createMock(EntityInterface::class);

    $this->assertTrue(
      $handler->checkEntityAccess(
        $entity,
        'update',
        $this->createAccount(TRUE),
      )->isAllowed(),
    );
    $this->assertFalse(
      $handler->checkEntityAccess(
        $entity,
        'update',
        $this->createAccount(FALSE),
      )->isAllowed(),
    );
  }

  /**
   * @covers ::checkAccess
   */
  public function testDeleteAccessIsForbiddenForLockedEntity(): void {
    $handler = $this->createHandler();
    $entity = $this->createMock(NameFormatInterface::class);
    $entity->method('isLocked')
      ->willReturn(TRUE);

    $result = $handler->checkEntityAccess(
      $entity,
      'delete',
      $this->createAccount(TRUE),
    );

    $this->assertFalse($result->isAllowed());
    $this->assertTrue($result->isForbidden());
  }

  /**
   * @covers ::checkAccess
   */
  public function testDeleteAccessRequiresPermissionForUnlockedEntity(): void {
    $handler = $this->createHandler();
    $entity = $this->createMock(NameFormatInterface::class);
    $entity->method('isLocked')
      ->willReturn(FALSE);

    $this->assertTrue(
      $handler->checkEntityAccess(
        $entity,
        'delete',
        $this->createAccount(TRUE),
      )->isAllowed(),
    );
    $this->assertFalse(
      $handler->checkEntityAccess(
        $entity,
        'delete',
        $this->createAccount(FALSE),
      )->isAllowed(),
    );
  }

  /**
   * @covers ::checkAccess
   */
  public function testUnknownOperationFallsBackToNeutralAccess(): void {
    $handler = $this->createHandler();
    $entity = $this->createMock(EntityInterface::class);

    $result = $handler->checkEntityAccess(
      $entity,
      'view',
      $this->createAccount(FALSE),
    );

    $this->assertTrue($result->isNeutral());
  }

  /**
   * Creates the handler under test.
   */
  private function createHandler(): TestNameFormatAccessHandler {
    return new TestNameFormatAccessHandler(
      $this->createMock(EntityTypeInterface::class),
    );
  }

  /**
   * Creates an account mock with the requested permission result.
   */
  private function createAccount(bool $has_permission): AccountInterface {
    $account = $this->createMock(AccountInterface::class);
    $account->method('hasPermission')
      ->with('administer site configuration')
      ->willReturn($has_permission);

    return $account;
  }

}

/**
 * Exposes protected access checks for unit testing.
 */
class TestNameFormatAccessHandler extends NameFormatAccessHandler {

  /**
   * Calls the protected entity access check.
   */
  public function checkEntityAccess(EntityInterface $entity, string $operation, AccountInterface $account): AccessResultInterface {
    return $this->checkAccess($entity, $operation, $account);
  }

}
