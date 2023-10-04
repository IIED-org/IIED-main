<?php

namespace Drupal\Tests\layout_paragraphs\FunctionalJavascript;

/**
 * Tests layout paragraphs permissions.
 *
 * @requires module layout_paragraphs_permissions
 *
 * @group layout_paragraphs
 */
class LayoutParagraphsPermissionsTest extends BuilderTestBase {

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
    'layout_paragraphs_permissions',
  ];

  /**
   * Tests adding a section WITHOUT reorder permissions.
   */
  public function testWithoutReorderPermission() {
    $this->loginWithPermissions([
      'create page content',
      'edit own page content',
    ]);

    $this->drupalGet('node/add/page');
    $this->addSectionComponent(2, '.lpb-btn--add');
    $this->submitForm([
      'title[0][value]' => 'Node title',
    ], 'Save');
    $this->drupalGet('node/1/edit');
    $this->assertCannotReorder();
  }

  /**
   * Tests adding a section WITH reorder permissions.
   */
  public function testWithReorderPermission() {
    $this->loginWithPermissions([
      'create page content',
      'edit own page content',
      'reorder layout paragraphs components',
    ]);

    $this->drupalGet('node/add/page');
    $this->addSectionComponent(2, '.lpb-btn--add');
    $this->submitForm([
      'title[0][value]' => 'Node title',
    ], 'Save');

    $this->drupalGet('node/1/edit');
    $this->assertCanReorder();
  }

  /**
   * Asserts that the user is able to reorder components.
   */
  protected function assertCanReorder() {
    $page = $this->getSession()->getPage();
    $builder_container = $page->find('css', '.lp-builder');
    $builder_id = $builder_container->getAttribute('data-lpb-id');
    $this->assertSession()->elementExists('css', '.lpb-drag');
    $this->assertSession()->elementExists('css', '.lpb-up');
    $this->assertSession()->elementExists('css', '.lpb-down');
    $this->drupalGet('layout-paragraphs-builder/' . $builder_id . '/reorder');
    // WebDriverTestBase can't check the status code, so check for element.
    $builder_container = $page->find('css', '.lp-builder');
    $this->assertSession()->elementExists('css', '.lp-builder');
  }

  /**
   * Asserts that the user is not able to reorder components.
   */
  protected function assertCannotReorder() {
    $page = $this->getSession()->getPage();
    $builder_container = $page->find('css', '.lp-builder');
    $builder_id = $builder_container->getAttribute('data-lpb-id');
    $this->assertSession()->elementNotExists('css', '.lpb-drag');
    $this->assertSession()->elementNotExists('css', '.lpb-up');
    $this->assertSession()->elementNotExists('css', '.lpb-down');
    $this->drupalGet('layout-paragraphs-builder/' . $builder_id . '/reorder');
    // WebDriverTestBase can't check the status code, so check page text.
    $this->assertSession()->pageTextContains('You are not authorized to access this page.');
  }

}
