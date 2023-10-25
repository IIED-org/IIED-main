<?php

namespace Drupal\Tests\layout_paragraphs\FunctionalJavascript;

/**
 * Tests that the layout is available in preprocess variables.
 *
 * @see https://www.drupal.org/project/layout_paragraphs/issues/3296245
 * @group layout_paragraphs
 */
class PreprocessLayoutTest extends BuilderTestBase {

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
    'layout_paragraphs_preprocess_layout_test',
  ];

  /**
   * Tests adding a section component to a new page.
   *
   * The first region should contain the bundle name of the paragraph section,
   * injected by the preprocess hook in
   * layout_paragraphs_preprocess_layout_test.module.
   */
  public function testAddSection() {
    $this->loginWithPermissions([
      'create page content',
      'edit own page content',
    ]);

    $this->drupalGet('node/add/page');
    $page = $this->getSession()->getPage();

    $this->addSectionComponent(2, '.lpb-btn--add');

    // Assert that three columns now exist.
    $first_col = $page->find('css', '.layout__region--first');
    $this->assertNotEmpty($first_col);
    $second_col = $page->find('css', '.layout__region--second');
    $this->assertNotEmpty($second_col);
    $third_col = $page->find('css', '.layout__region--third');
    $this->assertNotEmpty($third_col);
    $this->submitForm([
      'title[0][value]' => 'Node title',
    ], 'Save');
    $this->assertSession()->pageTextContains('bundle:section');

  }

}
