<?php

namespace Drupal\Tests\layout_paragraphs\FunctionalJavascript;

/**
 * Tests adding a new layout section to layout paragraphs.
 *
 * @group layout_paragraphs
 */
class ValidationConstraintTest extends BuilderTestBase {

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
    'layout_paragraphs_entity_validator_test',
  ];

  /**
   * Tests that error message appears when paragraph fails validation.
   */
  public function testConstraintValidation() {
    $this->loginWithPermissions($this->contentPermissions);

    $this->drupalGet('node/add/page');
    $page = $this->getSession()->getPage();
    $css_selector = '.lpb-btn--add';

    $button = $page->find('css', $css_selector);
    $button->click();
    $this->assertSession()->assertWaitOnAjaxRequest();

    $title = $page->find('css', '.ui-dialog-title');
    if ($title->getText() == 'Choose a component') {
      $page->clickLink('text');
      $this->assertSession()->assertWaitOnAjaxRequest();
    }
    $this->assertSession()->pageTextContains('field_text');

    $page->fillField('field_text[0][value]', 'Test text');
    // Force show the hidden submit button so we can click it.
    $this->getSession()->executeScript("jQuery('.lpb-btn--save').attr('style', '');");
    $button = $this->assertSession()->waitForElementVisible('css', ".lpb-btn--save");
    $button->press();

    $this->assertSession()->assertWaitOnAjaxRequest();
    // Assert the fail message exists.
    $this->assertSession()->pageTextContains('Failed Layout Paragraphs test validation.');
    // Asser the form is still present and hasn't been closed.
    $this->assertSession()->elementExists('css', 'form.layout-paragraphs-component-form');
    $this->htmlOutput($this->getSession()->getPage()->getHtml());

  }

}
