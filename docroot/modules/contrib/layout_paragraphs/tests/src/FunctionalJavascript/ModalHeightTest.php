<?php

namespace Drupal\Tests\layout_paragraphs\FunctionalJavascript;

/**
 * Tests that buttons remain reachable even with tall modal heights.
 *
 * @group layout_paragraphs
 */
class ModalHeightTest extends BuilderTestBase {

  /**
   * Tests that buttons remain reachable even with tall modal heights.
   */
  public function testModalHeight() {
    $this->loginWithPermissions([
      'create page content',
      'edit own page content',
    ]);
    $this->drupalGet('node/add/page');
    $page = $this->getSession()->getPage();

    // Click the Add Component button.
    $page->find('css', '.lpb-btn--add')->click();
    $this->assertSession()->assertWaitOnAjaxRequest(1000, 'Unable to click add a component.');

    // Open the "Create text" dialog.
    $page->clickLink('text');
    $this->assertSession()->waitForText('Create text');

    // Force the dialog height to expand beyond the viewport.
    // Since the dialog height is 'auto' by default, increasing the height of
    // the dialog's content will automatically increase the dialog's height.
    $this->getSession()->executeScript('jQuery(\'.layout-paragraphs-component-form\').height(\'2000px\');');
    // Pause for UI to update.
    $this->getSession()->wait(1000);

    // Save button should still be reachable.
    $this->assertSession()->assertVisibleInViewport('css', '.ui-dialog-buttonpane .lpb-btn--save');

  }

}
