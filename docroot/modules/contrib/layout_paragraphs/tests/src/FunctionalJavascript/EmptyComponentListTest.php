<?php

namespace Drupal\Tests\layout_paragraphs\FunctionalJavascript;

/**
 * Tests adding a new layout section to layout paragraphs.
 *
 * @group layout_paragraphs
 */
class EmptyComponentListTest extends BuilderTestBase {

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
    'layout_paragraphs_empty_component_list_test',
  ];

  /**
   * Tests that the default empty message is displayed.
   */
  public function testDefaultEmptyMessage() {
    $this->testMessagesShown([
      'No components to add.',
      'All components removed.',
    ]);
  }

  /**
   * Tests that a custom empty message is displayed.
   */
  public function testCustomEmptyMessage() {
    $custom_message = 'Custom empty message';
    $this->loginWithPermissions([
      'administer site configuration',
    ]);
    $this->drupalGet('/admin/config/content/layout_paragraphs/labels');
    $this->submitForm([
      'empty_message' => $custom_message,
    ], 'Save');
    $this->testMessagesShown([
      $custom_message,
      'All components removed.',
    ]);
  }

  /**
   * Tests that the provided messages are shown after clicking the "+" button.
   *
   * @param array $messages
   *   An array of messages to test for.
   */
  protected function testMessagesShown(array $messages) {
    $this->loginWithPermissions([
      'create page content',
      'edit own page content',
    ]);
    $this->drupalGet('/node/add/page');
    $page = $this->getSession()->getPage();
    // Click the Add Component button.
    $page->find('css', '.lpb-btn--add')->click();
    $this->assertSession()->assertWaitOnAjaxRequest(1000, 'Unable to click add a component.');
    // Test that the default message shows up.
    foreach ($messages as $message) {
      $this->assertSession()->pageTextContains($message);
    }
  }

}
