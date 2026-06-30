<?php

declare(strict_types=1);

namespace Drupal\Tests\name\Kernel\Service;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\name\Service\AutocompleteInterface;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;

/**
 * Kernel test for AutocompleteService::findFieldValues().
 *
 * Verifies per-component scoping, prefix match, dedupe, limits and that
 * access-denied entities never contribute values to the result set.
 *
 * @group name
 * @coversDefaultClass \Drupal\name\Service\AutocompleteService
 */
class AutocompleteFieldValueLookupTest extends EntityKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field',
    'name',
    'entity_test',
  ];

  /**
   * The service under test.
   */
  protected AutocompleteInterface $service;

  /**
   * Field definition for the installed name field.
   */
  protected FieldDefinitionInterface $fieldDefinition;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installConfig(['name']);
    $this->installEntitySchema('entity_test');

    FieldStorageConfig::create([
      'field_name' => 'field_name_test',
      'type' => 'name',
      'entity_type' => 'entity_test',
      'cardinality' => 1,
    ])->save();
    FieldConfig::create([
      'field_name' => 'field_name_test',
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
    ])->save();

    $this->service = $this->container->get('name.autocomplete');
    $this->fieldDefinition = $this->container->get('entity_field.manager')
      ->getFieldDefinitions('entity_test', 'entity_test')['field_name_test'];

    // Regular authenticated user with 'view test entity' permission.
    $role = Role::load(Role::AUTHENTICATED_ID);
    if (!$role) {
      $role = Role::create([
        'id' => Role::AUTHENTICATED_ID,
        'label' => 'Authenticated',
      ]);
      $role->save();
    }
    $role->grantPermission('view test entity')->save();

    $user = User::create([
      'name' => 'auth-' . uniqid('', TRUE),
      'status' => 1,
    ]);
    $user->save();
    $this->container->get('current_user')->setAccount($user);
  }

  /**
   * Creates an entity_test entity with the given component values.
   *
   * @param array<string, string> $values
   *   Component values keyed by component name.
   * @param string|null $name
   *   Optional entity label. The entity_test access handler denies view for
   *   entities labelled "forbid_access".
   */
  protected function createNameEntity(array $values, ?string $name = NULL): EntityTest {
    $entity = EntityTest::create([
      'name' => $name ?? 'test-' . uniqid('', TRUE),
      'field_name_test' => $values,
    ]);
    $entity->save();
    return $entity;
  }

  /**
   * Updates autocomplete settings and refreshes the field definition.
   *
   * @param array<string, list<string>> $sources
   *   Per-component autocomplete sources.
   */
  protected function setAutocompleteSources(array $sources): void {
    $field_config = FieldConfig::loadByName('entity_test', 'entity_test', 'field_name_test');
    $this->assertNotNull($field_config);
    $field_config->setSetting('autocomplete_source', $sources);
    $field_config->save();

    $this->container->get('entity_field.manager')->clearCachedFieldDefinitions();
    $this->fieldDefinition = $this->container->get('entity_field.manager')
      ->getFieldDefinitions('entity_test', 'entity_test')['field_name_test'];
  }

  /**
   * @covers ::findFieldValues
   * @covers ::collectEntityFieldMatches
   */
  public function testGivenLookupNeverReturnsFamilyValues(): void {
    $this->createNameEntity(['given' => 'Alice', 'family' => 'Smith']);
    $this->createNameEntity(['given' => 'Alfred', 'family' => 'Smythe']);
    $this->createNameEntity(['given' => 'Bob', 'family' => 'Alison']);

    $matches = $this->service->findFieldValues($this->fieldDefinition, 'given', 'Al', 10);
    $this->assertEqualsCanonicalizing(['Alice' => 'Alice', 'Alfred' => 'Alfred'], $matches);
    $this->assertArrayNotHasKey('Alison', $matches);
  }

  /**
   * @covers ::findFieldValues
   * @covers ::collectEntityFieldMatches
   */
  public function testFamilyLookupNeverReturnsGivenValues(): void {
    $this->createNameEntity(['given' => 'Smithson', 'family' => 'Jones']);
    $this->createNameEntity(['given' => 'Alice', 'family' => 'Smith']);

    $matches = $this->service->findFieldValues($this->fieldDefinition, 'family', 'Sm', 10);
    $this->assertSame(['Smith' => 'Smith'], $matches);
    $this->assertArrayNotHasKey('Smithson', $matches);
  }

  /**
   * @covers ::findFieldValues
   */
  public function testDedupesRepeatedValues(): void {
    $this->createNameEntity(['given' => 'Alice', 'family' => 'Smith']);
    $this->createNameEntity(['given' => 'Alice', 'family' => 'Jones']);
    $this->createNameEntity(['given' => 'Alice', 'family' => 'Brown']);

    $matches = $this->service->findFieldValues($this->fieldDefinition, 'given', 'A', 10);
    $this->assertSame(['Alice' => 'Alice'], $matches);
  }

  /**
   * @covers ::findFieldValues
   */
  public function testHonorsLimit(): void {
    foreach (['Ava', 'Abel', 'Abby', 'Adam', 'Aden'] as $given) {
      $this->createNameEntity(['given' => $given, 'family' => 'X']);
    }

    $matches = $this->service->findFieldValues($this->fieldDefinition, 'given', 'A', 3);
    $this->assertCount(3, $matches);
    foreach ($matches as $value) {
      $this->assertStringStartsWith('A', $value);
    }
  }

  /**
   * @covers ::findFieldValues
   */
  public function testEmptyTermReturnsEmpty(): void {
    $this->createNameEntity(['given' => 'Alice', 'family' => 'Smith']);

    $this->assertSame([], $this->service->findFieldValues($this->fieldDefinition, 'given', '', 10));
  }

  /**
   * @covers ::findFieldValues
   */
  public function testUnknownComponentReturnsEmpty(): void {
    $this->createNameEntity(['given' => 'Alice', 'family' => 'Smith']);

    $this->assertSame([], $this->service->findFieldValues($this->fieldDefinition, 'bogus', 'A', 10));
  }

  /**
   * @covers ::findFieldValues
   */
  public function testZeroLimitReturnsEmpty(): void {
    $this->createNameEntity(['given' => 'Alice', 'family' => 'Smith']);

    $this->assertSame([], $this->service->findFieldValues($this->fieldDefinition, 'given', 'A', 0));
  }

  /**
   * Contains mode returns mid-string matches that starts_with would reject.
   *
   * @covers ::findFieldValues
   */
  public function testContainsModeReturnsMidStringMatches(): void {
    $this->createNameEntity(['given' => 'Alice', 'family' => 'X']);
    $this->createNameEntity(['given' => 'Olivia', 'family' => 'X']);
    $this->createNameEntity(['given' => 'Bob', 'family' => 'X']);

    $starts_with = $this->service->findFieldValues($this->fieldDefinition, 'given', 'li', 10);
    $this->assertSame([], $starts_with, 'starts_with must not match mid-string');

    $contains = $this->service->findFieldValues($this->fieldDefinition, 'given', 'li', 10, 'contains');
    $this->assertEqualsCanonicalizing(['Alice' => 'Alice', 'Olivia' => 'Olivia'], $contains);
    $this->assertArrayNotHasKey('Bob', $contains);
  }

  /**
   * Uppercase terms are normalized before in-memory matching.
   *
   * @covers ::findFieldValues
   * @covers ::collectEntityFieldMatches
   */
  public function testUppercaseTermIsMatchedCaseInsensitively(): void {
    $this->createNameEntity(['given' => 'Alice', 'family' => 'Smith']);

    $matches = $this->service->findFieldValues(
      $this->fieldDefinition,
      'given',
      'AL',
      10,
    );

    $this->assertSame(['Alice' => 'Alice'], $matches);
  }

  /**
   * @covers ::getMatches
   */
  public function testGetMatchesNameTargetReadsGivenMiddleAndFamily(): void {
    $this->setAutocompleteSources([
      'given' => ['data'],
      'middle' => ['data'],
      'family' => ['data'],
      'title' => [],
      'credentials' => [],
      'generational' => [],
    ]);

    $this->createNameEntity(['given' => 'Sam', 'middle' => 'Sage', 'family' => 'Sanders']);
    $this->createNameEntity(['given' => 'Sally', 'middle' => 'Quinn', 'family' => 'Jones']);
    $this->createNameEntity(['given' => 'Bob', 'middle' => 'Saul', 'family' => 'Stone']);

    $matches = $this->service->getMatches($this->fieldDefinition, 'name', 'Sa');
    $this->assertArrayHasKey('Sam', $matches);
    $this->assertArrayHasKey('Sage', $matches);
    $this->assertArrayHasKey('Sally', $matches);
    $this->assertArrayHasKey('Sanders', $matches);
    $this->assertArrayHasKey('Saul', $matches);
    $this->assertArrayNotHasKey('Stone', $matches);
  }

  /**
   * @covers ::getMatches
   */
  public function testGetMatchesDedupesAcrossMultipleComponents(): void {
    $this->setAutocompleteSources([
      'given' => ['data'],
      'middle' => [],
      'family' => ['data'],
      'title' => [],
      'credentials' => [],
      'generational' => [],
    ]);

    $this->createNameEntity(['given' => 'Ava', 'family' => 'Jones']);
    $this->createNameEntity(['given' => 'Bob', 'family' => 'Ava']);

    $matches = $this->service->getMatches($this->fieldDefinition, 'given-family', 'A');
    $this->assertArrayHasKey('Ava', $matches);
    $this->assertCount(1, array_filter(array_keys($matches), static fn (string $key): bool => $key === 'Ava'));
  }

  /**
   * @covers ::getMatches
   */
  public function testGetMatchesNameAllTargetReadsEveryComponent(): void {
    $this->setAutocompleteSources([
      'given' => ['data'],
      'middle' => ['data'],
      'family' => ['data'],
      'title' => ['data'],
      'credentials' => ['data'],
      'generational' => ['data'],
    ]);

    $this->createNameEntity([
      'title' => 'Master',
      'given' => 'Martha',
      'middle' => 'May',
      'family' => 'Mason',
      'credentials' => 'MBA',
      'generational' => 'Major',
    ]);

    $matches = $this->service->getMatches($this->fieldDefinition, 'name-all', 'Ma');
    $this->assertArrayHasKey('Master', $matches);
    $this->assertArrayHasKey('Martha', $matches);
    $this->assertArrayHasKey('May', $matches);
    $this->assertArrayHasKey('Mason', $matches);
    $this->assertArrayHasKey('Major', $matches);
    $this->assertArrayNotHasKey('MBA', $matches);
  }

}
