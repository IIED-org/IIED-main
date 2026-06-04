<?php

declare(strict_types=1);

namespace Drupal\Tests\name\Unit;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Tests\UnitTestCase;

require_once __DIR__ . '/../../../name.post_update.php';

/**
 * Unit coverage for selected procedural post-update hooks.
 *
 * @group name
 */
final class PostUpdateHooksUnitTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    \Drupal::setContainer(new ContainerBuilder());
    parent::tearDown();
  }

  /**
   * @covers \name_post_update_formatter_settings_link_and_external_sources
   */
  public function testFormatterSettingsLinkUpdateRemovesLegacyOutputSetting(): void {
    $field_name = 'field_name_link';
    $field_storage = new class($field_name) {

      /**
       * Creates field storage test double.
       */
      public function __construct(private readonly string $fieldName) {}

      /**
       * Returns the field machine name.
       */
      public function getName(): string {
        return $this->fieldName;
      }

    };

    $field = new class() {

      /**
       * Returns target entity type.
       */
      public function getTargetEntityTypeId(): string {
        return 'entity_test';
      }

      /**
       * Returns target bundle.
       */
      public function getTargetBundle(): string {
        return 'entity_test';
      }

    };

    $component = [
      'type' => 'name_default',
      'label' => 'above',
      'settings' => [
        'format' => 'default',
        'markup' => 'none',
        'output' => 'default',
        'list_format' => '',
      ],
    ];
    $view_display = new class($field_name, $component) {

      /**
       * Captures updated component settings.
       *
       * @var array<string, mixed>
       */
      public array $updatedComponent = [];

      /**
       * Creates view display test double.
       *
       * @param string $expectedFieldName
       *   The field machine name expected by this display.
       * @param array<string, mixed> $component
       *   The existing formatter component.
       */
      public function __construct(
        private readonly string $expectedFieldName,
        private readonly array $component,
      ) {}

      /**
       * Returns the current component for the expected field.
       *
       * @param string $field_name
       *   The field machine name being requested.
       *
       * @return array<string, mixed>
       *   The existing component settings.
       */
      public function getComponent(string $field_name): array {
        return $field_name === $this->expectedFieldName ? $this->component : [];
      }

      /**
       * Stores the updated component payload.
       *
       * @param string $field_name
       *   The field machine name being updated.
       * @param array<string, mixed> $component
       *   The updated component.
       */
      public function setComponent(string $field_name, array $component): self {
        if ($field_name === $this->expectedFieldName) {
          $this->updatedComponent = $component;
        }
        return $this;
      }

      /**
       * Mimics config entity save().
       */
      public function save(): int {
        return 1;
      }

    };

    $field_storage_config_storage = $this->createMock(EntityStorageInterface::class);
    $field_storage_config_storage->expects($this->once())
      ->method('loadByProperties')
      ->with(['type' => 'name'])
      ->willReturn([$field_storage]);

    $field_config_storage = $this->createMock(EntityStorageInterface::class);
    $field_config_storage->expects($this->once())
      ->method('loadByProperties')
      ->with(['field_name' => $field_name])
      ->willReturn([$field]);

    $entity_view_display_storage = $this->createMock(EntityStorageInterface::class);
    $entity_view_display_storage->expects($this->once())
      ->method('loadByProperties')
      ->with([
        'targetEntityType' => 'entity_test',
        'bundle' => 'entity_test',
      ])
      ->willReturn([$view_display]);

    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->expects($this->exactly(3))
      ->method('getStorage')
      ->willReturnMap([
        ['field_storage_config', $field_storage_config_storage],
        ['field_config', $field_config_storage],
        ['entity_view_display', $entity_view_display_storage],
      ]);

    $container = new ContainerBuilder();
    $container->set('entity_type.manager', $entity_type_manager);
    \Drupal::setContainer($container);

    name_post_update_formatter_settings_link_and_external_sources();

    $this->assertArrayNotHasKey(
      'output',
      $view_display->updatedComponent['settings'],
    );
    $this->assertSame(
      '',
      $view_display->updatedComponent['settings']['link_target'],
    );
    $this->assertSame(
      '',
      $view_display->updatedComponent['settings']['preferred_field_reference'],
    );
    $this->assertSame(
      ', ',
      $view_display->updatedComponent['settings']['preferred_field_reference_separator'],
    );
    $this->assertSame(
      '',
      $view_display->updatedComponent['settings']['alternative_field_reference'],
    );
    $this->assertSame(
      ', ',
      $view_display->updatedComponent['settings']['alternative_field_reference_separator'],
    );
  }

}
