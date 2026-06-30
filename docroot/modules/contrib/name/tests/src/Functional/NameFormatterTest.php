<?php

declare(strict_types=1);

namespace Drupal\Tests\name\Functional;

use Drupal\Component\Utility\DeprecationHelper;
use Drupal\Core\Url;
use Drupal\name\Entity\NameFormat;
use Drupal\name\Entity\NameListFormat;
use Drupal\node\Entity\NodeType;

/**
 * Functional tests for the NameFormatter service.
 *
 * Tests the NameFormatter service through the web interface and with real
 * user interactions, including field display, node creation, and formatting
 * through the UI.
 *
 * @group name
 * @coversDefaultClass \Drupal\name\NameFormatter
 */
class NameFormatterTest extends NameTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field',
    'field_ui',
    'node',
    'name',
    'views',
  ];

  /**
   * The name formatter service.
   *
   * @var \Drupal\name\NameFormatter
   */
  protected $nameFormatter;

  /**
   * Test content type.
   *
   * @var \Drupal\node\Entity\NodeType
   */
  protected $contentType;

  /**
   * Test name field.
   *
   * @var string
   */
  protected $fieldName = 'field_test_name';

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    // Get the name formatter service.
    $this->nameFormatter = $this->container->get('name.formatter');

    // Create a test content type.
    $this->contentType = NodeType::create([
      'type' => 'test_content',
      'name' => 'Test Content',
    ]);
    $this->contentType->save();

    // Create a name field on the content type.
    $this->createNameField();
  }

  /**
   * Creates a name field on the test content type.
   */
  protected function createNameField(): void {
    $this->drupalLogin($this->adminUser);

    // Add the name field.
    $this->drupalGet('admin/structure/types/manage/test_content/fields/add-field');

    DeprecationHelper::backwardsCompatibleCall(
      \Drupal::VERSION,
      '11.2.2',
      fn() => $this->getSession()->getPage()->clickLink('Name'),
      fn() => !$this->getSession()->getPage()->fillField('new_storage_type', 'name') && $this->getSession()->getPage()->pressButton('Continue')
    );

    $field_settings = [
      'label' => 'Test Name',
      'field_name' => 'test_name',
    ];
    $this->submitForm($field_settings, 'Continue');

    // Configure the field storage settings.
    $storage_settings = [
      'settings[components][title]' => TRUE,
      'settings[components][given]' => TRUE,
      'settings[components][middle]' => TRUE,
      'settings[components][family]' => TRUE,
      'settings[components][generational]' => TRUE,
      'settings[components][credentials]' => TRUE,
      'settings[minimum_components][given]' => TRUE,
      'settings[minimum_components][family]' => TRUE,
      'settings[labels][title]' => 'Title',
      'settings[labels][given]' => 'Given name',
      'settings[labels][middle]' => 'Middle name',
      'settings[labels][family]' => 'Family name',
      'settings[labels][generational]' => 'Generational',
      'settings[labels][credentials]' => 'Credentials',
      'settings[max_length][title]' => 31,
      'settings[max_length][given]' => 63,
      'settings[max_length][middle]' => 127,
      'settings[max_length][family]' => 63,
      'settings[max_length][generational]' => 15,
      'settings[max_length][credentials]' => 255,
      'settings[title_options]' => "-- --\nMr.\nMrs.\nMiss\nDr.\nProf.",
      'settings[generational_options]' => "-- --\nJr.\nSr.\nI\nII\nIII\nIV",
    ];

    // Submit the storage settings and check for errors.
    DeprecationHelper::backwardsCompatibleCall(
      \Drupal::VERSION,
      '11.2.2',
      fn() => $this->submitForm($storage_settings, 'Save'),
      fn() => $this->submitForm($storage_settings, 'Save settings')
    );

    // Wait for the field to be fully created and clear caches.
    $this->resetAll();

    // Verify the field was created by checking the field list.
    $this->drupalGet('admin/structure/types/manage/test_content/fields');
    $this->assertSession()->pageTextContains('Test Name');

    // Also verify the field is accessible on the node add form.
    $this->drupalGet('node/add/test_content');
    $this->assertSession()->pageTextContains('Test Name');

    // Wait a bit more for the field to be fully available.
    sleep(1);

    // Final verification that the field components are accessible.
    $this->drupalGet('node/add/test_content');

    $this->assertSession()->fieldExists($this->fieldName . '[0][title]');
    $this->assertSession()->fieldExists($this->fieldName . '[0][given]');
    $this->assertSession()->fieldExists($this->fieldName . '[0][family]');
  }

  /**
   * Tests basic name formatting through the web interface.
   */
  public function testNameFormattingThroughWebInterface(): void {
    $this->drupalLogin($this->adminUser);

    // Create a test node with a name field.
    $node = $this->createTestNode();

    // View the node and verify the name is formatted correctly.
    $this->drupalGet('node/' . $node->id());
    $this->assertSession()->pageTextContains('Dr. John Michael Smith Jr., PhD');
  }

  /**
   * Tests name formatting with different format types.
   */
  public function testNameFormattingWithDifferentFormats(): void {
    // Create custom name formats for testing.
    $this->createCustomNameFormats();

    // Test the formatter service directly with different formats.
    $components = [
      'title' => 'Dr.',
      'given' => 'John',
      'middle' => 'Michael',
      'family' => 'Smith',
      'generational' => 'Jr.',
      'credentials' => 'PhD',
    ];

    // Test default format.
    $formatted = $this->nameFormatter->format($components, 'default');
    $this->assertStringContainsString('Dr. John Michael Smith Jr., PhD', (string) $formatted);

    // Test full format.
    $formatted = $this->nameFormatter->format($components, 'full');
    $this->assertStringContainsString('Dr. John Michael Smith Jr., PhD', (string) $formatted);

    // Test formal format.
    $formatted = $this->nameFormatter->format($components, 'test_formal');
    $this->assertStringContainsString('Dr. Smith', (string) $formatted);

    // Test family format.
    $formatted = $this->nameFormatter->format($components, 'test_family');
    $this->assertStringContainsString('Smith', (string) $formatted);
  }

  /**
   * Tests name list formatting functionality.
   */
  public function testNameListFormatting(): void {
    // Create custom list formats for testing.
    $this->createCustomListFormats();

    $names = [
      [
        'title' => 'Dr.',
        'given' => 'John',
        'family' => 'Smith',
        'credentials' => 'PhD',
      ],
      [
        'title' => 'Prof.',
        'given' => 'Jane',
        'family' => 'Doe',
        'credentials' => 'MD',
      ],
      [
        'title' => 'Mr.',
        'given' => 'Bob',
        'family' => 'Johnson',
      ],
    ];

    // Test default list format.
    $formatted = $this->nameFormatter->formatList($names, 'default', 'default');
    $this->assertStringContainsString('Dr. John Smith, PhD', (string) $formatted);
    $this->assertStringContainsString('Prof. Jane Doe, MD', (string) $formatted);
    $this->assertStringContainsString('Mr. Bob Johnson', (string) $formatted);

    // Test with et al formatting.
    $formatted = $this->nameFormatter->formatList($names, 'default', 'et_al');
    $this->assertStringContainsString('Dr. John Smith, PhD, et al', (string) $formatted);
  }

  /**
   * Tests name formatting with URL components.
   */
  public function testNameFormattingWithUrl(): void {
    $components = [
      'title' => 'Dr.',
      'given' => 'John',
      'family' => 'Smith',
      'url' => Url::fromUri('https://example.com/john-smith'),
    ];

    $formatted = $this->nameFormatter->format($components);
    $this->assertStringContainsString('<a href="https://example.com/john-smith">', (string) $formatted);
    $this->assertStringContainsString('Dr. John Smith', (string) $formatted);
    $this->assertStringContainsString('</a>', (string) $formatted);
  }

  /**
   * Tests name formatting settings.
   */
  public function testNameFormattingSettings(): void {
    // Test default settings.
    $this->assertEquals(' ', $this->nameFormatter->getSetting('sep1'));
    $this->assertEquals(', ', $this->nameFormatter->getSetting('sep2'));
    $this->assertEquals('', $this->nameFormatter->getSetting('sep3'));
    $this->assertEquals('none', $this->nameFormatter->getSetting('markup'));

    // Test setting custom values.
    $this->nameFormatter->setSetting('sep1', ' - ');
    $this->assertEquals(' - ', $this->nameFormatter->getSetting('sep1'));

    $this->nameFormatter->setSetting('markup', 'html');
    $this->assertEquals('html', $this->nameFormatter->getSetting('markup'));
  }

  /**
   * Tests delimiter type options.
   */
  public function testDelimiterTypeOptions(): void {
    $types = $this->nameFormatter->getLastDelimiterTypes(TRUE);
    $this->assertArrayHasKey('text', $types);
    $this->assertArrayHasKey('symbol', $types);
    $this->assertArrayHasKey('inherit', $types);

    $types_no_examples = $this->nameFormatter->getLastDelimiterTypes(FALSE);
    $this->assertArrayHasKey('text', $types_no_examples);
    $this->assertArrayHasKey('symbol', $types_no_examples);
    $this->assertArrayHasKey('inherit', $types_no_examples);
  }

  /**
   * Tests delimiter behavior options.
   */
  public function testDelimiterBehaviorOptions(): void {
    $behaviors = $this->nameFormatter->getLastDelimiterBehaviors(TRUE);
    $this->assertArrayHasKey('never', $behaviors);
    $this->assertArrayHasKey('always', $behaviors);
    $this->assertArrayHasKey('contextual', $behaviors);

    $behaviors_no_examples = $this->nameFormatter->getLastDelimiterBehaviors(FALSE);
    $this->assertArrayHasKey('never', $behaviors_no_examples);
    $this->assertArrayHasKey('always', $behaviors_no_examples);
    $this->assertArrayHasKey('contextual', $behaviors_no_examples);
  }

  /**
   * Tests name formatting through node creation and editing.
   */
  public function testNameFormattingInNodeOperations(): void {
    $this->drupalLogin($this->adminUser);

    // Create a node with a name field.
    $edit = [
      'title[0][value]' => 'Test Node with Name',
      $this->fieldName . '[0][title]' => 'Dr.',
      $this->fieldName . '[0][given]' => 'John',
      $this->fieldName . '[0][middle]' => 'Michael',
      $this->fieldName . '[0][family]' => 'Smith',
      $this->fieldName . '[0][generational]' => 'Jr.',
      $this->fieldName . '[0][credentials]' => 'PhD',
    ];

    $this->drupalGet('node/add/test_content');
    $this->submitForm($edit, 'Save');

    // Verify the node was created and the name is displayed correctly.
    $this->assertSession()->pageTextContains('Test Node with Name');
    $this->assertSession()->pageTextContains('Dr. John Michael Smith Jr., PhD');

    // Edit the node and change the name.
    $node = $this->getTestNodeByTitle('Test Node with Name');
    $this->drupalGet('node/' . $node->id() . '/edit');

    $edit = [
      $this->fieldName . '[0][title]' => 'Prof.',
      $this->fieldName . '[0][given]' => 'Jane',
      $this->fieldName . '[0][middle]' => '',
      $this->fieldName . '[0][family]' => 'Doe',
      $this->fieldName . '[0][generational]' => '_none',
      $this->fieldName . '[0][credentials]' => 'MD',
    ];

    $this->submitForm($edit, 'Save');

    // Verify the updated name is displayed correctly.
    $this->assertSession()->pageTextContains('Prof. Jane Doe, MD');
  }

  /**
   * Creates a test node with name field data.
   *
   * @return \Drupal\node\Entity\Node
   *   The created test node.
   */
  protected function createTestNode() {
    $edit = [
      'title[0][value]' => 'Test Node',
      $this->fieldName . '[0][title]' => 'Dr.',
      $this->fieldName . '[0][given]' => 'John',
      $this->fieldName . '[0][middle]' => 'Michael',
      $this->fieldName . '[0][family]' => 'Smith',
      $this->fieldName . '[0][generational]' => 'Jr.',
      $this->fieldName . '[0][credentials]' => 'PhD',
    ];

    $this->drupalGet('node/add/test_content');

    // Debug: Check if the form fields are present.
    $this->assertSession()->fieldExists('title[0][value]');

    // Check if the name field components are present.
    $this->assertSession()->fieldExists($this->fieldName . '[0][title]');
    $this->assertSession()->fieldExists($this->fieldName . '[0][given]');
    $this->assertSession()->fieldExists($this->fieldName . '[0][family]');

    $this->submitForm($edit, 'Save');

    return $this->getTestNodeByTitle('Test Node');
  }

  /**
   * Creates custom name formats for testing.
   */
  protected function createCustomNameFormats(): void {
    // Create a formal format.
    $formal_format = NameFormat::create([
      'id' => 'test_formal',
      'label' => 'Formal',
      'pattern' => 't+if',
      'status' => TRUE,
    ]);
    $formal_format->save();

    // Create a family format.
    $family_format = NameFormat::create([
      'id' => 'test_family',
      'label' => 'Family Only',
      'pattern' => 'if',
      'status' => TRUE,
    ]);
    $family_format->save();
  }

  /**
   * Creates custom list formats for testing.
   */
  protected function createCustomListFormats(): void {
    // Create an et al format.
    $et_al_format = NameListFormat::create([
      'id' => 'et_al',
      'label' => 'Et Al',
      'delimiter' => ', ',
      'and' => 'text',
      'delimiter_precedes_last' => 'never',
      'el_al_min' => 2,
      'el_al_first' => 1,
      'status' => TRUE,
    ]);
    $et_al_format->save();
  }

  /**
   * Gets a test node by its title.
   *
   * @param string $title
   *   The node title.
   *
   * @return \Drupal\node\Entity\Node|null
   *   The node entity or null if not found.
   */
  protected function getTestNodeByTitle(string $title) {
    $nodes = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->loadByProperties(['title' => $title]);
    return !empty($nodes) ? reset($nodes) : NULL;
  }

}
