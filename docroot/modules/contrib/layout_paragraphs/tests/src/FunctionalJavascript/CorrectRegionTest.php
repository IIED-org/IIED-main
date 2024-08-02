<?php

namespace Drupal\Tests\layout_paragraphs\FunctionalJavascript;

/**
 * Tests moving a component between regions before adding a new component.
 *
 * @see https://www.drupal.org/project/layout_paragraphs/issues/3281169
 * @group layout_paragraphs
 */
class CorrectRegionTest extends BuilderTestBase {

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
    'layout_paragraphs_correct_region_test',
  ];

  /**
   * Tests keyboard navigation.
   */
  public function testKeyboardNavigation() {

    $this->loginWithPermissions([
      'create page content',
      'edit own page content',
    ]);

    $this->drupalGet('node/add/page');
    $page = $this->getSession()->getPage();

    $this->addSectionComponent(2, '.lpb-btn--add');
    $this->addTextComponent('First item', '.layout__region--first .lpb-btn--add');
    $this->addTextComponent('Second item', '.layout__region--second .lpb-btn--add');
    $this->addTextComponent('Third item', '.layout__region--third .lpb-btn--add');

    // Click the new item's drag button.
    // This should create a <div> with the id 'lpb-navigatin-msg'.
    $button = $page->find('css', '.layout__region--third .lpb-drag');
    $button->click();

    // Moves third item to bottom of second region.
    $this->keyPress('ArrowUp');
    $this->assertSession()->assertWaitOnAjaxRequest();
    // Moves third item to top of second region.
    $this->keyPress('ArrowUp');
    $this->assertSession()->assertWaitOnAjaxRequest();
    // Moves third item to bottom of first region.
    $this->keyPress('ArrowUp');
    $this->assertSession()->assertWaitOnAjaxRequest();
    // Moves third item to top of first region.
    $this->keyPress('ArrowUp');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->keyPress('Enter');
//    $this->assertSession()->assertWaitOnAjaxRequest();

    // Add a fourth item above the third item, which has been moved to the first
    // region. Ensure the fourth item is correctly added to the first region,
    // hiding the controls ui first so it doesn't overlapp the + button.
    // @see https://www.drupal.org/project/layout_paragraphs/issues/3281169
    $this->forceHidden('.layout__region--first .lpb-controls');
    $this->addTextComponent('Fourth item', '.layout__region--first .lpb-btn--add');
    $this->assertOrderOfStrings([
      'Fourth item',
      'Third item',
      'First item',
      'Second item',
    ]);

    // Save the node.
    $this->submitForm([
      'title[0][value]' => 'Node title',
    ], 'Save');

    // Ensures reordering was correctly applied via Ajax.
    $this->assertOrderOfStrings([
      'Fourth item',
      'Third item',
      'First item',
      'Second item',
    ]);
  }

}
