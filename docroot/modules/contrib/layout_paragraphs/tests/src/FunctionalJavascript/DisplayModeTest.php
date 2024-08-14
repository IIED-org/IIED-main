<?php

namespace Drupal\Tests\layout_paragraphs\FunctionalJavascript;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity\EntityFormMode;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Entity\Entity\EntityViewMode;

/**
 * Tests setting non-default view modes for paragraphs rendered on the page and
 * rendered in the preview. Also tests setting a non-default form display mode.
 *
 * @group layout_paragraphs
 */
class DisplayModeTest extends BuilderTestBase {

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
  ];

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Add a view mode to paragraphs.
    $entity_view_mode = EntityViewMode::create([
      'id' => 'paragraph.alternative',
      'targetEntityType' => 'paragraph',
      'label' => 'Alternative',
      'status' => TRUE,
    ]);
    $entity_view_mode->save();

    $para_view_mode = EntityViewDisplay::create([
      'targetEntityType' => 'paragraph',
      'bundle' => 'text',
      'mode' => 'alternative',
    ]);
    $para_view_mode->save();

    // Set the display modes on the node.
    $node_view_display = EntityViewDisplay::load('node.page.default');
    $node_view_display->setComponent('field_content', [
      'type' => 'layout_paragraphs',
      'settings' => [
        'view_mode' => 'alternative',
      ],
    ])->save();

    // Add a form mode to paragraphs.
    $entity_form_mode = EntityFormMode::create([
      'id' => 'paragraph.alternative',
      'targetEntityType' => 'paragraph',
      'label' => 'Alternative',
      'status' => TRUE,
    ]);
    $entity_form_mode->save();

    // Copy the default form display.
    EntityFormDisplay::load('paragraph.text.default')
      ->createCopy('alternative')
      ->save();

    // Add the created field to the form display.
    $para_form_display = EntityFormDisplay::load('paragraph.text.alternative');
    $para_form_display->setComponent('created', [
      'type' => 'datetime_timestamp',
    ])->save();

    $node_form_display = EntityFormDisplay::load('node.page.default');
    $node_form_display->setComponent('field_content', [
      'type' => 'layout_paragraphs',
      'settings' => [
        'form_display_mode' => 'alternative',
        'preview_view_mode' => 'alternative',
      ],
    ])->save();
  }

  /**
   * Tests alternative view mode.
   */
  public function testAlternativeViewMode() {
    $this->loginWithPermissions([
      'create page content',
      'edit own page content',
    ]);

    $this->drupalGet('node/add/page');

    // Add a section component and a text component.
    $this->addSectionComponent(0, '.lpb-btn--add');
    $this->addTextComponent('First', '.layout__region--content .lpb-btn--add');

    // Save the node.
    $this->submitForm([
      'title[0][value]' => 'Node title',
    ], 'Save');

    $this->assertSession()->pageTextContains('First');
    $this->assertSession()
      ->elementExists('css', '.paragraph--view-mode--alternative');
  }

  /**
   * Tests alternative preview view mode.
   */
  public function testAlternativePreviewViewMode() {
    $this->loginWithPermissions([
      'create page content',
      'edit own page content',
    ]);

    $this->drupalGet('node/add/page');

    // Add a section component and a text component.
    $this->addSectionComponent(0, '.lpb-btn--add');
    $this->addTextComponent('Alternative preview text', '.layout__region--content .lpb-btn--add');

    // Check for the added components.
    $this->assertSession()->pageTextContains('Alternative preview text');
    $this->assertSession()
      ->elementExists('css', '.paragraph--view-mode--alternative');
  }

  /**
   * Tests alternative form display mode.
   */
  public function testAlternativeFormDisplayMode() {
    $entity_form_display = EntityFormDisplay::load('node.page.default');
    $component = $entity_form_display->getComponent('field_content');
    $component['settings']['form_display_mode'] = 'alternative';
    $entity_form_display
      ->setComponent('field_content', $component)
      ->save();

    $this->loginWithPermissions([
      'create page content',
      'edit own page content',
    ]);

    $this->drupalGet('node/add/page');

    // Add a section component and a text component.
    $this->addSectionComponent(0, '.lpb-btn--add');
    $this->addTextComponent('Alternative form mode', '.layout__region--content .lpb-btn--add');

    // Edit the text component.
    $page = $this->getSession()->getPage();
    $button = $page->find('css', '.layout__region--content .paragraph--type--text .lpb-edit');
    $button->click();
    $this->assertSession()->assertWaitOnAjaxRequest();

    $this->assertSession()->pageTextContains('Alternative form mode');
    $this->assertSession()->pageTextContains('Authored on');
  }

}
