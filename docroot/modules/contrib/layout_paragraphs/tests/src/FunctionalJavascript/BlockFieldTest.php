<?php

namespace Drupal\Tests\layout_paragraphs\FunctionalJavascript;

/**
 * Tests compatibility with the Block Field module.
 *
 * @group layout_paragraphs
 */
class BlockFieldTest extends BuilderTestBase {

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
    'search',
    'block_field',
  ];

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    $this->markTestSkipped('must be revisited.');
    parent::setUp();

    $this->addParagraphsType('block');
    $this->addFieldtoParagraphType('block', 'field_block', 'block_field');
  }

  /**
   * Tests adding a paragraph with a block reference field.
   */
  public function testBlockField() {
    $this->loginWithPermissions([
      'create page content',
      'edit own page content',
      'search content',
    ]);
    $this->drupalGet('node/add/page');
    $page = $this->getSession()->getPage();
    $this->addSectionComponent(2, '.lpb-btn--add');
    $this->addBlockFieldComponent('search_form_block', '.layout__region--first .lpb-btn--add');
    $this->addTextComponent('Some arbitrary text', '.layout__region--first .lpb-btn--add.after');
    $this->submitForm([
      'title[0][value]' => 'Node title',
    ], 'Save');
    $this->assertSession()->pageTextContains('Search form');
    $this->assertSession()->pageTextContains('Some arbitrary text');
    $this->assertOrderOfStrings(['Search form', 'Some arbitrary text']);
  }

  /**
   * Tests block reference field compatibility with the frontend builder.
   */
  public function testBlockFieldFrontEndBuilder() {
    $this->useFrontEndBuilderFormatter('page', 'field_content');
    $this->loginWithPermissions([
      'create page content',
      'edit own page content',
      'search content',
    ]);
    $this->drupalGet('node/add/page');
    $this->submitForm([
      'title[0][value]' => 'Node title',
    ], 'Save');
    $this->enableFrontendBuilder();
    $this->addSectionComponent(2, '.lpb-btn--add');
    $this->addBlockFieldComponent('search_form_block', '.layout__region--first .lpb-btn--add');
    $this->addTextComponent('Some arbitrary text', '.layout__region--first .lpb-btn--add.after');
    $this->saveAndCloseFrontendBuilder();
    $this->drupalGet('node/1');
    $this->assertSession()->pageTextContains('Search form');
    $this->assertSession()->pageTextContains('Some arbitrary text');
    $this->assertOrderOfStrings(['Search form', 'Some arbitrary text']);
  }

  /**
   * Adds a block field component.
   *
   * @param string $block_id
   *   The block id.
   * @param string $css_selector
   *   The css selector for the + button to press.
   */
  protected function addBlockFieldComponent($block_id, $css_selector) {
    $page = $this->getSession()->getPage();
    $button = $page->find('css', $css_selector);
    $button->click();
    $this->assertSession()->assertWaitOnAjaxRequest();

    $title = $page->find('css', '.ui-dialog-title');
    if ($title->getText() == 'Choose a component') {
      $page->clickLink('block');
      $this->assertSession()->assertWaitOnAjaxRequest();
    }
    $this->assertSession()->pageTextContains('field_block');
    $page->fillField('field_block[0][plugin_id]', $block_id);
    $this->assertSession()->assertWaitOnAjaxRequest(1000);

    // Force show the hidden submit button so we can click it.
    $this->getSession()->executeScript("jQuery('.lpb-btn--save').attr('style', '');");
    $button = $this->assertSession()->waitForElementVisible('css', ".lpb-btn--save");
    $button->press();

    $this->assertSession()->assertWaitOnAjaxRequest();
  }

}
