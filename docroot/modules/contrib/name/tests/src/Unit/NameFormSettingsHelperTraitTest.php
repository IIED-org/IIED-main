<?php

declare(strict_types=1);

namespace Drupal\Tests\name\Unit;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Tests\UnitTestCase;
use Drupal\name\Service\NameComponentMetadataService;
use Drupal\name\Traits\NameFormSettingsHelperTrait;

/**
 * Tests the NameFormSettingsHelperTrait.
 *
 * @group name
 * @coversDefaultClass \Drupal\name\Traits\NameFormSettingsHelperTrait
 */
class NameFormSettingsHelperTraitTest extends UnitTestCase {

  /**
   * A test class that uses the trait.
   */
  private object $traitObject;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Set up container with string translation and component metadata.
    $container = new ContainerBuilder();
    $container->set('string_translation', $this->getStringTranslationStub());
    $container->set('name.component_metadata', new NameComponentMetadataService(
      $container->get('string_translation'),
    ));
    \Drupal::setContainer($container);

    // Create a test class that uses the trait and translation.
    $this->traitObject = new class() {
      use NameFormSettingsHelperTrait;
      use StringTranslationTrait;
    };
  }

  /**
   * Tests fieldSettingsFormPreRender with component checked.
   *
   * @covers ::fieldSettingsFormPreRender
   */
  public function testFieldSettingsFormPreRenderWithComponentChecked(): void {
    $form = [
      'components' => [
        '#title' => 'Components',
        'given' => [
          '#type' => 'checkbox',
          '#title' => 'Given',
        ],
      ],
      'labels' => [
        '#title' => 'Labels',
        'given' => [
          '#type' => 'textfield',
          '#title' => 'Label for Given',
          '#default_value' => 'First Name',
        ],
      ],
    ];

    $result = $this->traitObject->fieldSettingsFormPreRender($form);

    // Verify #states structure exists.
    $this->assertArrayHasKey('name_settings', $result);
    $this->assertArrayHasKey('table', $result['name_settings']);
    $this->assertSame('table', $result['name_settings']['table']['#type']);
    $this->assertArrayHasKey('labels', $result['name_settings']['table']);
    $this->assertArrayHasKey('given', $result['name_settings']['table']['labels']);

    // Verify #states includes the component checked condition.
    $states = $result['name_settings']['table']['labels']['given']['#states'];
    $this->assertArrayHasKey('visible', $states);
    $this->assertIsArray($states['visible']);
    // Two conditions + 'or'.
    $this->assertCount(3, $states['visible']);

    // Verify first condition (component checked).
    $this->assertIsArray($states['visible'][0]);
    $this->assertArrayHasKey(':input[name$="[components][given]"]', $states['visible'][0]);
    $this->assertEquals(['checked' => TRUE], $states['visible'][0][':input[name$="[components][given]"]']);

    // Verify OR operator.
    $this->assertEquals('or', $states['visible'][1]);

    // Verify second condition (label empty).
    $this->assertIsArray($states['visible'][2]);
    $this->assertArrayHasKey(':input[name$="[labels][given]"]', $states['visible'][2]);
    $this->assertEquals(['empty' => TRUE], $states['visible'][2][':input[name$="[labels][given]"]']);
  }

  /**
   * Tests fieldSettingsFormPreRender with blank label.
   *
   * @covers ::fieldSettingsFormPreRender
   */
  public function testFieldSettingsFormPreRenderWithBlankLabel(): void {
    $form = [
      'components' => [
        '#title' => 'Components',
        'given' => [
          '#type' => 'checkbox',
          '#title' => 'Given',
        ],
      ],
      'labels' => [
        '#title' => 'Labels',
        'given' => [
          '#type' => 'textfield',
          '#title' => 'Label for Given',
      // Blank label.
          '#default_value' => '',
        ],
      ],
    ];

    $result = $this->traitObject->fieldSettingsFormPreRender($form);

    // Verify #states includes OR condition for blank labels.
    $states = $result['name_settings']['table']['labels']['given']['#states'];
    $this->assertArrayHasKey('visible', $states);
    // Two conditions + 'or'.
    $this->assertCount(3, $states['visible']);
    $this->assertEquals('or', $states['visible'][1]);

    // Verify the label empty condition exists.
    $this->assertIsArray($states['visible'][2]);
    $this->assertArrayHasKey(':input[name$="[labels][given]"]', $states['visible'][2]);
    $this->assertEquals(['empty' => TRUE], $states['visible'][2][':input[name$="[labels][given]"]']);
  }

  /**
   * Tests fieldSettingsFormPreRender #states structure.
   *
   * @covers ::fieldSettingsFormPreRender
   */
  public function testFieldSettingsFormPreRenderStatesStructure(): void {
    $form = [
      'components' => [
        '#title' => 'Components',
        'title' => [
          '#type' => 'checkbox',
          '#title' => 'Title',
        ],
        'given' => [
          '#type' => 'checkbox',
          '#title' => 'Given',
        ],
      ],
      'labels' => [
        '#title' => 'Labels',
        'title' => [
          '#type' => 'textfield',
          '#title' => 'Label for Title',
          '#default_value' => 'Title',
        ],
        'given' => [
          '#type' => 'textfield',
          '#title' => 'Label for Given',
          '#default_value' => '',
        ],
      ],
    ];

    $result = $this->traitObject->fieldSettingsFormPreRender($form);

    // Verify structure for both components.
    foreach (['title', 'given'] as $key) {
      $states = $result['name_settings']['table']['labels'][$key]['#states'];
      $this->assertArrayHasKey('visible', $states);
      $this->assertIsArray($states['visible']);
      $this->assertCount(3, $states['visible']);

      // Verify structure: [condition1, 'or', condition2].
      $this->assertIsArray($states['visible'][0]);
      $this->assertEquals('or', $states['visible'][1]);
      $this->assertIsArray($states['visible'][2]);

      // Verify selectors are correct.
      $component_selector = ':input[name$="[components][' . $key . ']"]';
      $label_selector = ':input[name$="[labels][' . $key . ']"]';

      $this->assertArrayHasKey($component_selector, $states['visible'][0]);
      $this->assertArrayHasKey($label_selector, $states['visible'][2]);
    }
  }

  /**
   * Tests fieldSettingsFormPreRender applies to all children.
   *
   * @covers ::fieldSettingsFormPreRender
   */
  public function testFieldSettingsFormPreRenderAllChildren(): void {
    $form = [
      'components' => [
        '#title' => 'Components',
        'given' => [
          '#type' => 'checkbox',
          '#title' => 'Given',
        ],
      ],
      'labels' => [
        '#title' => 'Labels',
        'given' => [
          '#type' => 'textfield',
          '#title' => 'Label for Given',
          '#default_value' => 'First Name',
        ],
      ],
      'size' => [
        '#title' => 'HTML size',
        'given' => [
          '#type' => 'number',
          '#title' => 'Size for Given',
          '#default_value' => 20,
        ],
      ],
      'title_display' => [
        '#title' => 'Label display',
        'given' => [
          '#type' => 'radios',
          '#title' => 'Display for Given',
          '#default_value' => 'description',
        ],
      ],
    ];

    $result = $this->traitObject->fieldSettingsFormPreRender($form);

    // Verify all children (except components) get the OR condition.
    $children = ['labels', 'size', 'title_display'];
    foreach ($children as $child) {
      $this->assertArrayHasKey($child, $result['name_settings']['table']);
      $this->assertArrayHasKey('given', $result['name_settings']['table'][$child]);

      $states = $result['name_settings']['table'][$child]['given']['#states'];
      $this->assertArrayHasKey('visible', $states);
      $this->assertCount(3, $states['visible']);
      $this->assertEquals('or', $states['visible'][1]);
    }

    // Verify components row does NOT get #states.
    $this->assertArrayHasKey('components', $result['name_settings']['table']);
    $this->assertArrayHasKey('given', $result['name_settings']['table']['components']);
    $this->assertArrayNotHasKey('#states', $result['name_settings']['table']['components']['given']);
  }

  /**
   * Tests fieldSettingsFormPreRender with excluded components.
   *
   * @covers ::fieldSettingsFormPreRender
   */
  public function testFieldSettingsFormPreRenderExcludedComponents(): void {
    $form = [
      // #excluded_components should be an associative array with component
      // keys.
      '#excluded_components' => [
        'middle' => 'Middle name(s)',
        'generational' => 'Generational',
      ],
      'components' => [
        '#title' => 'Components',
        'given' => [
          '#type' => 'checkbox',
          '#title' => 'Given',
        ],
        'family' => [
          '#type' => 'checkbox',
          '#title' => 'Family',
        ],
      ],
      'labels' => [
        '#title' => 'Labels',
        'given' => [
          '#type' => 'textfield',
          '#title' => 'Label for Given',
          '#default_value' => 'First Name',
        ],
        'family' => [
          '#type' => 'textfield',
          '#title' => 'Label for Family',
          '#default_value' => 'Last Name',
        ],
        'middle' => [
          '#type' => 'textfield',
          '#title' => 'Label for Middle',
          '#default_value' => 'Middle',
        ],
      ],
    ];

    $result = $this->traitObject->fieldSettingsFormPreRender($form);

    // Verify included components have #states.
    foreach (['given', 'family'] as $key) {
      $this->assertArrayHasKey('labels', $result['name_settings']['table']);
      $this->assertArrayHasKey($key, $result['name_settings']['table']['labels']);
      $states = $result['name_settings']['table']['labels'][$key]['#states'];
      $this->assertArrayHasKey('visible', $states);
    }

    // Verify excluded components are in hidden section.
    // The code moves excluded components to hidden[child][key] structure.
    // Note: hidden is initialized with ['#access' => FALSE], then children
    // are added.
    $this->assertArrayHasKey('hidden', $result);
    // The middle component was provided in the labels form child, so it
    // should be moved to hidden.
    // The structure should be: hidden['labels']['middle'] = [element with
    // #access = FALSE].
    $this->assertArrayHasKey(
      'labels',
      $result['hidden'],
      'Excluded component should be in hidden[labels] when provided in labels form child'
    );
    $this->assertArrayHasKey(
      'middle',
      $result['hidden']['labels'],
      'Middle component should be in hidden[labels][middle]'
    );
    $this->assertFalse(
      $result['hidden']['labels']['middle']['#access'],
      'Excluded component should have #access = FALSE'
    );

    // Also verify that excluded components that were provided in the form
    // are not in the visible table.
    $this->assertArrayHasKey('labels', $result['name_settings']['table']);
    // The 'middle' component was provided in the form and is excluded, so
    // it should NOT be in the visible table.
    $this->assertArrayNotHasKey(
      'middle',
      $result['name_settings']['table']['labels'],
      'Excluded component that was provided in form should not be in visible table'
    );

    // Note: The 'generational' component was excluded but NOT provided in
    // the labels form child.
    // According to the code logic (lines 113-161 in
    // NameFormSettingsHelperTrait.php):
    // - Line 114: Checks if component is excluded AND exists in form child
    //   → moves to hidden
    // - Line 119: Else if component exists in form child → adds to visible
    //   table
    // - Line 154: Else (component doesn't exist) → creates empty cell
    // So excluded components that aren't in the form child will still get
    // empty cells created, meaning they WILL appear in the visible table as
    // empty cells (#markup => "&nbsp;").
    // This is the current behavior - we only verify that excluded
    // components that WERE provided are moved to hidden and not in the
    // visible table.
  }

}
