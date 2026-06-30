<?php

declare(strict_types=1);

namespace Drupal\Tests\name\Kernel\Plugin\Field\FieldType;

use Drupal\Core\Form\FormState;
use Drupal\Core\Render\RenderContext;
use Drupal\KernelTests\KernelTestBase;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\name\Plugin\Field\FieldType\NameItem;
use Drupal\user\Entity\User;

/**
 * Tests the Name field type plugin.
 *
 * @group name
 * @coversDefaultClass \Drupal\name\Plugin\Field\FieldType\NameItem
 */
final class NameItemTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'name',
    'field',
    'entity_test',
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
    $this->installEntitySchema('entity_test');
    $this->installEntitySchema('user');
    $this->container->get('entity_type.listener')
      ->onEntityTypeCreate(\Drupal::entityTypeManager()->getDefinition('entity_test'));
    $this->container->get('entity_type.listener')
      ->onEntityTypeCreate(\Drupal::entityTypeManager()->getDefinition('user'));
  }

  /**
   * @covers ::schema
   */
  public function testSchemaHasAllColumnsAndIndexes(): void {
    $storage = FieldStorageConfig::create([
      'field_name' => 'field_name_schema',
      'entity_type' => 'entity_test',
      'type' => 'name',
    ]);

    $schema = NameItem::schema($storage);

    foreach (['title', 'given', 'middle', 'family', 'generational', 'credentials'] as $column) {
      $this->assertArrayHasKey($column, $schema['columns']);
    }
    $this->assertArrayHasKey('given', $schema['indexes']);
    $this->assertArrayHasKey('family', $schema['indexes']);
  }

  /**
   * @covers ::isEmpty
   */
  public function testIsEmptyReturnsTrueForBlankItem(): void {
    $this->installNameField('field_name_item_blank', [
      'components' => ['given' => TRUE, 'family' => TRUE],
    ]);

    $entity = EntityTest::create(['name' => 'Blank']);
    $item = $entity->get('field_name_item_blank')->appendItem([]);

    $this->assertTrue($item->isEmpty());
  }

  /**
   * @covers ::isEmpty
   */
  public function testIsEmptyReturnsFalseWhenGivenNameSet(): void {
    $this->installNameField('field_name_item_given');

    $entity = EntityTest::create(['name' => 'Given']);
    $item = $entity->get('field_name_item_given')->appendItem([
      'given' => 'Pat',
    ]);

    $this->assertFalse($item->isEmpty());
  }

  /**
   * @covers ::isEmpty
   */
  public function testIsEmptyIgnoresTitleInRawValuesWhenNoPropertiesExist(): void {
    $this->installNameField('field_name_item_title');

    $entity = EntityTest::create(['name' => 'Title']);
    $item = $entity->get('field_name_item_title')->appendItem([]);

    $property = new \ReflectionProperty($item, 'values');
    $property->setAccessible(TRUE);
    $property->setValue($item, ['title' => 'Dr.']);

    $this->assertTrue($item->isEmpty());
  }

  /**
   * @covers ::filteredArray
   */
  public function testFilteredArrayReturnsOnlyActiveComponents(): void {
    $this->installNameField('field_name_filtered', [
      'components' => [
        'given' => TRUE,
        'middle' => FALSE,
        'family' => TRUE,
      ],
    ]);

    $entity = EntityTest::create(['name' => 'Filtered']);
    $item = $entity->get('field_name_filtered')->appendItem([
      'given' => 'Pat',
      'middle' => 'M',
      'family' => 'Lee',
    ]);

    $filtered = $item->filteredArray();
    $this->assertArrayHasKey('given', $filtered);
    $this->assertArrayHasKey('family', $filtered);
    $this->assertArrayNotHasKey('middle', $filtered);
  }

  /**
   * @covers ::activeComponents
   */
  public function testActiveComponentsReturnsEnabledLabels(): void {
    $this->installNameField('field_name_active', [
      'components' => [
        'given' => TRUE,
        'family' => TRUE,
        'credentials' => FALSE,
      ],
      'labels' => [
        'given' => 'Given custom',
        'family' => 'Family custom',
      ],
    ]);

    $entity = EntityTest::create(['name' => 'Active']);
    $item = $entity->get('field_name_active')->appendItem([
      'given' => 'Pat',
      'family' => 'Lee',
    ]);

    $components = $item->activeComponents();
    $this->assertSame('Given custom', $components['given']);
    $this->assertSame('Family custom', $components['family']);
    $this->assertArrayNotHasKey('credentials', $components);
  }

  /**
   * @covers ::generateSampleValue
   */
  public function testGenerateSampleValueReturnsNameArray(): void {
    $field = $this->installNameField('field_name_sample');
    $sample = NameItem::generateSampleValue($field);

    $this->assertIsArray($sample);
    $this->assertArrayHasKey('given', $sample);
    $this->assertArrayHasKey('family', $sample);
  }

  /**
   * @covers ::__sleep
   */
  public function testSleepRemovesValuesProperty(): void {
    $this->installNameField('field_name_sleep');

    $entity = EntityTest::create(['name' => 'Sleep']);
    $item = $entity->get('field_name_sleep')->appendItem([
      'given' => 'Pat',
      'family' => 'Lee',
    ]);
    $keys = $item->__sleep();

    $this->assertIsArray($keys);
    $this->assertNotContains('values', $keys);
  }

  /**
   * @covers ::fieldSettingsForm
   */
  public function testFieldSettingsFormAddsUserPreferredElementsForUserFields(): void {
    $field_name = 'field_name_user_pref';
    $this->installUserNameField($field_name);

    $account = User::create(['name' => 'name-item-settings']);
    $account->save();
    $item = $account->get($field_name)->appendItem([]);

    $renderer = $this->container->get('renderer');
    $form = $renderer->executeInRenderContext(
      new RenderContext(),
      static fn() => $item->fieldSettingsForm([], new FormState()),
    );

    $this->assertSame('checkbox', $form['name_user_preferred']['#type']);
    $expected_default = \Drupal::config('name.settings')->get('user_preferred') === $field_name ? 1 : 0;
    $this->assertSame($expected_default, $form['name_user_preferred']['#default_value']);
    $this->assertSame('hidden', $form['name_user_preferred_fieldname']['#type']);
    $this->assertSame($field_name, $form['name_user_preferred_fieldname']['#default_value']);
    $this->assertSame('select', $form['override_format']['#type']);
    $this->assertContains([NameItem::class, 'validateUserPreferred'], $form['#element_validate']);
  }

  /**
   * @covers ::validateUserPreferred
   */
  public function testValidateUserPreferredUpdatesAndClearsConfiguration(): void {
    $field_name = 'field_name_user_validate';
    $this->installUserNameField($field_name);

    User::create(['name' => 'name-item-user-a'])->save();
    User::create(['name' => 'name-item-user-b'])->save();

    $form_state = new FormState();
    $complete_form = [];

    $checked_element = [
      'name_user_preferred' => ['#value' => 1],
      'name_user_preferred_fieldname' => ['#default_value' => $field_name],
    ];
    NameItem::validateUserPreferred($checked_element, $form_state, $complete_form);
    $this->assertSame($field_name, \Drupal::config('name.settings')->get('user_preferred'));

    $unchecked_element = [
      'name_user_preferred' => ['#value' => 0],
      'name_user_preferred_fieldname' => ['#default_value' => $field_name],
    ];
    NameItem::validateUserPreferred($unchecked_element, $form_state, $complete_form);
    $this->assertNull(\Drupal::config('name.settings')->get('user_preferred'));
  }

  /**
   * Installs a configurable name field.
   */
  private function installNameField(string $field_name, array $field_settings = []): FieldConfig {
    $storage = FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'entity_test',
      'type' => 'name',
    ]);
    $storage->save();

    $field = FieldConfig::create([
      'field_storage' => $storage,
      'field_name' => $field_name,
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
      'settings' => $field_settings,
    ]);
    $field->save();

    return $field;
  }

  /**
   * Installs a Name field on the user entity.
   */
  private function installUserNameField(string $field_name, array $field_settings = []): FieldConfig {
    $storage = FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'user',
      'type' => 'name',
    ]);
    $storage->save();

    $field = FieldConfig::create([
      'field_storage' => $storage,
      'field_name' => $field_name,
      'entity_type' => 'user',
      'bundle' => 'user',
      'settings' => $field_settings,
    ]);
    $field->save();

    return $field;
  }

}
