<?php

namespace Drupal\Tests\layout_paragraphs\FunctionalJavascript;

use Drupal\Core\Entity\Entity\EntityFormDisplay;

/**
 * Tests nested sections including the node preview screen.
 *
 * @group layout_paragraphs
 */
class NestedSectionsTest extends BuilderTestBase {

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Allow nesting sections.
    $entity_form_display = EntityFormDisplay::load('node.page.default');
    $component = $entity_form_display->getComponent('field_content');
    $component['settings']['nesting_depth'] = 1;
    $entity_form_display
      ->setComponent('field_content', $component)
      ->save();
  }

  /**
   * Tests nested sections.
   */
  public function testNestedSections() {

    $this->loginWithPermissions([
      'create page content',
      'edit own page content',
    ]);

    $this->drupalGet('node/add/page');
    $page = $this->getSession()->getPage();

    // Add a two-column section.
    $this->addSectionComponent(1, '.lpb-btn--add');
    // Add a one-column section in region 1.
    $this->addSectionComponent(0, '.layout__region--first .lpb-btn--add');
    // Add a three-column section in region 2.
    $this->addSectionComponent(2, '.layout__region--second .lpb-btn--add');

    // Add a text component in each nested section.
    $this->addTextComponent('First', '.layout__region--first .layout__region--content .lpb-btn--add');
    $this->addTextComponent('Second', '.layout__region--second .layout__region--first .lpb-btn--add');
    $this->addTextComponent('Third', '.layout__region--second .layout__region--second .lpb-btn--add');
    $this->addTextComponent('Fourth', '.layout__region--second .layout__region--third .lpb-btn--add');

    // Preview the node.
    $this->submitForm([
      'title[0][value]' => 'Node title',
    ], 'Preview');

    // Check for all the added components.
    $this->assertSession()->pageTextContains('First');
    $this->assertSession()->pageTextContains('Second');
    $this->assertSession()->pageTextContains('Third');
    $this->assertSession()->pageTextContains('Fourth');

    // Back to editing.
    $this->clickLink('Back to content editing');

    // Check for all the added components still on edit tab.
    $this->assertSession()->pageTextContains('First');
    $this->assertSession()->pageTextContains('Second');
    $this->assertSession()->pageTextContains('Third');
    $this->assertSession()->pageTextContains('Fourth');

    // Save the node.
    $this->submitForm([
      'title[0][value]' => 'Node title',
    ], 'Save');

    // Check for all the added components still on view tab.
    $this->assertSession()->pageTextContains('First');
    $this->assertSession()->pageTextContains('Second');
    $this->assertSession()->pageTextContains('Third');
    $this->assertSession()->pageTextContains('Fourth');
  }

}
