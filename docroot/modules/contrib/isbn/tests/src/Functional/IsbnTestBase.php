<?php

namespace Drupal\Tests\isbn\Functional;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\BrowserTestBase;

/**
 * Provides a base class for testing Isbn functionality.
 */
abstract class IsbnTestBase extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'entity_test',
    'field_ui',
    'isbn',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * A field to use in this test class.
   *
   * @var \Drupal\field\Entity\FieldStorageConfig
   */
  protected $fieldStorage;

  /**
   * The field used in this test class.
   *
   * @var \Drupal\field\Entity\FieldConfig
   */
  protected $field;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->drupalLogin($this->drupalCreateUser([
      'view test entity',
      'administer entity_test content',
      'administer entity_test form display',
      'administer entity_test fields',
    ]));

    // Create a field with settings to validate.
    $this->createField();
  }

  /**
   * Creates an isbn test field.
   *
   * @return string
   *   The name of the field that got created.
   */
  protected function createField(): string {
    $field_name = mb_strtolower($this->randomMachineName());
    $field_label = $this->randomMachineName();

    $this->fieldStorage = FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'entity_test',
      'type' => 'isbn',
    ]);
    $this->fieldStorage->save();
    $this->field = FieldConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
      'label' => $field_label,
      'required' => TRUE,
    ]);
    $this->field->save();

    /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $display_repository */
    $display_repository = \Drupal::service('entity_display.repository');

    // Create a form display for the default form mode.
    $display_repository->getFormDisplay('entity_test', 'entity_test')
      ->setComponent($field_name, [
        'type' => 'isbn_widget',
      ])
      ->save();
    // Create a display for the full view mode.
    $display_repository->getViewDisplay('entity_test', 'entity_test', 'full')
      ->setComponent($field_name, [
        'type' => 'isbn_default',
      ])
      ->save();

    return $field_name;
  }

}
