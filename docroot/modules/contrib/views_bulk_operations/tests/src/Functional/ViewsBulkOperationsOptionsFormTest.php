<?php

declare(strict_types=1);

namespace Drupal\Tests\views_bulk_operations\Functional;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\views_bulk_operations\WatchdogTestTrait;
use Drupal\views_bulk_operations\Plugin\views\field\ViewsBulkOperationsBulkForm;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the VBO field handler options form in the Views UI.
 *
 * Smoke test for the defineOptions(), buildOptionsForm() and
 * submitOptionsForm() methods that the bulk form plugin receives via
 * \Drupal\views_bulk_operations\Traits\ViewsBulkOperationsOptionsFormTrait.
 */
#[CoversClass(ViewsBulkOperationsBulkForm::class)]
#[Group('views_bulk_operations')]
final class ViewsBulkOperationsOptionsFormTest extends BrowserTestBase {

  use WatchdogTestTrait;

  private const TEST_VIEW_ID = 'views_bulk_operations_test';

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'dblog',
    'node',
    'views',
    'views_ui',
    'views_bulk_operations',
    'views_bulk_operations_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->drupalLogin($this->drupalCreateUser(['administer views']));
  }

  /**
   * Tests rendering and submitting the VBO field options form.
   *
   * Both steps share one test method so the site is only installed once.
   */
  public function testOptionsForm(): void {
    $assert = $this->assertSession();

    // Render: options declared in defineOptions() and built by
    // buildOptionsForm().
    $this->drupalGet(Url::fromRoute('views_ui.form_handler', [
      'js' => 'nojs',
      'view' => self::TEST_VIEW_ID,
      'display_id' => 'default',
      'type' => 'field',
      'id' => 'views_bulk_operations_bulk_form',
    ]));

    $assert->statusCodeEquals(200);
    $assert->fieldExists('options[batch]');
    $assert->fieldExists('options[batch_size]');
    $assert->fieldExists('options[ajax_loader]');
    $assert->fieldExists('options[buttons]');
    $assert->fieldExists('options[clear_on_exposed]');
    $assert->fieldExists('options[action_title]');
    $assert->fieldExists('options[show_multipage_selection_box]');
    $assert->fieldExists('options[show_select_all]');

    // The selectable actions table lists the available actions, each with a
    // state checkbox - this is the actions loop in buildOptionsForm().
    $assert->elementExists('css', '.vbo-actions-widget');
    $assert->pageTextContains('VBO simple test action');
    $assert->fieldExists('options[selected_actions][table][0][container][state]');

    // Submit: apply the rendered form unchanged, then save the view. Applying
    // runs submitOptionsForm(), which filters out unchecked actions and
    // flattens each remaining one (dropping 'state', 'container' and 'weight').
    $this->submitForm([], 'Apply');
    $this->submitForm([], 'Save');

    $selected_actions = $this->config('views.view.' . self::TEST_VIEW_ID)
      ->get('display.default.display_options.fields.views_bulk_operations_bulk_form.selected_actions');

    // The three actions preconfigured on the test view survive; the other
    // available (unchecked) actions are filtered out.
    self::assertEqualsCanonicalizing([
      'views_bulk_operations_simple_test_action',
      'views_bulk_operations_advanced_test_action',
      'views_bulk_operations_test_null_type',
    ], \array_column($selected_actions, 'action_id'));

    // Each surviving entry is flattened by submitOptionsForm().
    foreach ($selected_actions as $action) {
      self::assertArrayHasKey('action_id', $action);
      self::assertArrayNotHasKey('state', $action);
      self::assertArrayNotHasKey('container', $action);
      self::assertArrayNotHasKey('weight', $action);
    }
  }

}
