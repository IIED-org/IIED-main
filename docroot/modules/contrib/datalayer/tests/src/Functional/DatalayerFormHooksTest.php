<?php

namespace Drupal\Tests\datalayer\Functional;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\field_ui\Traits\FieldUiTestTrait;

/**
 * Functional test cases for datalayer form hooks.
 *
 * Tests the DatalayerFormHooks class which adds configuration options
 * to field configuration forms.
 *
 * @group DataLayer
 */
class DatalayerFormHooksTest extends BrowserTestBase {

  use FieldUiTestTrait;

  /**
   * Modules to install.
   *
   * @var array
   */
  protected static $modules = [
    'datalayer',
    'node',
    'field',
    'field_ui',
    'text',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * A user with permission to administer fields.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    // Create a content type.
    $this->drupalCreateContentType([
      'type' => 'article',
      'name' => 'Article',
    ]);

    // Create an admin user with field administration permissions.
    $this->adminUser = $this->drupalCreateUser([
      'access administration pages',
      'administer content types',
      'administer node fields',
      'administer node form display',
      'administer node display',
    ]);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests that datalayer form elements appear when output_fields is enabled.
   */
  public function testDatalayerFormElementsWithOutputFieldsEnabled() {
    // Enable output_fields setting.
    $this->config('datalayer.settings')
      ->set('output_fields', TRUE)
      ->save();

    // Create a test field.
    $field_name = 'field_test_text';
    $this->createTextField('article', $field_name);

    // Visit the field configuration form.
    $this->drupalGet("admin/structure/types/manage/article/fields/node.article.{$field_name}");
    $assert = $this->assertSession();

    // Verify the page loads successfully.
    $assert->statusCodeEquals(200);

    // Verify datalayer form elements are present.
    $assert->pageTextContains('Expose field data in JavaScript');
    $assert->pageTextContains('dataLayer');
    $assert->fieldExists('third_party_settings[datalayer][expose]');
    $assert->fieldExists('third_party_settings[datalayer][label]');

    // Verify the checkbox is unchecked by default.
    $assert->checkboxNotChecked('third_party_settings[datalayer][expose]');

    // Verify the label field has the field name as default value.
    $assert->fieldValueEquals('third_party_settings[datalayer][label]', $field_name);
  }

  /**
   * Tests form elements don't appear when output_fields is disabled.
   */
  public function testDatalayerFormElementsWithOutputFieldsDisabled() {
    // Disable output_fields setting.
    $this->config('datalayer.settings')
      ->set('output_fields', FALSE)
      ->save();

    // Create a test field.
    $field_name = 'field_test_text_2';
    $this->createTextField('article', $field_name);

    // Visit the field configuration form.
    $this->drupalGet("admin/structure/types/manage/article/fields/node.article.{$field_name}");
    $assert = $this->assertSession();

    // Verify the page loads successfully.
    $assert->statusCodeEquals(200);

    // Verify datalayer form elements are NOT present.
    $assert->fieldNotExists('third_party_settings[datalayer][expose]');
    $assert->fieldNotExists('third_party_settings[datalayer][label]');
  }

  /**
   * Tests that field can be added without any issues when datalayer is enabled.
   */
  public function testFieldCreationDoesNotBreak() {
    // Enable output_fields setting.
    $this->config('datalayer.settings')
      ->set('output_fields', TRUE)
      ->save();

    $label = 'Test New Field';
    $field_name = 'test_new_field';

    // Use the FieldUiTestTrait method to add a new field with custom settings.
    $field_edit = [
      'third_party_settings[datalayer][expose]' => 1,
      'third_party_settings[datalayer][label]' => 'newFieldLabel',
    ];

    $this->fieldUIAddNewField(
      'admin/structure/types/manage/article',
      $field_name,
      $label,
      'string',
      [],
      $field_edit
    );

    // Verify field was created successfully with datalayer settings.
    $field = FieldConfig::loadByName('node', 'article', 'field_' . $field_name);
    $this->assertNotNull($field, 'Field was created successfully.');
    $this->assertEquals(1, $field->getThirdPartySetting('datalayer', 'expose'));
    $this->assertEquals('newFieldLabel', $field->getThirdPartySetting('datalayer', 'label'));
  }

  /**
   * Helper function to create a text field.
   *
   * @param string $bundle
   *   The bundle to add the field to.
   * @param string $field_name
   *   The field name.
   */
  protected function createTextField($bundle, $field_name) {
    // Create field storage.
    FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'node',
      'type' => 'string',
    ])->save();

    // Create field instance.
    FieldConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'node',
      'bundle' => $bundle,
      'label' => 'Test field',
    ])->save();
  }

}
