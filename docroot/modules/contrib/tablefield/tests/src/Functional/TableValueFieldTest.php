<?php

namespace Drupal\Tests\tablefield\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Simple test to ensure that a field can be created.
 *
 * @group tablefield
 */
class TableValueFieldTest extends BrowserTestBase {

  use TablefieldCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node', 'tablefield'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->drupalLogin($this->rootUser);

    $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);
    $this->createTableField('field_table', 'article');
  }

  /**
   * Create a node with a tablefield, and ensure it's displayed correctly.
   */
  public function testTableField() {
    $this->drupalGet('node/add/article');

    // Create a node.
    $edit = [];
    $edit['title[0][value]'] = 'Llamas are cool';
    $edit['body[0][value]'] = 'Llamas are very cool';
    $edit['field_table[0][caption]'] = 'Table caption';
    $edit['field_table[0][tablefield][table][0][0]'] = 'Header 1';
    $edit['field_table[0][tablefield][table][0][1]'] = 'Header 2';
    $edit['field_table[0][tablefield][table][0][2]'] = 'Header 3';
    $edit['field_table[0][tablefield][table][1][0]'] = 'Row 1-1';
    $edit['field_table[0][tablefield][table][1][1]'] = 'Row 1-2';
    $edit['field_table[0][tablefield][table][1][2]'] = 'Row 1-3';
    $edit['field_table[0][tablefield][table][2][0]'] = 'Row 2-1';
    $edit['field_table[0][tablefield][table][2][1]'] = 'Row 2-2';
    $edit['field_table[0][tablefield][table][2][2]'] = 'Row 2-3';

    $this->submitForm($edit, t('Save'));
    $assert_session = $this->assertSession();
    $assert_session->pageTextContains('Article Llamas are cool has been created.');

    $assert_session->elementContains('css', 'table#tablefield-node-1-field_table-0 caption', 'Table caption');

    $assert_session->elementContains('css', 'table#tablefield-node-1-field_table-0 thead th.row_0.col_0', 'Header 1');
    $assert_session->elementContains('css', 'table#tablefield-node-1-field_table-0 thead th.row_0.col_1', 'Header 2');
    $assert_session->elementContains('css', 'table#tablefield-node-1-field_table-0 thead th.row_0.col_2', 'Header 3');

    $assert_session->elementContains('css', 'table#tablefield-node-1-field_table-0 tbody tr td.row_1.col_0', 'Row 1-1');
    $assert_session->elementContains('css', 'table#tablefield-node-1-field_table-0 tbody tr td.row_1.col_1', 'Row 1-2');
    $assert_session->elementContains('css', 'table#tablefield-node-1-field_table-0 tbody tr td.row_1.col_2', 'Row 1-3');

    $assert_session->elementContains('css', 'table#tablefield-node-1-field_table-0 tbody tr td.row_2.col_0', 'Row 2-1');
    $assert_session->elementContains('css', 'table#tablefield-node-1-field_table-0 tbody tr td.row_2.col_1', 'Row 2-2');
    $assert_session->elementContains('css', 'table#tablefield-node-1-field_table-0 tbody tr td.row_2.col_2', 'Row 2-3');
  }

}
