<?php

namespace Drupal\Tests\layout_builder_styles\Functional;

use Drupal\layout_builder\Entity\LayoutBuilderEntityViewDisplay;
use Drupal\node\Entity\Node;
use Drupal\Tests\BrowserTestBase;
use Drupal\layout_builder_styles\Entity\LayoutBuilderStyle;

/**
 * Tests the Layout Builder Styles apply as expected.
 *
 * @group layout_builder_styles
 */
class LayoutBuilderStyleTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'layout_builder',
    'block',
    'block_content',
    'node',
    'layout_builder_styles',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->drupalPlaceBlock('local_tasks_block');

    // Create two nodes.
    $this->createContentType([
      'type' => 'bundle_with_section_field',
      'name' => 'Bundle with section field',
    ]);

    LayoutBuilderEntityViewDisplay::load('node.bundle_with_section_field.default')
      ->enableLayoutBuilder()
      ->setOverridable()
      ->save();
  }

  /**
   * Test Layout Builder section styles can be created and applied.
   */
  public function testSectionStyles() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $section_node = $this->createNode([
      'type' => 'bundle_with_section_field',
      'title' => 'The first node title',
      'body' => [
        [
          'value' => 'The first node body',
        ],
      ],
    ]);

    $this->drupalLogin($this->drupalCreateUser([
      'configure any layout',
      'manage layout builder styles',
      'administer layout builder styles configuration',
    ]));

    // Create styles for section.
    LayoutBuilderStyle::create([
      'id' => 'Foobar',
      'label' => 'Foobar',
      'classes' => 'foo-style-class bar-style-class',
      'type' => 'section',
    ])->save();

    LayoutBuilderStyle::create([
      'id' => 'Foobar2',
      'label' => 'Foobar2',
      'classes' => 'foo2-style-class bar2-style-class',
      'type' => 'section',
    ])->save();

    // Add section to node with new styles.
    $this->drupalGet('node/' . $section_node->id());
    $assert_session->responseNotContains('foo-style-class bar-style-class');
    $assert_session->responseNotContains('foo2-style-class bar2-style-class');
    $page->clickLink('Layout');
    $page->clickLink('Add section');
    $page->clickLink('Two column');
    // Verify that only a single option may be selected.
    $assert_session->elementExists('css', 'select#edit-layout-builder-style option');
    $page->selectFieldOption('edit-layout-builder-style', 'Foobar');
    $page->pressButton('Add section');

    // Confirm section element contains the proper classes.
    $page->pressButton('Save layout');
    $assert_session->responseContains('foo-style-class bar-style-class');
    $assert_session->responseNotContains('foo2-style-class bar2-style-class');

    // Set the configuration to allow multiple styles per block.
    $this->drupalGet('admin/config/content/layout_builder_style/config');
    $page->selectFieldOption('edit-multiselect-multiple', 'multiple');
    $page->selectFieldOption('edit-form-type-multiple-select', 'multiple-select');
    $page->pressButton('Save configuration');

    $this->drupalGet('layout_builder/configure/section/overrides/node.' . $section_node->id() . '/0');
    $page->selectFieldOption('edit-layout-builder-style', 'Foobar', TRUE);
    $page->selectFieldOption('edit-layout-builder-style', 'Foobar2', TRUE);
    $page->pressButton('Update');
    $assert_session->responseContains('foo-style-class bar-style-class');
    $assert_session->responseContains('foo2-style-class bar2-style-class');

  }

  /**
   * Test Layout Builder block styles can be created and applied.
   */
  public function testBlockStyles() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $block_node = $this->createNode([
      'type' => 'bundle_with_section_field',
      'title' => 'The first node title',
      'body' => [
        [
          'value' => 'The first node body',
        ],
      ],
    ]);

    $this->drupalLogin($this->drupalCreateUser([
      'configure any layout',
      'manage layout builder styles',
      'administer layout builder styles configuration',
    ]));

    // Create styles for blocks.
    LayoutBuilderStyle::create([
      'id' => 'Foobar',
      'label' => 'Foobar',
      'classes' => 'foo-style-class bar-style-class',
      'type' => 'component',
    ])->save();

    LayoutBuilderStyle::create([
      'id' => 'Foobar2',
      'label' => 'Foobar2',
      'classes' => 'foo2-style-class bar2-style-class',
      'type' => 'component',
    ])->save();

    // Add block to node with new style.
    $this->drupalGet('node/' . $block_node->id());
    $assert_session->responseNotContains('foo-style-class bar-style-class');
    $assert_session->responseNotContains('foo2-style-class bar2-style-class');
    $page->clickLink('Layout');
    $page->clickLink('Add block');
    $page->clickLink('Powered by Drupal');
    // Verify that only a single option may be selected.
    $assert_session->elementExists('css', 'select#edit-layout-builder-style option');
    $page->selectFieldOption('edit-layout-builder-style', 'Foobar');
    $page->pressButton('Add block');
    $page->pressButton('Save layout');

    // Confirm block element contains proper classes.
    $assert_session->responseContains('foo-style-class bar-style-class');
    $assert_session->responseNotContains('foo2-style-class bar2-style-class');

    // Set the configuration to allow multiple styles per block.
    $this->drupalGet('admin/config/content/layout_builder_style/config');
    $page->selectFieldOption('edit-multiselect-multiple', 'multiple');
    $page->selectFieldOption('edit-form-type-multiple-select', 'multiple-select');
    $page->pressButton('Save configuration');

    // Change block configuration to have multiple styles.
    $components = Node::load($block_node->id())->get('layout_builder__layout')->getSection(0)->getComponents();
    end($components);
    $uuid = key($components);
    $this->drupalGet('layout_builder/update/block/overrides/node.' . $block_node->id() . '/0/content/' . $uuid);
    $page->selectFieldOption('edit-layout-builder-style', 'Foobar', TRUE);
    $page->selectFieldOption('edit-layout-builder-style', 'Foobar2', TRUE);
    $page->pressButton('Update');
    $page->pressButton('Save layout');

    // Confirm block element contains proper classes.
    $assert_session->responseContains('foo-style-class bar-style-class');
    $assert_session->responseContains('foo2-style-class bar2-style-class');

    // Change block back to first style.
    $this->drupalGet('layout_builder/update/block/overrides/node.' . $block_node->id() . '/0/content/' . $uuid);
    $page->selectFieldOption('edit-layout-builder-style', 'Foobar');
    $page->pressButton('Update');
    $page->pressButton('Save layout');

    // Confirm change to settings now only renders first style.
    $assert_session->responseContains('foo-style-class bar-style-class');
    $assert_session->responseNotContains('foo2-style-class bar2-style-class');

    // Change configuration to use checkboxes element.
    $this->drupalGet('admin/config/content/layout_builder_style/config');
    $page->selectFieldOption('edit-multiselect-multiple', 'multiple');
    $page->selectFieldOption('edit-form-type-checkboxes', 'checkboxes');
    $page->pressButton('Save configuration');

    // Change block to use the second style.
    $this->drupalGet('layout_builder/update/block/overrides/node.' . $block_node->id() . '/0/content/' . $uuid);
    $page->uncheckField('edit-layout-builder-style-foobar', 'Foobar');
    $page->selectFieldOption('edit-layout-builder-style-foobar2', 'Foobar2');
    $page->pressButton('Update');
    $page->pressButton('Save layout');

    // Confirm change to settings now only renders second style.
    $assert_session->responseNotContains('foo-style-class bar-style-class');
    $assert_session->responseContains('foo2-style-class bar2-style-class');

  }

}
