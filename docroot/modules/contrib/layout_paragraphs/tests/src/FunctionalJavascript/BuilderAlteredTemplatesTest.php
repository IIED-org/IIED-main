<?php

namespace Drupal\Tests\layout_paragraphs\FunctionalJavascript;

/**
 * Tests Layout Paragraphs Builder with altered paragraphs templates.
 *
 * This test uses the display suite module which alters the variables passed
 * to the paragraph template, effectively testing the UI for cases where a
 * paragraph template has been adjusted and no longer renders the entire
 * content array.
 *
 * Tests are identical to BuilderTest EXCEPT that the text paragraph template
 * has been altered to only output a single field. If items can be added
 * and reordered, the test is successful.
 *
 * @see https://www.drupal.org/project/layout_paragraphs/issues/3244055
 * @see https://www.drupal.org/project/layout_paragraphs/issues/3244654
 *
 * @requires module layout_paragraphs_altered_template_test
 *
 * @group layout_paragraphs
 */
class BuilderAlteredTemplatesTest extends BuilderTest {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'layout_paragraphs_altered_template_test',
    'paragraphs',
    'node',
    'field',
    'field_ui',
    'block',
    'paragraphs_test',
  ];

  /**
   * Runs BuilderTest:testReorderComponents, asserting templates are customized.
   */
  public function testReorderComponents() {
    parent::testReorderComponents();
    $this->assertSession()->pageTextContains('Custom template rendering text paragraph type.');
  }

}
