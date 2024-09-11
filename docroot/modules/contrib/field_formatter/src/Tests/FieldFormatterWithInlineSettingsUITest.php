<?php

namespace Drupal\field_formatter\Tests;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Tests\BrowserTestBase;

/**
 * Ensures that field_formatter UI works correctly.
 *
 * @group field_formatter
 */
class FieldFormatterWithInlineSettingsUITest extends BrowserTestBase {
  use StringTranslationTrait;

  /**
   * The test user.
   *
   * @var \Drupal\User\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field_formatter_test',
    'field_ui',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'starterkit_theme';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->adminUser = $this->drupalCreateUser([
      'administer taxonomy',
      'bypass node access',
      'administer node display',
      'administer node fields',
    ]);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests a field formatter with inline settings.
   */
  public function testFieldFormatterWithInlineSettings() {
    // Add term.
    $this->drupalGet('admin/structure/taxonomy/manage/test_vocabulary/add');
    $term_name = strtolower($this->randomMachineName());
    $field = strtolower($this->randomMachineName());
    $edit_term = [
      'name[0][value]' => $term_name,
      'field_test_field[0][value]' => $field,
    ];
    $this->submitForm($edit_term, $this->t('Save'));
    $this->assertSession()->pageTextContains("Created new term $term_name.");

    // Add content.
    $this->drupalGet('node/add/test_content_type');
    $content_name = strtolower($this->randomMachineName());
    $edit_content = [
      'title[0][value]' => $content_name,
      'field_field_test_ref_inline[0][target_id]' => $term_name,
    ];
    $this->submitForm($edit_content, $this->t('Save'));
    $this->assertSession()->responseContains('<div class="field__label">test_field</div>');
    $this->assertSession()->responseContains('<div class="field__item">' . $field . '</div>');

    // Check that on display management all fields of the destination entity
    // are available (all bundles).
    $this->drupalGet('admin/structure/types/manage/test_content_type/display');
    // Open the formatter settings.
    $this->submitForm([], 'field_field_test_ref_inline_settings_edit');
    $this->assertSession()->fieldExists('fields[field_field_test_ref_inline][settings_edit_form][settings][field_name]');
    $field_select_element = $this->xpath('//*[@name="fields[field_field_test_ref_inline][settings_edit_form][settings][field_name]"]');
    $field_select_id = $field_select_element[0]->getAttribute('id');
    $this->assertSession()->optionExists($field_select_id, 'field_test_field');
    $this->assertSession()->optionExists($field_select_id, 'field_test_field2');
    $this->assertSession()->fieldValueEquals('fields[field_field_test_ref_inline][settings_edit_form][settings][label]', 'above');

  }

}
