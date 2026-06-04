<?php

declare(strict_types=1);

namespace Drupal\Tests\name\Unit\Traits;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Tests\UnitTestCase;
use Drupal\name\Traits\NameAdditionalPreferredTrait;

/**
 * Tests for NameAdditionalPreferredTrait.
 *
 * @group name
 * @coversDefaultClass \Drupal\name\Traits\NameAdditionalPreferredTrait
 */
class NameAdditionalPreferredTraitTest extends UnitTestCase {

  /**
   * The mocked entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The mocked entity field manager.
   */
  protected EntityFieldManagerInterface $entityFieldManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->entityFieldManager = $this->createMock(EntityFieldManagerInterface::class);

    $container = new ContainerBuilder();
    $container->set('string_translation', $this->getStringTranslationStub());
    $container->set('entity_type.manager', $this->entityTypeManager);
    $container->set('entity_field.manager', $this->entityFieldManager);
    \Drupal::setContainer($container);
  }

  /**
   * Builds an anonymous trait consumer.
   *
   * @param bool $is_field
   *   Override of getTraitUsageIsField() return value.
   * @param bool $inject_services
   *   Whether to pre-assign the trait's service properties. FALSE forces the
   *   trait to resolve them through \Drupal::service().
   */
  protected function createConsumer(bool $is_field, bool $inject_services = TRUE): object {
    $consumer = new class() {
      use NameAdditionalPreferredTrait;
      use StringTranslationTrait;

      /**
       * Settings exposed via getSetting().
       *
       * @var array<string, mixed>
       */
      public array $settings = [];

      /**
       * The field definition used by the trait.
       */
      public ?FieldDefinitionInterface $fieldDefinition = NULL;

      /**
       * Controls getTraitUsageIsField() return value.
       */
      public bool $isField = FALSE;

      /**
       * Declared to silence PHP 8.2 dynamic property deprecations.
       */
      public ?EntityTypeManagerInterface $entityTypeManager = NULL;

      /**
       * Declared to silence PHP 8.2 dynamic property deprecations.
       */
      public ?EntityFieldManagerInterface $entityFieldManager = NULL;

      /**
       * Returns a setting by key.
       */
      public function getSetting(string $key): mixed {
        return $this->settings[$key] ?? NULL;
      }

      /**
       * Returns the field definition.
       */
      public function getFieldDefinition(): ?FieldDefinitionInterface {
        return $this->fieldDefinition;
      }

      /**
       * Overrides the trait's check with a testable value.
       */
      protected function getTraitUsageIsField(): bool {
        return $this->isField;
      }

      /**
       * Exposes the protected defaults.
       *
       * @return array<string, mixed>
       *   Default additional-preferred settings.
       */
      public static function publicDefaults(): array {
        return static::getDefaultAdditionalPreferredSettings();
      }

      /**
       * Exposes the protected form builder.
       *
       * @return array<string, mixed>
       *   Settings form elements.
       */
      public function publicForm(array &$form, FormStateInterface $form_state): array {
        return $this->getNameAdditionalPreferredSettingsForm($form, $form_state);
      }

      /**
       * Exposes the protected summary builder.
       *
       * @param array<int, mixed> $summary
       *   Summary array to populate.
       */
      public function publicSummary(array &$summary): void {
        $this->settingsNameAdditionalPreferredSummary($summary);
      }

      /**
       * Exposes the protected sources helper.
       *
       * @return array<string, mixed>
       *   Discovered additional sources.
       */
      public function publicSources(): array {
        return $this->getAdditionalSources();
      }

      /**
       * Exposes the empty option helper.
       */
      public function publicEmpty(): mixed {
        return $this->getEmptyOption();
      }

    };
    $consumer->isField = $is_field;
    if ($inject_services) {
      $consumer->entityTypeManager = $this->entityTypeManager;
      $consumer->entityFieldManager = $this->entityFieldManager;
    }
    return $consumer;
  }

  /**
   * Primes mocks needed by getAdditionalSources().
   *
   * @param string $entity_type_id
   *   Entity type id.
   * @param string|null $bundle
   *   Bundle id.
   * @param string $entity_type_label
   *   Entity type label.
   * @param string $bundle_label
   *   Bundle label; pass empty string to exercise the fallback.
   * @param array<string, array{label: string, base: bool}> $fields
   *   Keyed by field name.
   * @param string $self_field_name
   *   Field definition name used by the trait consumer.
   */
  protected function primeAdditionalSources(
    string $entity_type_id,
    ?string $bundle,
    string $entity_type_label,
    string $bundle_label,
    array $fields,
    string $self_field_name,
  ): FieldDefinitionInterface {
    $field_definition = $this->createMock(FieldDefinitionInterface::class);
    $field_definition->method('getTargetEntityTypeId')->willReturn($entity_type_id);
    $field_definition->method('getTargetBundle')->willReturn($bundle);
    $field_definition->method('getName')->willReturn($self_field_name);

    $entity_type = $this->createMock(EntityTypeInterface::class);
    $entity_type->method('getBundleLabel')->willReturn($bundle_label);
    $entity_type->method('getLabel')->willReturn($entity_type_label);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getEntityType')->willReturn($entity_type);
    $this->entityTypeManager
      ->method('getStorage')
      ->with($entity_type_id)
      ->willReturn($storage);

    $field_defs = [];
    foreach ($fields as $name => $info) {
      $storage_def = $this->createMock(FieldStorageDefinitionInterface::class);
      $storage_def->method('isBaseField')->willReturn($info['base']);

      $definition = $this->createMock(FieldDefinitionInterface::class);
      $definition->method('getName')->willReturn($name);
      $definition->method('getLabel')->willReturn($info['label']);
      $definition->method('getFieldStorageDefinition')->willReturn($storage_def);
      $field_defs[$name] = $definition;
    }
    $this->entityFieldManager
      ->method('getFieldDefinitions')
      ->with($entity_type_id, $bundle)
      ->willReturn($field_defs);

    return $field_definition;
  }

  /**
   * @covers ::getDefaultAdditionalPreferredSettings
   */
  public function testDefaults(): void {
    $consumer = $this->createConsumer(FALSE);
    $this->assertSame([
      'preferred_field_reference'             => '',
      'preferred_field_reference_separator'   => ', ',
      'alternative_field_reference'           => '',
      'alternative_field_reference_separator' => ', ',
    ], $consumer::publicDefaults());
  }

  /**
   * @covers ::getEmptyOption
   */
  public function testEmptyOptionSwitchesOnTraitUsage(): void {
    $non_field = $this->createConsumer(FALSE);
    $this->assertSame('-- field default --', (string) $non_field->publicEmpty());

    $field = $this->createConsumer(TRUE);
    $this->assertSame('-- none --', (string) $field->publicEmpty());
  }

  /**
   * @covers ::getAdditionalSources
   */
  public function testSourcesNonUserWithBundleLabel(): void {
    $field_definition = $this->primeAdditionalSources(
      'node',
      'article',
      'Content',
      'Content type',
      [
        'title'      => ['label' => 'Title', 'base' => TRUE],
        'body'       => ['label' => 'Body', 'base' => FALSE],
        'field_self' => ['label' => 'Self', 'base' => FALSE],
        'field_nick' => ['label' => 'Nickname', 'base' => FALSE],
      ],
      'field_self',
    );
    $consumer = $this->createConsumer(FALSE);
    $consumer->fieldDefinition = $field_definition;

    $sources = $consumer->publicSources();

    $this->assertArrayHasKey('_self', $sources);
    $this->assertArrayNotHasKey('_self_property_name', $sources);
    $this->assertArrayNotHasKey('title', $sources, 'Base fields are skipped.');
    $this->assertArrayNotHasKey('field_self', $sources, 'The current field is skipped.');
    $this->assertArrayHasKey('body', $sources);
    $this->assertArrayHasKey('field_nick', $sources);
    $this->assertSame('Nickname', (string) $sources['field_nick']);
  }

  /**
   * @covers ::getAdditionalSources
   */
  public function testSourcesUserAddsLoginNameAndFallsBackToEntityLabel(): void {
    $field_definition = $this->primeAdditionalSources(
      'user',
      NULL,
      'User',
      '',
      [
        'uid'         => ['label' => 'ID', 'base' => TRUE],
        'field_nick'  => ['label' => 'Nick', 'base' => FALSE],
        'field_name'  => ['label' => 'Name', 'base' => FALSE],
      ],
      'field_name',
    );
    $consumer = $this->createConsumer(FALSE);
    $consumer->fieldDefinition = $field_definition;

    $sources = $consumer->publicSources();

    $this->assertArrayHasKey('_self', $sources);
    $this->assertArrayHasKey('_self_property_name', $sources);
    $this->assertArrayHasKey('field_nick', $sources);
    $this->assertArrayNotHasKey('field_name', $sources);
  }

  /**
   * @covers ::getAdditionalSources
   */
  public function testSourcesResolvesServicesFromContainer(): void {
    $field_definition = $this->primeAdditionalSources(
      'node',
      'page',
      'Content',
      'Content type',
      [
        'field_alt' => ['label' => 'Alternate', 'base' => FALSE],
      ],
      'field_name',
    );
    $consumer = $this->createConsumer(FALSE, FALSE);
    $consumer->fieldDefinition = $field_definition;

    $sources = $consumer->publicSources();

    $this->assertArrayHasKey('field_alt', $sources);
    $this->assertArrayHasKey('_self', $sources);
  }

  /**
   * @covers ::getNameAdditionalPreferredSettingsForm
   */
  public function testFormHasExpectedSelectsAndSeparators(): void {
    $field_definition = $this->primeAdditionalSources(
      'node',
      'article',
      'Content',
      'Content type',
      [
        'field_nick' => ['label' => 'Nickname', 'base' => FALSE],
      ],
      'field_name',
    );
    $consumer = $this->createConsumer(FALSE);
    $consumer->fieldDefinition = $field_definition;
    $consumer->settings = [
      'preferred_field_reference'             => 'field_nick',
      'preferred_field_reference_separator'   => ' / ',
      'alternative_field_reference'           => '',
      'alternative_field_reference_separator' => ', ',
    ];

    $form = [];
    $elements = $consumer->publicForm($form, $this->createMock(FormStateInterface::class));

    $this->assertSame('select', $elements['preferred_field_reference']['#type']);
    $this->assertSame('field_nick', $elements['preferred_field_reference']['#default_value']);
    $this->assertSame('-- field default --', (string) $elements['preferred_field_reference']['#empty_option']);
    $this->assertArrayHasKey('_self', $elements['preferred_field_reference']['#options']);
    $this->assertArrayHasKey('field_nick', $elements['preferred_field_reference']['#options']);

    $this->assertSame(' / ', $elements['preferred_field_reference_separator']['#default_value']);
    $this->assertSame(
      ['value' => ''],
      $elements['preferred_field_reference_separator']['#states']['invisible'][':input[name$="[preferred_field_reference]"]'],
    );
    $this->assertSame(
      ['value' => ''],
      $elements['alternative_field_reference_separator']['#states']['invisible'][':input[name$="[alternative_field_reference]"]'],
    );

    $this->assertSame('select', $elements['alternative_field_reference']['#type']);
    $this->assertArrayHasKey('_self', $elements['alternative_field_reference']['#options']);
  }

  /**
   * @covers ::settingsNameAdditionalPreferredSummary
   */
  public function testSummaryReportsConfiguredValuesWithValidAndInvalidTargets(): void {
    $field_definition = $this->primeAdditionalSources(
      'node',
      'article',
      'Content',
      'Content type',
      [
        'field_nick' => ['label' => 'Nickname', 'base' => FALSE],
      ],
      'field_name',
    );
    $consumer = $this->createConsumer(FALSE);
    $consumer->fieldDefinition = $field_definition;
    $consumer->settings = [
      'preferred_field_reference'   => 'field_nick',
      'alternative_field_reference' => 'missing_field',
    ];

    $summary = [];
    $consumer->publicSummary($summary);
    $strings = array_map('strval', $summary);

    $this->assertCount(2, $strings);
    $this->assertSame('Preferred: Nickname', $strings[0]);
    $this->assertSame('Alternative: -- invalid --', $strings[1]);
  }

  /**
   * @covers ::settingsNameAdditionalPreferredSummary
   */
  public function testSummaryFieldDefaultFallbackWithValidAndInvalidTargets(): void {
    $field_definition = $this->primeAdditionalSources(
      'node',
      'article',
      'Content',
      'Content type',
      [
        'field_nick' => ['label' => 'Nickname', 'base' => FALSE],
      ],
      'field_name',
    );
    $field_definition->method('getSetting')->willReturnCallback(fn (string $key) => match ($key) {
      'preferred_field_reference'   => 'field_nick',
      'alternative_field_reference' => 'missing_field',
      default                       => NULL,
    });

    $consumer = $this->createConsumer(FALSE);
    $consumer->fieldDefinition = $field_definition;

    $summary = [];
    $consumer->publicSummary($summary);
    $strings = array_map('strval', $summary);

    $this->assertCount(2, $strings);
    $this->assertSame('Preferred: field default (Nickname)', $strings[0]);
    $this->assertSame('Alternative: field default (-- invalid --)', $strings[1]);
  }

  /**
   * @covers ::settingsNameAdditionalPreferredSummary
   */
  public function testSummaryFieldDefaultNoneFallback(): void {
    $field_definition = $this->primeAdditionalSources(
      'node',
      'article',
      'Content',
      'Content type',
      [],
      'field_name',
    );
    $field_definition->method('getSetting')->willReturn(NULL);

    $consumer = $this->createConsumer(FALSE);
    $consumer->fieldDefinition = $field_definition;

    $summary = [];
    $consumer->publicSummary($summary);
    $strings = array_map('strval', $summary);

    $this->assertSame([
      'Preferred: field default (-- none --)',
      'Alternative: field default (-- none --)',
    ], $strings);
  }

  /**
   * @covers ::settingsNameAdditionalPreferredSummary
   */
  public function testSummaryForFieldItemSkipsFieldDefaults(): void {
    $consumer = $this->createConsumer(TRUE);

    $summary = [];
    $consumer->publicSummary($summary);

    $this->assertSame([], $summary);
  }

}
