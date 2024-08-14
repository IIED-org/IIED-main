<?php

namespace Drupal\Tests\layout_paragraphs\FunctionalJavascript;

/**
 * Tests reordering Layout Paragraphs fields under manage display tab.
 *
 * @group layout_paragraphs
 */
class ReorderFormElementsTest extends BuilderTestBase {

  /**
   * Tests moving the "Published" flag above/below LP fields.
   */
  public function testReorderingFields() {

    // Drag published checkbox above layout paragraphs fields.
    $this->loginWithPermissions([
      'administer site configuration',
      'administer node fields',
      'administer node display',
      'administer paragraphs types',
      'administer paragraph form display',
    ]);
    $this->drupalGet('admin/structure/paragraphs_type/section/form-display');
    $page = $this->getSession()->getPage();
//    $published_field = $page->find('xpath', '//tr[@id="status"]//a[@class="tabledrag-handle"]');
//    $lp_fields = $page->find('xpath', '//tr[@id="layout-paragraphs-fields"]');
//    $published_field->dragTo($lp_fields);
    $published_field = $page->find('css', 'tr#status a.tabledrag-handle');
    $published_field->dragTo($page->find('css', 'tr#layout-paragraphs-fields td'));
    $this->submitForm([], 'Save');
    $this->drupalLogout();

    // Test correct order when adding a section.
    $this->loginWithPermissions([
      'create page content',
      'edit own page content',
    ]);
    $this->drupalGet('node/add/page');
    $page = $this->getSession()->getPage();
    $page->find('css', '.lpb-btn--add')->click();
    $this->assertSession()->assertWaitOnAjaxRequest(1000, 'Unable to click add a component.');
    $page->find('css', '.type-section a')->click();
    $this->assertSession()->assertWaitOnAjaxRequest(1000, 'Unable to click section.');
    $this->assertOrderOfStrings(['Published', 'Choose a layout'], 'Publish checkbox is incorrectly placed below layout options.');
    $this->drupalLogout();

    // Drag the layout paragraphs fields above the published checkbox.
    $this->loginWithPermissions([
      'administer site configuration',
      'administer node fields',
      'administer node display',
      'administer paragraphs types',
      'administer paragraph form display',
    ]);
    $this->drupalGet('admin/structure/paragraphs_type/section/form-display');
    $page = $this->getSession()->getPage();
//    $published_field = $page->find('xpath', '//tr[@id="status"]');
//    $lp_fields = $page->find('xpath', '//tr[@id="layout-paragraphs-fields"]//a[@class="tabledrag-handle"]');
//    $lp_fields->dragTo($published_field);
    $lp_fields = $page->find('css', 'tr#layout-paragraphs-fields a.tabledrag-handle');
    $lp_fields->dragTo($page->find('css', 'tr#status'));
    $this->submitForm([], 'Save');
    $this->drupalLogout();

    // Test correct order when adding a section.
    $this->loginWithPermissions([
      'create page content',
      'edit own page content',
    ]);
    $this->drupalGet('node/add/page');
    $page = $this->getSession()->getPage();
    $page->find('css', '.lpb-btn--add')->click();
    $this->assertSession()->assertWaitOnAjaxRequest(1000, 'Unable to click add a component.');
    $page->find('css', '.type-section a')->click();
    $this->assertSession()->assertWaitOnAjaxRequest(1000, 'Unable to click section.');
    $this->assertOrderOfStrings(['Choose a layout', 'Published'], 'Publish checkbox is incorrectly placed above layout options.');
    $this->drupalLogout();

  }

}
