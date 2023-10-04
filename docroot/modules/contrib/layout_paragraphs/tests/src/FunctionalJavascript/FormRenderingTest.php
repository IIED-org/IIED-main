<?php

namespace Drupal\Tests\layout_paragraphs\FunctionalJavascript;

/**
 * Tests forms rendered in paragraphs in the Layout Paragraphs Builder.
 *
 * @see https://www.drupal.org/project/layout_paragraphs/issues/3263715
 * @group layout_paragraphs
 */
class FormRenderingTest extends BuilderTestBase {

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
    'layout_paragraphs_form_rendering_test',
  ];

  /**
   * Tests rendering forms within a Layout Paragraphs Builder instance.
   */
  public function testFormRendering() {
    $this->loginWithPermissions([
      'create page content',
      'edit own page content',
    ]);

    $this->drupalGet('node/add/page');
    $page = $this->getSession()->getPage();

    $this->addSectionComponent(0, '.lpb-btn--add');
    // Make sure the "Test field" form element appears.
    $this->assertSession()->pageTextContains('Test field');
    // Save the node.
    $this->submitForm([
      'title[0][value]' => 'Node title',
    ], 'Save');
    // Make sure the node appears correctly.
    $this->assertSession()->pageTextContains('Node title');
    $this->assertSession()->pageTextContains('Test field');
  }

}
