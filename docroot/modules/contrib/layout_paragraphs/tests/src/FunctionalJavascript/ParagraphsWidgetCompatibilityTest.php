<?php

namespace Drupal\Tests\layout_paragraphs\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\paragraphs\FunctionalJavascript\ParagraphsTestBaseTrait;

/**
 * Tests basic compatibility with default paragraphs form widget.
 *
 * @group layout_paragraphs
 */
class ParagraphsWidgetCompatibilityTest extends WebDriverTestBase {

  use ParagraphsTestBaseTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'layout_paragraphs',
    'paragraphs',
    'node',
    'field',
    'field_ui',
    'block',
    'paragraphs_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'claro';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->addParagraphsType('section');
    $this->addFieldtoParagraphType('section', 'field_text', 'text');

    $user = $this->drupalCreateUser([
      'administer site configuration',
      'administer node fields',
      'administer node display',
      'administer node form display',
      'administer paragraphs types',
    ]);
    $this->drupalLogin($user);

    // Enable Layout Paragraphs behavior for section paragraph type.
    $this->drupalGet('admin/structure/paragraphs_type/section');
    $this->submitForm([
      'behavior_plugins[layout_paragraphs][enabled]' => TRUE,
      'behavior_plugins[layout_paragraphs][settings][available_layouts][]' => [
        'layout_onecol',
        'layout_twocol',
        'layout_threecol_25_50_25',
        'layout_threecol_33_34_33',
      ],
    ], 'Save');
    $this->assertSession()->pageTextContains('Saved the section Paragraphs type.');
    $this->drupalGet('admin/structure/paragraphs_type/section');
    $this->addParagraphedContentType('page', 'field_content');
    $this->drupalGet('admin/structure/types/manage/page/fields/node.page.field_content');
    $this->submitForm([
      'settings[handler_settings][negate]' => '1',
    ], 'Save settings');
    $this->drupalGet('admin/structure/types/manage/page/form-display');
    $page = $this->getSession()->getPage();
    $btn = $page->find('css', '#edit-fields-field-content-settings-edit');
    $btn->click();
    $this->assertSession()->assertWaitOnAjaxRequest();
    $page->selectFieldOption('fields[field_content][settings_edit_form][settings][default_paragraph_type]', 'section');
    $this->submitForm([], 'Save');
    $this->drupalLogout();
  }

  /**
   * Tests that adding a new page works without errors.
   */
  public function testAddPage() {
    $user = $this->drupalCreateUser([
      'create page content',
      'edit own page content',
      'edit behavior plugin settings',
    ]);
    $this->drupalLogin($user);
    $this->drupalGet('node/add/page');
    $this->submitForm([
      'title[0][value]' => 'Node title',
    ], 'Save');
    $this->assertSession()->pageTextNotContains('The website encountered an unexpected error. Please try again later.');

  }

}
