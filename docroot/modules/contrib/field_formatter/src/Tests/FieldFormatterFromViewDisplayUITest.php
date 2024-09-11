<?php

namespace Drupal\field_formatter\Tests;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Tests\BrowserTestBase;

/**
 * Ensures that field_formatter UI work correctly.
 *
 * @group field_formatter
 */
class FieldFormatterFromViewDisplayUITest extends BrowserTestBase {
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
    ]);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests a field_formatter from view display.
   */
  public function testFieldFormatterFromViewDisplay() {
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
      'field_field_test_ref[0][target_id]' => $term_name,
    ];
    $this->submitForm($edit_content, $this->t('Save'));
    $this->assertSession()->responseContains('<div class="field__label">test_field</div>');
    $this->assertSession()->responseContains('<div class="field__item">' . $field . '</div>');
  }

  /**
   * Tests a field_formatter from view config form.
   */
  public function testFieldFormatterFromViewConfigForm() {
    $account = $this->drupalCreateUser(['administer node display']);
    $this->drupalLogin($account);

    $this->drupalGet('admin/structure/types/manage/test_content_type/display');
    $this->submitForm([], 'field_field_test_ref_settings_edit');
    $this->assertSession()->fieldExists('fields[field_field_test_ref][settings_edit_form][settings][view_mode]');
    $this->assertSession()->responseContains('<option value="default">Default</option>');
    $this->assertSession()->fieldExists('fields[field_field_test_ref][settings_edit_form][settings][field_name]');
  }

}
