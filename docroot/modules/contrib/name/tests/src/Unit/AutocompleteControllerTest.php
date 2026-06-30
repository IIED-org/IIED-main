<?php

declare(strict_types=1);

namespace Drupal\Tests\name\Unit;

use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\name\Controller\AutocompleteController;
use Drupal\name\Service\AutocompleteInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Tests controller access checks and response mapping.
 *
 * @group name
 * @coversDefaultClass \Drupal\name\Controller\AutocompleteController
 */
class AutocompleteControllerTest extends UnitTestCase {

  /**
   * @covers ::autocomplete
   */
  public function testAutocompleteDeniesAccessWhenCreateAccessFails(): void {
    $field_definition = $this->createMock(FieldDefinitionInterface::class);
    $field_definition->method('getType')->willReturn('name');

    $autocomplete = $this->createMock(AutocompleteInterface::class);
    $autocomplete->expects($this->never())->method('getMatches');

    $entity_field_manager = $this->getMockBuilder(EntityFieldManager::class)
      ->disableOriginalConstructor()
      ->getMock();
    $entity_field_manager->method('getFieldDefinitions')
      ->with('node', 'page')
      ->willReturn([
        'field_author' => $field_definition,
      ]);

    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->method('getAccessControlHandler')
      ->with('node')
      ->willReturn(new class() {

        /**
         * Denies bundle create access.
         */
        public function createAccess(string $bundle): bool {
          return FALSE;
        }

        /**
         * Unused because createAccess() already fails.
         */
        public function fieldAccess(string $operation, FieldDefinitionInterface $field_definition): bool {
          return TRUE;
        }

      });

    $controller = new AutocompleteController(
      $autocomplete,
      $entity_field_manager,
      $entity_type_manager,
    );

    $this->expectException(AccessDeniedHttpException::class);
    $controller->autocomplete(
      Request::create('/name/autocomplete', 'GET', ['q' => 'Al']),
      'field_author',
      'node',
      'page',
      'given',
    );
  }

  /**
   * @covers ::autocomplete
   */
  public function testAutocompleteDeniesAccessWhenFieldAccessFails(): void {
    $field_definition = $this->createMock(FieldDefinitionInterface::class);
    $field_definition->method('getType')->willReturn('name');

    $autocomplete = $this->createMock(AutocompleteInterface::class);
    $autocomplete->expects($this->never())->method('getMatches');

    $entity_field_manager = $this->getMockBuilder(EntityFieldManager::class)
      ->disableOriginalConstructor()
      ->getMock();
    $entity_field_manager->method('getFieldDefinitions')
      ->with('node', 'page')
      ->willReturn([
        'field_author' => $field_definition,
      ]);

    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->method('getAccessControlHandler')
      ->with('node')
      ->willReturn(new class() {

        /**
         * Allows bundle create access.
         */
        public function createAccess(string $bundle): bool {
          return TRUE;
        }

        /**
         * Denies field edit access after create access succeeds.
         */
        public function fieldAccess(string $operation, FieldDefinitionInterface $field_definition): bool {
          return FALSE;
        }

      });

    $controller = new AutocompleteController(
      $autocomplete,
      $entity_field_manager,
      $entity_type_manager,
    );

    $this->expectException(AccessDeniedHttpException::class);
    $controller->autocomplete(
      Request::create('/name/autocomplete', 'GET', ['q' => 'Al']),
      'field_author',
      'node',
      'page',
      'given',
    );
  }

  /**
   * @covers ::autocomplete
   */
  public function testAutocompleteMapsAssocMatchesToValueLabelRows(): void {
    $field_definition = $this->createMock(FieldDefinitionInterface::class);
    $field_definition->method('getType')->willReturn('name');

    $autocomplete = $this->createMock(AutocompleteInterface::class);
    $autocomplete->expects($this->once())
      ->method('getMatches')
      ->with($field_definition, 'given', 'Al')
      ->willReturn([
        123 => 456,
        'Alice' => 'Alice Label',
      ]);

    $entity_field_manager = $this->getMockBuilder(EntityFieldManager::class)
      ->disableOriginalConstructor()
      ->getMock();
    $entity_field_manager->method('getFieldDefinitions')
      ->with('node', 'page')
      ->willReturn([
        'field_author' => $field_definition,
      ]);

    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->method('getAccessControlHandler')
      ->with('node')
      ->willReturn(new class() {

        /**
         * Allows bundle create access.
         */
        public function createAccess(string $bundle): bool {
          return TRUE;
        }

        /**
         * Allows field edit access.
         */
        public function fieldAccess(string $operation, FieldDefinitionInterface $field_definition): bool {
          return TRUE;
        }

      });

    $controller = new AutocompleteController(
      $autocomplete,
      $entity_field_manager,
      $entity_type_manager,
    );

    $response = $controller->autocomplete(
      Request::create('/name/autocomplete', 'GET', ['q' => 'Al']),
      'field_author',
      'node',
      'page',
      'given',
    );

    $this->assertSame([
      [
        'value' => '123',
        'label' => '456',
      ],
      [
        'value' => 'Alice',
        'label' => 'Alice Label',
      ],
    ], json_decode((string) $response->getContent(), TRUE));
  }

}
