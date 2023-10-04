<?php

namespace Drupal\Tests\layout_paragraphs\FunctionalJavascript;

/**
 * Tests layout paragraphs permissions.
 *
 * @requires module layout_paragraphs_permissions
 * @requires module layout_paragraphs_complex_permissions_test
 *
 * @group layout_paragraphs
 */
class ComplexPermissionsTest extends LayoutParagraphsPermissionsTest {

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
    'layout_paragraphs_complex_permissions_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setup();
    $this->loginWithPermissions([
      'administer site configuration',
      'administer node fields',
      'administer node display',
      'administer paragraphs types',
    ]);
    $this->addLayoutParagraphedContentType('article', 'field_article_content');
  }

  /**
   * Checks permission per content type.
   */
  public function testPermissionsByContentType() {

    $this->loginWithPermissions([
      'create page content',
      'edit own page content',
      'create article content',
      'edit own article content',
      'reorder layout paragraph components for page content',
    ]);

    $this->drupalGet('node/add/page');
    $this->addSectionComponent(2, '.lpb-btn--add');
    $this->submitForm([
      'title[0][value]' => 'Node title',
    ], 'Save');
    $this->drupalGet('node/1/edit');
    $this->assertCanReorder();

    $this->drupalGet('node/add/article');
    $this->addSectionComponent(2, '.lpb-btn--add');
    $this->submitForm([
      'title[0][value]' => 'Node title',
    ], 'Save');
    $this->drupalGet('node/2/edit');
    $this->assertCannotReorder();

  }

}
