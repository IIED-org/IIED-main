<?php

namespace Drupal\Tests\layout_paragraphs\FunctionalJavascript;

use Behat\Mink\Exception\ExpectationException;

/**
 * Tests duplicating components in a Layout Paragraphs Layout.
 *
 * @group layout_paragraphs
 */
class DuplicateComponentsTest extends BuilderTestBase {

  /**
   * Tests duplicating a simple text component inside a section.
   */
  public function testDuplicateComponent() {

    $this->loginWithPermissions([
      'create page content',
      'edit own page content',
    ]);

    $this->drupalGet('node/add/page');
    $page = $this->getSession()->getPage();

    $this->addSectionComponent(0, '.lpb-btn--add');
    $this->addTextComponent('Text component.', '.layout__region .lpb-btn--add');

    $button = $page->find('css', '.layout__region .lpb-duplicate');
    $button->click();
    $this->assertSession()->assertWaitOnAjaxRequest();

    $this->submitForm([
      'title[0][value]' => 'Node title',
    ], 'Save');

    $page_text = $page->getHtml();
    if (\substr_count($page_text, 'Text component.') != 2) {
      throw new ExpectationException('Text component was not duplicated', $this->getSession()->getDriver());
    }
    $this->assertSession()->pageTextContains('Text component.');
  }

  /**
   * Tests adding a section with components and duplicating the section.
   */
  public function testDuplicateSection() {

    $this->loginWithPermissions([
      'create page content',
      'edit own page content',
    ]);

    $this->drupalGet('node/add/page');
    $page = $this->getSession()->getPage();

    $this->addSectionComponent(2, '.lpb-btn--add');
    $this->addTextComponent('Component in first column.', '.layout__region--first .lpb-btn--add');
    $this->addTextComponent('Component in second column.', '.layout__region--second .lpb-btn--add');

    $button = $page->find('css', '.is-layout .lpb-duplicate');
    $button->click();
    $this->assertSession()->assertWaitOnAjaxRequest();

    $this->submitForm([
      'title[0][value]' => 'Node title',
    ], 'Save');

    $page_text = $page->getHtml();
    if (\substr_count($page_text, 'Component in first column.') != 2) {
      throw new ExpectationException('Component in first column was not duplicated', $this->getSession()->getDriver());
    }
    if (\substr_count($page_text, 'Component in second column.') != 2) {
      throw new ExpectationException('Component in second column was not duplicated', $this->getSession()->getDriver());
    }
    $this->assertSession()->pageTextContains('Component in first column.');
    $this->assertSession()->pageTextContains('Component in second column.');

  }

}
