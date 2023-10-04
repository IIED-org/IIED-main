<?php

namespace Drupal\Tests\layout_paragraphs\FunctionalJavascript;

/**
 * Tests layout paragraphs permissions.
 *
 * @group layout_paragraphs
 * @requires module entity_usage
 * @requires module paragraphs_library
 */
class LayoutParagraphsLibraryTest extends BuilderTestBase {

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
    'entity_usage',
    'paragraphs_library',
    'layout_paragraphs_library',
  ];

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->loginWithPermissions([
      'administer paragraphs types',
    ]);
    $this->drupalGet('admin/structure/paragraphs_type/text');
    $this->submitForm([
      'allow_library_conversion' => TRUE,
    ], 'Save');
    $this->drupalLogout();
  }

  /**
   * Tests the "Promote to library" button exists.
   */
  public function testPromoteToLibrary() {

    $this->loginWithPermissions([
      'create page content',
      'edit own page content',
    ]);

    $this->drupalGet('node/add/page');
    $page = $this->getSession()->getPage();

    // Click the Add Component button.
    $page->find('css', '.lpb-btn--add')->click();
    $this->assertSession()->waitForText('Choose a component');

    // Open the "Create section" dialog.
    $page->clickLink('section');
    $this->assertSession()->waitForText('Create section');

    // Section paragraphs should not include the "Promote to library" button.
    $this->assertSession()->elementNotExists('css', '.lpb-btn--promote-to-library');
    $page->find('css', '.ui-dialog-buttonpane .lpb-btn--cancel')->click();
    $this->assertSession()->assertWaitOnAjaxRequest();

    // Click the Add Component button.
    $page->find('css', '.lpb-btn--add')->click();
    $this->assertSession()->waitForText('Choose a component');

    // Open the "Create text" dialog.
    $page->clickLink('text');
    $this->assertSession()->waitForText('Create text');

    // Text paragraphs should include the "Promote to library" button.
    $this->assertSession()->elementExists('css', '.lpb-btn--promote-to-library');

    // Promote the new text item to the library.
    $page->fillField('field_text[0][value]', 'Library item');
    $page->find('css', '.ui-dialog-buttonpane .lpb-btn--promote-to-library')->click();
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->pageTextContains('Library item');

    // Save the node.
    $this->submitForm([
      'title[0][value]' => 'Node title',
    ], 'Save');

    // Create a new page.
    $this->drupalGet('node/add/page');
    $page = $this->getSession()->getPage();

    // Open the "Choose a component" dialog and select "From library".
    $page->find('css', '.lpb-btn--add')->click();
    $this->assertSession()->waitForText('Choose a component');
    $page->clickLink('From library');
    $this->assertSession()->waitForText('Create From library');

    // Widget will be an autocomplete by default.
    $page->fillField('field_reusable_paragraph[0][target_id]', 'Text: Library item (1)');

    // Save the library item.
    $page->find('css', '.ui-dialog-buttonpane .lpb-btn--save')->click();
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->pageTextContains('Library item');
  }

}
