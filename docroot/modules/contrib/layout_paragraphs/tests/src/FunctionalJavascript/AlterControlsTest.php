<?php

namespace Drupal\Tests\layout_paragraphs\FunctionalJavascript;

use Behat\Mink\Exception\ExpectationException;

/**
 * Tests the ability to alter the controls ui element.
 *
 * @requires module layout_paragraphs_alter_controls_test
 *
 * @group layout_paragraphs
 */
class AlterControlsTest extends BuilderTestBase {

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
    'layout_paragraphs_alter_controls_test',
  ];

  /**
   * Tests that controls have been altered.
   */
  public function testAlterControls() {
    $this->loginWithPermissions([
      'create page content',
      'edit own page content',
    ]);

    $this->drupalGet('node/add/page');
    $this->addSectionComponent(2, '.lpb-btn--add');

    $page = $this->getSession()->getPage();
    $this->forceVisible('.lpb-controls');

    $this->assertSession()->elementExists('css', 'a.lpb-drag');
    $this->assertSession()->elementExists('css', '.lpb-alter-controls-test-element');

    $this->assertSession()->elementNotExists('css', 'a.lpb-edit');
    $this->assertSession()->elementNotExists('css', 'a.lpb-delete');

    if (empty($page->find('css', 'span.lpb-alter-controls-test-element')->getText())) {
      throw new ExpectationException('Text element is missing.', $this->getSession()->getDriver());
    }
  }

}
