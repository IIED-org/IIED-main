<?php

namespace Drupal\Tests\isbn\Kernel;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\Tests\field\Kernel\FieldKernelTestBase;
use Drupal\field\Entity\FieldStorageConfig;
use PHPUnit\Framework\AssertionFailedError;

/**
 * Tests the isbn field type.
 *
 * @group isbn
 */
class IsbnItemTest extends FieldKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['isbn'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create an isbn field storage and field for validation.
    FieldStorageConfig::create([
      'field_name' => 'field_isbn',
      'entity_type' => 'entity_test',
      'type' => 'isbn',
    ])->save();
    FieldConfig::create([
      'entity_type' => 'entity_test',
      'field_name' => 'field_isbn',
      'bundle' => 'entity_test',
    ])->save();
  }

  /**
   * Tests using entity fields of the isbn field type.
   */
  public function testIsbnItem() {
    // Verify entity creation.
    $entity = EntityTest::create();
    $value = '0123456789';
    $entity->field_isbn = $value;
    $entity->name->value = $this->randomMachineName();
    $this->entityValidateAndSave($entity);

    // Verify entity has been created properly.
    $id = $entity->id();
    $entity = EntityTest::load($id);
    $this->assertInstanceOf(FieldItemListInterface::class, $entity->field_isbn);
    $this->assertInstanceOf(FieldItemInterface::class, $entity->field_isbn[0]);
    $this->assertEquals($value, $entity->field_isbn->value);
    $this->assertEquals($value, $entity->field_isbn[0]->value);

    // Verify changing the isbn value.
    $new_value = '9790123456785';
    $entity->field_isbn->value = $new_value;
    $this->assertEquals($new_value, $entity->field_isbn->value);

    // Read changed entity and assert changed values.
    $this->entityValidateAndSave($entity);
    $entity = EntityTest::load($id);
    $this->assertEquals($new_value, $entity->field_isbn->value);

    // Test sample item generation.
    $entity = EntityTest::create();
    $entity->field_isbn->generateSampleItems();
    $this->entityValidateAndSave($entity);
  }

  /**
   * Tests the constraint validations.
   *
   * @dataProvider isbnValidationProvider
   */
  public function testIsbnValidation($value) {
    $this->expectException(AssertionFailedError::class);

    $entity = EntityTest::create();
    $entity->set('field_isbn', $value);
    $this->entityValidateAndSave($entity);
  }

  /**
   * Provider for testIsbnValidation().
   */
  public function isbnValidationProvider() {
    return [
      // Invalid ISBN numbers.
      ['abcdefghij'],
      ['abcdefghijklm'],
      ['012345678X'],
      ['1234567890'],
      // Invalid because of length.
      ['01234567890'],
      ['012345678901'],
      ['01234567890123'],
    ];
  }

}
